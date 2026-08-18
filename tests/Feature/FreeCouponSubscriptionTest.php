<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use App\Models\Coupon;
use App\Models\Interval;
use Carbon\CarbonImmutable;
use App\Models\PlanInterval;
use Illuminate\Http\Request;
use App\Http\Responses\RegisterResponse;
use App\Http\Controllers\SubscriptionController;

function createPaidPlanIntervalForCouponTest(): PlanInterval
{
    $plan = Plan::query()->create([
        'name' => 'Vitrine',
        'description' => 'Plano de teste',
        'stripe_product_id' => 'prod_test',
        'stripe_price_id' => 'price_test',
        'currency' => 'brl',
        'features' => [],
        'is_active' => true,
        'is_default' => false,
        'new_user_discount_type' => 'none',
    ]);

    $interval = Interval::query()->create([
        'name' => 'Mensal',
        'code' => 'monthly-coupon-test',
        'is_active' => true,
    ]);

    return PlanInterval::query()->forceCreate([
        'plan_id' => $plan->id,
        'interval_id' => $interval->id,
        'price' => 100,
        'stripe_price_id' => 'price_test',
    ]);
}

function createStoreOwnerForCouponTest(): User
{
    $user = User::factory()->create();
    $team = Team::query()->forceCreate([
        'user_id' => $user->id,
        'name' => 'Loja Cupom',
        'slug' => 'loja-cupom-'.$user->id,
        'personal_team' => false,
        'status' => 'pendente',
    ]);

    $user->forceFill(['current_team_id' => $team->id])->save();

    return $user->refresh();
}

test('free period coupon activates a local trial without Stripe checkout', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-01-15 12:00:00'));

    $planInterval = createPaidPlanIntervalForCouponTest();
    $user = createStoreOwnerForCouponTest();
    $coupon = Coupon::query()->create([
        'code' => 'TRESMESES',
        'name' => 'Três meses grátis',
        'type' => 'percentage',
        'value' => 100,
        'duration_value' => 3,
        'duration_unit' => 'months',
        'is_active' => true,
    ]);

    $this->actingAs($user);
    session(['applied_coupon' => $coupon->code]);

    $request = Request::create('/register', 'POST', [
        'journey' => 'subscription',
        'plan' => $planInterval->id,
    ]);

    $response = app(RegisterResponse::class)->toResponse($request);

    expect($response->getTargetUrl())->toBe(route('dashboard'));
    expect(session('success'))->toContain('3 meses');
    expect(session('welcome_discount_text'))->toBe('3 meses grátis');
    expect(session()->has('applied_coupon'))->toBeFalse();

    $subscription = $user->subscriptions()->where('name', 'default')->firstOrFail();

    expect($subscription->stripe_id)->toStartWith('coupon_');
    expect($subscription->stripe_status)->toBe('active');
    expect((float) $subscription->final_price)->toBe(0.0);
    expect($subscription->coupon_id)->toBe($coupon->id);
    expect($subscription->trial_ends_at->toDateTimeString())->toBe('2026-04-15 12:00:00');
    expect($subscription->discount_ends_at->toDateTimeString())->toBe('2026-04-15 12:00:00');
    expect($subscription->items()->count())->toBe(1);

    expect($user->fresh()->stripe_id)->toBeNull();
    expect($user->currentTeam->fresh()->status)->toBe('ativo');
    expect($user->currentTeam->fresh()->plan_id)->toBe($planInterval->plan_id);
    expect(Team::query()->active()->whereKey($user->current_team_id)->exists())->toBeTrue();
    expect($coupon->fresh()->uses_count)->toBe(1);
});

