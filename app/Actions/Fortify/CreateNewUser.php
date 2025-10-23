<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Models\Plan;
use App\Models\Media;
use Stripe\Customer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => Arr::get($input, 'password') ? $this->passwordRules() : 'sometimes',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            'plan' => ['nullable', 'string'],

            // Validação dos dados da loja (TanaVitrine)
            'store_name' => ['nullable', 'string', 'max:255'],
            'sale_type' => ['nullable', 'in:atacado,varejo,ambos'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(fn () => tap(User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Arr::get($input, 'password') ? Hash::make($input['password']) : Str::random(12),
        ]), function (User $user) use ($input): void {
            $team = $this->createTeam($user, $input);
            $this->createCustomer($user);
            $this->processOnboardingData($user, $team, $input);

            // Se tem dados da loja, criar vitrine imediatamente
            if (isset($input['store_name']) && !empty($input['store_name'])) {
                $this->createStoreFromOnboarding($user, $input);
            }
        }));
    }

    /**
     * Create a personal team for the user.
     */
    private function createTeam(User $user, array $input): Team
    {
        // Get default plan (free plan)
        $defaultPlan = Plan::where('is_default', true)->first();
        $planId = $defaultPlan ? $defaultPlan->id : null;

        $teamName = $input['establishment_name'] ?? explode(' ', $user->name, 2)[0]."'s Team";

        $team = $user->ownedTeams()->save(Team::query()->forceCreate([
            'user_id' => $user->id,
            'name' => $teamName,
            'personal_team' => true,
            'plan_id' => $planId,
        ]));

        // Store selected plan in session for checkout after registration
        if (!empty($input['plan'])) {
            session(['selected_plan' => $input['plan']]);
        }

        return $team;
    }

    /**
     * Create a billing customer for the user.
     */
    private function createCustomer(User $user): void
    {
        if (! Config::get('cashier.billing_enabled')) {
            return;
        }

        /** @var Customer $stripeCustomer */
        $stripeCustomer = $user->createOrGetStripeCustomer();

        $user->update([
            'stripe_id' => $stripeCustomer->id,
        ]);
    }

    /**
     * Process and save onboarding data (logo, photos, video, etc.)
     */
    private function processOnboardingData(User $user, Team $team, array $input): void
    {
        $onboardingData = [
            'establishment_name' => $input['establishment_name'] ?? null,
            'phone' => $input['phone'] ?? null,
            'slogan' => $input['slogan'] ?? null,
            'service_types' => $input['service_types'] ?? [],
            'social_media' => $input['social_media'] ?? [],
            'video_link' => $input['video_link'] ?? null,
        ];

        // Processar address se presente
        if (isset($input['address'])) {
            $onboardingData['address'] = is_string($input['address'])
                ? json_decode($input['address'], true)
                : $input['address'];
        }

        // Processar social_media se presente como string JSON
        if (isset($input['social_media']) && is_string($input['social_media'])) {
            $onboardingData['social_media'] = json_decode($input['social_media'], true);
        }

        // Processar service_types se presente como string JSON
        if (isset($input['service_types']) && is_string($input['service_types'])) {
            $onboardingData['service_types'] = json_decode($input['service_types'], true);
        }

        $teamPath = "clinics/team_{$team->id}";

        // Processar upload de logo
        if (request()->hasFile('logo')) {
            $logoFile = request()->file('logo');
            $logoPath = $logoFile->store($teamPath . '/logo', 'public');
            $onboardingData['logo_path'] = $logoPath;
        }

        // Processar upload de fotos
        if (request()->hasFile('photos')) {
            $photoPaths = [];
            foreach (request()->file('photos') as $index => $photo) {
                $photoPath = $photo->store($teamPath . '/photos', 'public');
                $photoPaths[] = $photoPath;
            }
            $onboardingData['photos_paths'] = $photoPaths;
        }

        // Processar upload de vídeo institucional
        if (request()->hasFile('video')) {
            $videoFile = request()->file('video');
            $videoPath = $videoFile->store($teamPath . '/video', 'public');
            $onboardingData['video_path'] = $videoPath;
        }

        // Salvar dados de onboarding no usuário
        $user->update([
            'onboarding_data' => $onboardingData,
            'onboarding_completed' => false, // Será true após preencher endereço de entrega
        ]);
    }

    /**
     * Create store vitrine from onboarding data (TanaVitrine)
     */
    private function createStoreFromOnboarding(User $user, array $input): void
    {
        // Gerar slug único para a loja
        $slug = Str::slug($input['store_name']);
        $count = 1;
        while (Team::where('slug', $slug)->exists()) {
            $slug = Str::slug($input['store_name']) . '-' . $count;
            $count++;
        }

        // Processar endereço se presente
        $address = null;
        if (isset($input['address'])) {
            $address = is_string($input['address'])
                ? json_decode($input['address'], true)
                : $input['address'];
        }

        // Processar redes sociais se presente
        $socialMedia = null;
        if (isset($input['social_media'])) {
            $socialMedia = is_string($input['social_media'])
                ? json_decode($input['social_media'], true)
                : $input['social_media'];
        }

        // Criar a vitrine do usuário
        $store = Team::create([
            'user_id' => $user->id,
            'name' => $input['store_name'],
            'slug' => $slug,
            'sale_type' => $input['sale_type'] ?? 'varejo',
            'category_id' => $input['category_id'] ?? null,
            'subcategory' => $input['subcategory'] ?? null,
            'gender' => $input['gender'] ?? null,
            'description' => $input['description'] ?? '',
            'min_order' => $input['min_order'] ?? null,
            'store_type' => 'virtual', // Default
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'whatsapp' => $input['whatsapp'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email' => $user->email,
            'status' => 'ativo', // Aguardando aprovação
            'personal_team' => false,
        ]);

        $teamPath = "stores/store_{$store->id}";

        // Processar upload de logo se houver
        if (request()->hasFile('logo')) {
            $logoFile = request()->file('logo');
            $logoPath = $logoFile->store($teamPath . '/logo', 'public');

            // Atualizar store com o logo
            $store->update([
                'logo_path' => $logoPath,
            ]);
        }

        // Processar upload de fotos se houver
        if (request()->hasFile('photos')) {
            foreach (request()->file('photos') as $index => $photo) {
                $photoPath = $photo->store($teamPath . '/photos', 'public');

                // Criar registro de mídia
                Media::create([
                    'team_id' => $store->id,
                    'name' => $photo->getClientOriginalName(),
                    'path' => $photoPath,
                    'type' => 'image',
                    'size' => $photo->getSize(),
                ]);
            }
        }

        // Marcar onboarding como completo
        $user->update([
            'onboarding_completed' => true,
            'onboarding_data' => array_merge($user->onboarding_data ?? [], [
                'store_id' => $store->id,
            ]),
        ]);
    }
}
