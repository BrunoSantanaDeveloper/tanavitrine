# TanaVitrine 🛍️

Marketplace online para lojistas de moda anunciarem seus produtos e conectarem-se com compradores atacadistas e varejistas.

## ✨ Funcionalidades

- 🏪 **Vitrines Personalizadas** - Crie sua vitrine digital com fotos e informações da loja
- 📸 **Galeria de Produtos** - Upload múltiplo de fotos conforme o plano (3-20 fotos)
- 📊 **Analytics Completo** - Acompanhe visualizações, cliques e leads capturados
- 🎯 **Segmentação** - Vitrines para Atacado, Varejo ou Ambos
- 📱 **WhatsApp Integrado** - Conexão direta com compradores via WhatsApp
- 🔍 **Busca Avançada** - Filtros por categoria, localização e tipo de venda
- ⭐ **Sistema de Favoritos** - Compradores salvam lojas de interesse
- 💎 **Vitrines em Destaque** - Maior visibilidade para lojistas Premium
- 🔐 **Autenticação Completa** - Login, registro e recuperação de senha
- 💳 **Planos de Assinatura** - Gratuito, Vitrine, Destaque e Premium

## 📦 Planos Disponíveis

| Plano | Fotos | Analytics | Destaque | Preço |
|-------|-------|-----------|----------|-------|
| **Gratuito** | 3 | ❌ | ❌ | R$ 0/mês |
| **Vitrine** | 10 | ✅ | ❌ | Sob consulta |
| **Destaque** | 20 | ✅ | ✅ | Sob consulta |
| **Premium** | Ilimitadas | ✅ | ✅ | Sob consulta |

## 🚀 Instalação

### Requisitos

- Docker & Docker Compose
- PHP 8.3+
- Composer
- Node.js 20+
- MySQL 8.0+

### Setup Rápido

```bash
# Clone o repositório
git clone <seu-repositorio>
cd tanavitrine

# Instale dependências do Composer via Docker
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# Configure ambiente
cp .env.example .env

# Inicie os containers
./vendor/bin/sail up -d

# Execute setup inicial
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link

# Instale dependências do Node
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Acesse: `http://localhost`

### Configuração do Stripe (Pagamentos)

1. Crie conta em [stripe.com](https://stripe.com)
2. Obtenha suas chaves API
3. Configure no `.env`:

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

## 🛠️ Stack Tecnológica

- **Backend**: Laravel 11
- **Frontend**: Vue 3 + Inertia.js
- **UI**: TailwindCSS + shadcn/ui
- **Database**: MySQL
- **Pagamentos**: Laravel Cashier + Stripe
- **Admin**: FilamentPHP
- **Containerização**: Docker + Laravel Sail
- **Ícones**: Iconify

## 📁 Estrutura do Projeto

```
tanavitrine/
├── app/
│   ├── Http/Controllers/
│   │   ├── WelcomeController.php      # Página inicial
│   │   ├── DashboardController.php    # Dashboard lojista
│   │   ├── DashboardStoreController.php # Gerenciamento vitrine
│   │   └── StoreController.php        # Página pública vitrine
│   ├── Models/
│   │   ├── Team.php                   # Modelo de vitrine/loja
│   │   ├── Plan.php                   # Planos de assinatura
│   │   ├── Media.php                  # Fotos dos produtos
│   │   └── StoreLead.php              # Leads capturados
│   └── Traits/
│       └── HasPlanLimits.php          # Gestão de limites por plano
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   │   ├── StoreCard.vue          # Card de vitrine
│   │   │   └── AppSidebarContent.vue  # Menu lateral
│   │   └── Pages/
│   │       ├── Welcome.vue            # Página inicial
│   │       ├── Dashboard/
│   │       │   ├── StoreEdit.vue      # Edição vitrine
│   │       │   ├── StorePhotos.vue    # Gestão de fotos
│   │       │   └── StoreAnalytics.vue # Analytics
│   │       └── Store/
│   │           └── Show.vue           # Vitrine pública
│   └── views/
│       ├── PrivacyPolicy.vue          # Política de privacidade
│       └── TermsOfService.vue         # Termos de serviço
├── database/
│   └── seeders/
│       └── DefaultPlansSeeder.php     # Criação de planos padrão
└── docker/
    └── docker-compose.yml
```

## 🔑 Principais Funcionalidades

### Para Lojistas

- **Onboarding Guiado**: Processo de criação de vitrine passo a passo
- **Editor de Vitrine**: Formulário completo com informações, localização e contato
- **Upload de Fotos**: Sistema de galeria com limite baseado no plano
- **Analytics Dashboard**: Métricas de visualizações, cliques e leads
- **Captura de Leads**: Registra interesse de compradores
- **Gestão de Planos**: Upgrade/downgrade de assinatura

### Para Compradores

- **Busca e Filtros**: Por categoria, localização e tipo de venda (atacado/varejo)
- **Visualização de Vitrines**: Galeria de fotos e informações completas
- **Favoritos**: Salvar lojas de interesse
- **Contato Direto**: WhatsApp, telefone, site e mapa
- **Compartilhamento**: Links sociais para vitrines

## 🔒 Segurança

- Autenticação Laravel Jetstream
- CSRF Protection
- XSS Protection
- SQL Injection Protection
- Rate Limiting
- SSL/TLS Ready

Para reportar vulnerabilidades: **seguranca@tanavitrine.com.br**

## 📄 Licença

[MIT](./LICENSE.md)

## 🤝 Contribuindo

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues e pull requests.

## 📞 Suporte

- **Email**: suporte@tanavitrine.com.br
- **WhatsApp**: [Clique aqui](https://wa.me/5562991729522?text=Preciso%20de%20ajuda%20com%20o%20TanaVitrine)

---

Desenvolvido com ❤️ para o mercado de moda brasileiro