test('free period coupon respects a duration configured in days', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-01-15 12:00:00'));

    $planInterval = createPaidPlanIntervalForCouponTest();
    $user = createStoreOwnerForCouponTest();
    $coupon = Coupon::query()->create([
        'code' => 'QUARENTACINCODIAS',
        'name' => 'Quarenta e cinco dias grátis',
        'type' => 'percentage',
        'value' => 100,
        'duration_value' => 45,
        'duration_unit' => 'days',
        'is_active' => true,
    ]);

    $this->actingAs($user);
    session(['applied_coupon' => $coupon->code]);

    app(RegisterResponse::class)->toResponse(Request::create('/register', 'POST', [
        'journey' => 'subscription',
        'plan' => $planInterval->id,
    ]));

    $subscription = $user->subscriptions()->where('name', 'default')->firstOrFail();

    expect(session('welcome_discount_text'))->toBe('45 dias grátis');
    expect($subscription->trial_ends_at->toDateTimeString())->toBe('2026-03-01 12:00:00');
    expect($subscription->discount_ends_at->toDateTimeString())->toBe('2026-03-01 12:00:00');
    expect($user->currentTeam->fresh()->status)->toBe('ativo');
});

test('partial discount continues through Stripe and does not activate a local trial', function (): void {
    config(['cashier.secret' => '']);

    $planInterval = createPaidPlanIntervalForCouponTest();
    $user = createStoreOwnerForCouponTest();
    $coupon = Coupon::query()->create([
        'code' => 'METADE',
        'name' => 'Metade do valor',
        'type' => 'percentage',
        'value' => 50,
        'duration_value' => 3,
        'duration_unit' => 'months',
        'is_active' => true,
    ]);

    $this->actingAs($user);
    session(['applied_coupon' => $coupon->code]);

    $request = Request::create('/register', 'POST', [
        'journey' => 'subscription',
        'plan' => $planInterval->id,
    ]);

    $response = app(RegisterResponse::class)->toResponse($request);

    expect($response->getTargetUrl())->toBe(route('dashboard'));
    expect($user->subscriptions()->exists())->toBeFalse();
    expect($user->currentTeam->fresh()->status)->toBe('pendente');
    expect($coupon->fresh()->uses_count)->toBe(0);
});

test('early subscription carries the remaining promotional period to Stripe pricing', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-01-15 12:00:00'));

    $user = createStoreOwnerForCouponTest();
    $coupon = Coupon::query()->create([
        'code' => 'ANTECIPADO',
        'name' => 'Trial antecipado',
        'type' => 'percentage',
        'value' => 100,
        'duration_value' => 3,
        'duration_unit' => 'months',
        'is_active' => true,
    ]);

    $user->subscriptions()->create([
        'name' => 'default',
        'type' => 'Vitrine Trial Promocional',
        'stripe_id' => 'coupon_early_subscription',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
        'trial_ends_at' => now()->addMonths(2),
        'coupon_id' => $coupon->id,
        'original_price' => 100,
        'discount_amount' => 100,
        'final_price' => 0,
        'discount_ends_at' => now()->addMonths(2),
    ]);

    $method = new ReflectionMethod(SubscriptionController::class, 'resolveActiveDiscountTrialCarryoverDays');
    $remainingDays = $method->invoke(app(SubscriptionController::class), $user, null);

    expect($remainingDays)->toBe(59);
});

test('storefront is deactivated after the free coupon trial expires', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-01-15 12:00:00'));

    $planInterval = createPaidPlanIntervalForCouponTest();
    $user = createStoreOwnerForCouponTest();
    $coupon = Coupon::query()->create([
        'code' => 'EXPIRA3MESES',
        'name' => 'Trial com expiração',
        'type' => 'percentage',
        'value' => 100,
        'duration_value' => 3,
        'duration_unit' => 'months',
        'is_active' => true,
    ]);

    $this->actingAs($user);
    session(['applied_coupon' => $coupon->code]);

    app(RegisterResponse::class)->toResponse(Request::create('/register', 'POST', [
        'journey' => 'subscription',
        'plan' => $planInterval->id,
    ]));

    expect($user->currentTeam->fresh()->status)->toBe('ativo');

    $this->travelTo(CarbonImmutable::parse('2026-04-15 12:00:01'));
    $this->artisan('subscriptions:expire-stores')->assertSuccessful();

    expect($user->currentTeam->fresh()->status)->toBe('inativo');
    expect(Team::query()->active()->whereKey($user->current_team_id)->exists())->toBeFalse();
});
