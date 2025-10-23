<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoStoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário demo se não existir
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@tanavitrine.com.br'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
            ]
        );

        // Buscar categorias
        $roupasCategory = Category::where('slug', 'roupas')->first();
        $calcadosCategory = Category::where('slug', 'calcados')->first();
        $acessoriosCategory = Category::where('slug', 'acessorios')->first();
        $joiasCategory = Category::where('slug', 'joias')->first();

        $stores = [
            // Atacado - Featured
            [
                'name' => 'Moda Bella Atacado',
                'description' => 'Atacado de moda feminina com as melhores tendências do mercado. Peças de alta qualidade e preços competitivos. Trabalhamos com as principais marcas nacionais e importadas.',
                'sale_type' => 'atacado',
                'store_type' => 'ambos',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Feminino',
                'gender' => 'feminino',
                'min_order' => '50 peças',
                'whatsapp' => '11999999999',
                'email' => 'contato@modabella.com.br',
                'website' => 'https://modabella.com.br',
                'instagram' => '@modabellaofi',
                'city' => 'São Paulo',
                'state' => 'SP',
                'featured' => true,
                'status' => 'ativo',
            ],
            [
                'name' => 'Acessórios Premium Atacado',
                'description' => 'Distribuidora de acessórios fashion: bolsas, cintos, bijuterias e muito mais. Qualidade garantida e preços que cabem no seu bolso.',
                'sale_type' => 'atacado',
                'store_type' => 'fisica',
                'category_id' => $acessoriosCategory?->id,
                'subcategory' => 'Bolsas',
                'gender' => 'feminino',
                'min_order' => '30 peças',
                'whatsapp' => '31977777777',
                'email' => 'vendas@acessoriospremium.com.br',
                'city' => 'Belo Horizonte',
                'state' => 'MG',
                'featured' => true,
                'status' => 'ativo',
            ],
            [
                'name' => 'Beach Style Atacado',
                'description' => 'Maior variedade de moda praia no atacado. Biquínis, sungas, saídas de praia e muito mais. Coleções exclusivas para o verão.',
                'sale_type' => 'atacado',
                'store_type' => 'virtual',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Moda Praia',
                'gender' => 'unissex',
                'min_order' => '25 peças',
                'whatsapp' => '71988885555',
                'email' => 'contato@beachstyle.com.br',
                'website' => 'https://beachstyle.com.br',
                'city' => 'Salvador',
                'state' => 'BA',
                'featured' => true,
                'status' => 'ativo',
            ],

            // Atacado - Não Featured
            [
                'name' => 'Fashion Plus Atacado',
                'description' => 'Especialistas em moda plus size para atacado. Coleções exclusivas e variadas para todos os estilos. Do casual ao social.',
                'sale_type' => 'atacado',
                'store_type' => 'ambos',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Plus Size',
                'gender' => 'feminino',
                'min_order' => '30 peças',
                'whatsapp' => '41988887777',
                'email' => 'vendas@fashionplus.com.br',
                'city' => 'Curitiba',
                'state' => 'PR',
                'featured' => false,
                'status' => 'ativo',
            ],
            [
                'name' => 'Kids Fashion Atacado',
                'description' => 'Atacado de moda infantil com as marcas mais queridas. Qualidade e conforto para os pequenos. Roupas de 0 a 14 anos.',
                'sale_type' => 'atacado',
                'store_type' => 'fisica',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Infantil',
                'gender' => 'unissex',
                'min_order' => '40 peças',
                'whatsapp' => '85988886666',
                'email' => 'contato@kidsfashion.com.br',
                'city' => 'Fortaleza',
                'state' => 'CE',
                'featured' => false,
                'status' => 'ativo',
            ],
            [
                'name' => 'Calçados Brasil Atacado',
                'description' => 'Distribuidora de calçados com as melhores marcas do mercado. Tênis, sandálias, sapatos sociais e muito mais.',
                'sale_type' => 'atacado',
                'store_type' => 'ambos',
                'category_id' => $calcadosCategory?->id,
                'subcategory' => 'Feminino',
                'gender' => 'feminino',
                'min_order' => '20 pares',
                'whatsapp' => '48988884444',
                'email' => 'vendas@calcadosbrasil.com.br',
                'city' => 'Florianópolis',
                'state' => 'SC',
                'featured' => false,
                'status' => 'ativo',
            ],

            // Varejo - Featured
            [
                'name' => 'Shoes Express',
                'description' => 'Loja especializada em calçados esportivos e casuais. Variedade de marcas e modelos para todos os estilos. Entrega rápida.',
                'sale_type' => 'varejo',
                'store_type' => 'ambos',
                'category_id' => $calcadosCategory?->id,
                'subcategory' => 'Esportivo',
                'gender' => 'unissex',
                'min_order' => 'Sem pedido mínimo',
                'whatsapp' => '21988888888',
                'email' => 'contato@shoesexpress.com.br',
                'website' => 'https://shoesexpress.com.br',
                'instagram' => '@shoesexpress',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'featured' => true,
                'status' => 'ativo',
            ],
            [
                'name' => 'Luxe Accessories',
                'description' => 'Acessórios de luxo para completar seu look. Bolsas, relógios, óculos e muito mais. Produtos importados e nacionais.',
                'sale_type' => 'varejo',
                'store_type' => 'virtual',
                'category_id' => $acessoriosCategory?->id,
                'subcategory' => 'Bolsas',
                'gender' => 'feminino',
                'min_order' => 'Sem pedido mínimo',
                'whatsapp' => '48988887777',
                'email' => 'contato@luxeaccessories.com.br',
                'website' => 'https://luxeaccessories.com.br',
                'city' => 'Florianópolis',
                'state' => 'SC',
                'featured' => true,
                'status' => 'ativo',
            ],
            [
                'name' => 'Jóias Douradas',
                'description' => 'Joalheria especializada em peças exclusivas. Jóias e semijoias de alta qualidade. Atendimento personalizado.',
                'sale_type' => 'varejo',
                'store_type' => 'fisica',
                'category_id' => $joiasCategory?->id,
                'subcategory' => 'Anéis',
                'gender' => 'unissex',
                'min_order' => 'Sem pedido mínimo',
                'whatsapp' => '11988886666',
                'email' => 'contato@joiasdouradas.com.br',
                'city' => 'São Paulo',
                'state' => 'SP',
                'featured' => true,
                'status' => 'ativo',
            ],

            // Varejo - Não Featured
            [
                'name' => 'Style Boutique',
                'description' => 'Boutique de moda feminina com peças exclusivas e tendências da estação. Atendimento personalizado e consultoria de estilo.',
                'sale_type' => 'varejo',
                'store_type' => 'fisica',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Feminino',
                'gender' => 'feminino',
                'min_order' => 'Sem pedido mínimo',
                'whatsapp' => '11987654321',
                'email' => 'contato@styleboutique.com.br',
                'instagram' => '@styleboutique',
                'city' => 'São Paulo',
                'state' => 'SP',
                'featured' => false,
                'status' => 'ativo',
            ],
            [
                'name' => 'Urban Streetwear',
                'description' => 'Streetwear urbano com as marcas mais desejadas. Estilo e atitude para seu dia a dia. Lançamentos semanais.',
                'sale_type' => 'varejo',
                'store_type' => 'ambos',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Masculino',
                'gender' => 'masculino',
                'min_order' => 'Sem pedido mínimo',
                'whatsapp' => '51988886666',
                'email' => 'contato@urbanstreetwear.com.br',
                'website' => 'https://urbanstreetwear.com.br',
                'city' => 'Porto Alegre',
                'state' => 'RS',
                'featured' => false,
                'status' => 'ativo',
            ],
            [
                'name' => 'Kids World',
                'description' => 'Moda infantil com as marcas favoritas das crianças. Conforto e diversão em cada peça. Coleções temáticas.',
                'sale_type' => 'varejo',
                'store_type' => 'fisica',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Infantil',
                'gender' => 'unissex',
                'min_order' => 'Sem pedido mínimo',
                'whatsapp' => '61988885555',
                'email' => 'contato@kidsworld.com.br',
                'city' => 'Brasília',
                'state' => 'DF',
                'featured' => false,
                'status' => 'ativo',
            ],

            // Ambos - Featured
            [
                'name' => 'Mega Moda Brasil',
                'description' => 'Atacado e varejo de moda feminina, masculina e infantil. Uma das maiores redes do Brasil com preços imbatíveis.',
                'sale_type' => 'ambos',
                'store_type' => 'ambos',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Feminino',
                'gender' => 'unissex',
                'min_order' => '20 peças para atacado',
                'whatsapp' => '11999998888',
                'email' => 'contato@megamodabrasil.com.br',
                'website' => 'https://megamodabrasil.com.br',
                'instagram' => '@megamodabrasil',
                'facebook' => 'megamodabrasil',
                'city' => 'São Paulo',
                'state' => 'SP',
                'featured' => true,
                'status' => 'ativo',
            ],

            // Ambos - Não Featured
            [
                'name' => 'Tendência Fashion',
                'description' => 'Loja completa de moda com atacado e varejo. Sempre acompanhando as últimas tendências do mercado fashion.',
                'sale_type' => 'ambos',
                'store_type' => 'virtual',
                'category_id' => $roupasCategory?->id,
                'subcategory' => 'Feminino',
                'gender' => 'feminino',
                'min_order' => '15 peças para atacado',
                'whatsapp' => '21988887777',
                'email' => 'vendas@tendenciafashion.com.br',
                'website' => 'https://tendenciafashion.com.br',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'featured' => false,
                'status' => 'ativo',
            ],
        ];

        foreach ($stores as $storeData) {
            $storeData['user_id'] = $demoUser->id;
            $storeData['personal_team'] = false;

            // Generate slug
            $slug = Str::slug($storeData['name']);
            $count = 1;
            while (Team::where('slug', $slug)->exists()) {
                $slug = Str::slug($storeData['name']) . '-' . $count;
                $count++;
            }
            $storeData['slug'] = $slug;

            Team::create($storeData);
        }

        $this->command->info('Created ' . count($stores) . ' demo stores successfully!');
    }
}
