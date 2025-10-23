<?php

declare(strict_types=1);

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Route;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Support\Facades\RateLimiter;
use App\Actions\User\ActiveOauthProviderAction;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Http\Responses\RegisterResponse;

final class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind custom RegisterResponse
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->string(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        $this->configureLoginView();
        $this->configureRegisterView();
    }

    private function configureLoginView(): void
    {
        Fortify::loginView(fn () => Inertia::render('Auth/Login', [
            'availableOauthProviders' => (new ActiveOauthProviderAction())->handle(),
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]));
    }

    private function configureRegisterView(): void
    {
        Fortify::registerView(function (Request $request) {
            return Inertia::render('Auth/Register', [
                'plan' => $request->query('plan'),
            ]);
        });
    }
}
