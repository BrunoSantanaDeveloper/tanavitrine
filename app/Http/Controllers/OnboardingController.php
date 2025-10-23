<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use App\Models\PlanInterval;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class OnboardingController extends Controller
{
    /**
     * Iniciar o fluxo de onboarding multi-step
     */
    public function start(Request $request): Response|RedirectResponse
    {
        // Se não tiver plano na URL, redireciona para home
        if (!$request->has('plan')) {
            return redirect()->route('home')->with('error', 'Selecione um plano para continuar');
        }

        $planIntervalId = $request->input('plan');
        $planInterval = PlanInterval::with(['plan', 'interval'])->find($planIntervalId);

        // Validar se o plano existe
        if (!$planInterval) {
            return redirect()->route('home')->with('error', 'Plano inválido');
        }

        // Buscar todos os planos disponíveis para permitir troca
        $allPlans = PlanInterval::with(['plan', 'interval'])
            ->whereHas('plan', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->map(function ($pi) {
                return [
                    'id' => $pi->id,
                    'name' => $pi->plan->name,
                    'description' => $pi->plan->description,
                    'price' => $pi->price,
                    'interval_name' => $pi->interval->name,
                    'features' => $pi->plan->features,
                ];
            });

        // Buscar categorias com subcategorias
        $categories = Category::active()
            ->parents()
            ->with('children')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                        ];
                    }),
                ];
            });

        // Renderizar página de onboarding multi-step
        return Inertia::render('Onboarding/MultiStepOnboarding', [
            'plan' => [
                'id' => $planInterval->id,
                'name' => $planInterval->plan->name,
                'description' => $planInterval->plan->description,
                'price' => $planInterval->price,
                'interval_name' => $planInterval->interval->name,
                'features' => $planInterval->plan->features,
            ],
            'availablePlans' => $allPlans,
            'categories' => $categories,
        ]);
    }

    public function show()
    {
        // Buscar categorias com subcategorias
        $categories = Category::active()
            ->parents()
            ->with('children')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                        ];
                    }),
                ];
            });

        return inertia('OnBoarding', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Como conheceu
            'referralSource' => ['required', 'string'],

            // Dados da loja
            'sale_type' => ['required', 'in:atacado,varejo,ambos'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50'],
            'store_type' => ['required', 'in:fisica,virtual,ambos'],
            'min_order' => ['nullable', 'string', 'max:255'],

            // Logo e Fotos
            'logo' => ['required', 'image', 'max:2048'], // 2MB max
            'photos' => ['required', 'array', 'min:3', 'max:10'],
            'photos.*' => ['image', 'max:5120'], // 5MB max per photo

            // Localização e Contato
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($validated, $user, $request) {
            // Gerar slug único para a loja
            $slug = Str::slug($validated['name']);
            $count = 1;
            while (Team::where('slug', $slug)->exists()) {
                $slug = Str::slug($validated['name']) . '-' . $count;
                $count++;
            }

            // Criar a vitrine do usuário
            $store = Team::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'slug' => $slug,
                'sale_type' => $validated['sale_type'],
                'category_id' => $validated['category_id'],
                'subcategory' => $validated['subcategory'],
                'gender' => $validated['gender'],
                'description' => $validated['description'],
                'min_order' => $validated['min_order'],
                'store_type' => $validated['store_type'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'whatsapp' => $validated['whatsapp'],
                'email' => $validated['email'],
                'status' => 'ativo', // Ativo quando plano é contratado
                'personal_team' => false,
            ]);

            // Salvar logo
            if ($request->hasFile('logo')) {
                $logoFile = $request->file('logo');
                $logoPath = $logoFile->store('stores/' . $store->id . '/logo', 'public');

                $logoMedia = Media::create([
                    'team_id' => $store->id,
                    'name' => 'Logo - ' . $store->name,
                    'path' => $logoPath,
                    'type' => 'image',
                    'size' => $logoFile->getSize() / 1024, // KB
                    'is_generic' => false,
                    'category' => 'logo',
                ]);

                // Attach logo to store
                $store->photos()->attach($logoMedia->id, [
                    'order' => 0,
                    'is_primary' => false,
                ]);
            }

            // Salvar fotos dos produtos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $photoFile) {
                    $photoPath = $photoFile->store('stores/' . $store->id . '/photos', 'public');

                    $photoMedia = Media::create([
                        'team_id' => $store->id,
                        'name' => 'Produto ' . ($index + 1) . ' - ' . $store->name,
                        'path' => $photoPath,
                        'type' => 'image',
                        'size' => $photoFile->getSize() / 1024, // KB
                        'is_generic' => false,
                        'category' => 'product',
                    ]);

                    // Attach photo to store (first photo is primary)
                    $store->photos()->attach($photoMedia->id, [
                        'order' => $index + 1,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            // Marcar onboarding como completo
            $user->update([
                'onboarding_completed' => true,
                'onboarding_data' => [
                    'referral_source' => $validated['referralSource'],
                    'store_id' => $store->id,
                ],
            ]);

            return redirect()->route('dashboard')->with('success', 'Vitrine criada e ativada com sucesso!');
        });
    }
}
