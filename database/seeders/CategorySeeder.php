<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Roupas',
                'slug' => 'roupas',
                'icon' => 'lucide:shirt',
                'description' => 'Vestuário em geral',
                'sort_order' => 1,
                'subcategories' => [
                    'Feminino',
                    'Masculino',
                    'Infantil',
                    'Plus Size',
                    'Moda Praia',
                    'Lingerie',
                    'Esportivo',
                ],
            ],
            [
                'name' => 'Calçados',
                'slug' => 'calcados',
                'icon' => 'lucide:footprints',
                'description' => 'Sapatos, tênis, sandálias e mais',
                'sort_order' => 2,
                'subcategories' => [
                    'Feminino',
                    'Masculino',
                    'Infantil',
                    'Esportivo',
                    'Social',
                ],
            ],
            [
                'name' => 'Acessórios',
                'slug' => 'acessorios',
                'icon' => 'lucide:glasses',
                'description' => 'Bolsas, cintos, bijuterias e mais',
                'sort_order' => 3,
                'subcategories' => [
                    'Bolsas',
                    'Cintos',
                    'Bijuterias',
                    'Relógios',
                    'Óculos',
                    'Carteiras',
                    'Mochilas',
                ],
            ],
            [
                'name' => 'Jóias',
                'slug' => 'joias',
                'icon' => 'lucide:gem',
                'description' => 'Jóias e semijoias',
                'sort_order' => 4,
                'subcategories' => [
                    'Anéis',
                    'Colares',
                    'Brincos',
                    'Pulseiras',
                    'Alianças',
                ],
            ],
            [
                'name' => 'Perfumaria',
                'slug' => 'perfumaria',
                'icon' => 'lucide:spray-can',
                'description' => 'Perfumes e cosméticos',
                'sort_order' => 5,
                'subcategories' => [
                    'Perfumes',
                    'Cosméticos',
                    'Cuidados Pessoais',
                ],
            ],
            [
                'name' => 'Moda Casa',
                'slug' => 'moda-casa',
                'icon' => 'lucide:home',
                'description' => 'Decoração e utilidades',
                'sort_order' => 6,
                'subcategories' => [
                    'Cama',
                    'Mesa',
                    'Banho',
                    'Decoração',
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $subcategories = $categoryData['subcategories'] ?? [];
            unset($categoryData['subcategories']);

            $category = Category::create([
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'icon' => $categoryData['icon'],
                'description' => $categoryData['description'],
                'sort_order' => $categoryData['sort_order'],
                'is_active' => true,
            ]);

            // Create subcategories
            foreach ($subcategories as $index => $subName) {
                Category::create([
                    'name' => $subName,
                    'slug' => Str::slug($category->slug . '-' . $subName),
                    'parent_id' => $category->id,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
            }
        }
    }
}
