<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use App\Models\Plan;
use App\Models\Media;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'store_type' => ['nullable', 'in:fisica,virtual,ambos'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory' => ['nullable', 'array'],
            'subcategory.*' => ['string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'google_maps_url' => ['nullable', 'url', 'max:500'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,webm,avi', 'max:102400'], // 100MB
        ])->validate();

        return DB::transaction(fn () => tap(User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Arr::get($input, 'password') ? Hash::make($input['password']) : Str::random(12),
        ]), function (User $user) use ($input): void {
            $team = $this->createTeam($user, $input);

            // Se tem dados da loja, atualizar a personal team (vitrine)
            if (isset($input['store_name']) && !empty($input['store_name'])) {
                // Marcar onboarding como completo ANTES de processar para evitar redirecionamento
                $user->update(['onboarding_completed' => true]);

                $this->updateTeamWithStoreData($user, $team, $input);
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

        // Use store_name if available, otherwise use user's first name
        $teamName = $input['store_name'] ?? explode(' ', $user->name, 2)[0]."'s Team";

        $team = $user->ownedTeams()->save(Team::query()->forceCreate([
            'user_id' => $user->id,
            'name' => $teamName,
            'personal_team' => true,
            'plan_id' => $planId,
        ]));

        // Set this team as the user's current team
        $user->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        // Store selected plan in session for checkout after registration
        if (!empty($input['plan'])) {
            session(['selected_plan' => $input['plan']]);
        }

        // Store coupon code in session if provided
        if (!empty($input['coupon_code'])) {
            session(['applied_coupon' => $input['coupon_code']]);
        }

        return $team;
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
     * Update personal team with store vitrine data (TanaVitrine)
     */
    private function updateTeamWithStoreData(User $user, Team $team, array $input): void
    {
        // Gerar slug único para a loja
        $slug = Str::slug($input['store_name']);
        $count = 1;
        while (Team::where('slug', $slug)->where('id', '!=', $team->id)->exists()) {
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

        // Processar subcategory se presente como string JSON
        $subcategory = $input['subcategory'] ?? null;
        if (is_string($subcategory)) {
            $subcategory = json_decode($subcategory, true);
        }

        // Atualizar a personal team com os dados da vitrine e converter para vitrine pública
        $team->update([
            'name' => $input['store_name'],
            'slug' => $slug,
            'personal_team' => false, // Converter para vitrine pública
            'sale_type' => $input['sale_type'] ?? 'varejo',
            'category_id' => $input['category_id'] ?? null,
            'subcategory' => $subcategory,
            'gender' => $input['gender'] ?? null,
            'description' => $input['description'] ?? '',
            'min_order' => $input['min_order'] ?? null,
            'store_type' => $input['store_type'] ?? 'virtual',
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'address' => $address['street'] ?? null,
            'address_number' => $address['number'] ?? null,
            'address_complement' => $address['complement'] ?? null,
            'zip_code' => $address['cep'] ?? null,
            'google_maps_url' => $input['google_maps_url'] ?? null,
            'instagram' => $socialMedia['instagram'] ?? null,
            'facebook' => $socialMedia['facebook'] ?? null,
            'tiktok' => $socialMedia['tiktok'] ?? null,
            'website' => $socialMedia['website'] ?? null,
            'latitude' => $input['latitude'] ?? null,
            'longitude' => $input['longitude'] ?? null,
            'whatsapp' => $input['whatsapp'] ?? null,
            'phone' => $input['phone'] ?? null,
            'email' => $user->email,
            'status' => 'ativo',
        ]);

        $teamPath = "stores/store_{$team->id}";

        // Processar upload de logo se houver
        if (request()->hasFile('logo')) {
            $logoFile = request()->file('logo');
            $logoPath = $logoFile->store($teamPath . '/logo', 'public');

            // Atualizar team com o logo
            $team->update([
                'logo_path' => $logoPath,
            ]);
        }

        // Processar upload de fotos se houver
        if (request()->hasFile('photos')) {
            foreach (request()->file('photos') as $index => $photo) {
                $photoPath = $photo->store($teamPath . '/photos', 'public');

                // Criar registro de mídia
                $media = Media::create([
                    'team_id' => $team->id,
                    'name' => $photo->getClientOriginalName(),
                    'path' => $photoPath,
                    'type' => 'image',
                    'size' => $photo->getSize() / 1024, // Converter para KB
                ]);

                // Vincular na tabela pivot team_media
                $team->photos()->attach($media->id, [
                    'order' => $index,
                    'is_primary' => $index === 0, // Primeira foto é a principal
                ]);
            }
        }

        // Processar vídeo (upload ou URL)
        if (request()->hasFile('video')) {
            $videoFile = request()->file('video');
            $videoPath = $videoFile->store($teamPath . '/videos', 'public');

            // Atualizar team com o caminho do vídeo
            $team->update([
                'video_url' => $videoPath,
            ]);
        } elseif (isset($input['video_url']) && !empty($input['video_url'])) {
            // Se não tem upload mas tem URL (YouTube/Vimeo), salvar a URL
            $team->update([
                'video_url' => $input['video_url'],
            ]);
        }
    }
}
