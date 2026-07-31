<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Configurações Gerais
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => env('APP_NAME', 'LaraSonic'),
                'type' => 'string',
                'is_public' => true,
                'description' => 'Nome do site',
                'sort_order' => 1,
            ],
            [
                'group' => 'general',
                'key' => 'site_description',
                'value' => 'Your SaaS Platform',
                'type' => 'text',
                'is_public' => true,
                'description' => 'Descrição do site',
                'sort_order' => 2,
            ],
            [
                'group' => 'general',
                'key' => 'default_timezone',
                'value' => env('APP_TIMEZONE', 'UTC'),
                'type' => 'select',
                'options' => timezone_identifiers_list(),
                'is_public' => true,
                'description' => 'Fuso horário padrão',
                'sort_order' => 3,
            ],
            [
                'group' => 'general',
                'key' => 'default_language',
                'value' => env('APP_LOCALE', 'pt_BR'),
                'type' => 'select',
                'options' => ['pt_BR' => 'Português', 'en' => 'English'],
                'is_public' => true,
                'description' => 'Idioma padrão',
                'sort_order' => 4,
            ],
            [
                'group' => 'general',
                'key' => 'maintenance_mode',
                'value' => false,
                'type' => 'boolean',
                'is_public' => true,
                'description' => 'Modo de manutenção',
                'sort_order' => 5,
            ],
            [
                'group' => 'general',
                'key' => 'enable_registration',
                'value' => true,
                'type' => 'boolean',
                'is_public' => true,
                'description' => 'Permitir novos registros',
                'sort_order' => 6,
            ],

            // Recursos Globais
            [
                'group' => 'features',
                'key' => 'enable_teams',
                'value' => true,
                'type' => 'boolean',
                'is_public' => true,
                'description' => 'Habilitar times',
                'sort_order' => 1,
            ],
            [
                'group' => 'features',
                'key' => 'enable_api',
                'value' => true,
                'type' => 'boolean',
                'is_public' => true,
                'description' => 'Habilitar API',
                'sort_order' => 2,
            ],
            [
                'group' => 'features',
                'key' => 'enable_chat',
                'value' => true,
                'type' => 'boolean',
                'is_public' => true,
                'description' => 'Habilitar chat',
                'sort_order' => 3,
            ],
            [
                'group' => 'features',
                'key' => 'enable_notifications',
                'value' => true,
                'type' => 'boolean',
                'is_public' => true,
                'description' => 'Habilitar notificações',
                'sort_order' => 4,
            ],

            // Email
            [
                'group' => 'mail',
                'key' => 'mail_mailer',
                'value' => env('MAIL_MAILER', 'smtp'),
                'type' => 'select',
                'options' => ['smtp', 'ses', 'mailgun', 'postmark'],
                'is_public' => false,
                'description' => 'Provedor de Email',
                'sort_order' => 1,
            ],
            [
                'group' => 'mail',
                'key' => 'mail_host',
                'value' => env('MAIL_HOST'),
                'type' => 'string',
                'is_public' => false,
                'description' => 'Host SMTP',
            ],
            [
                'group' => 'mail',
                'key' => 'mail_port',
                'value' => env('MAIL_PORT'),
                'type' => 'number',
                'is_public' => false,
                'description' => 'Porta SMTP',
            ],
            [
                'group' => 'mail',
                'key' => 'mail_encryption',
                'value' => env('MAIL_ENCRYPTION', 'tls'),
                'type' => 'select',
                'options' => ['tls', 'ssl', null],
                'is_public' => false,
                'description' => 'Criptografia SMTP',
            ],

            // Gateway de Pagamento (único)
            [
                'group' => 'payment',
                'key' => 'active_gateway',
                'value' => 'stripe',
                'type' => 'select',
                'options' => ['stripe', 'paypal'],
                'is_public' => false,
                'description' => 'Gateway de Pagamento Ativo',
            ],

            // Regras de Assinatura (fase sem Stripe)
            [
                'group' => 'subscription',
                'key' => 'subscription_default_trial_days',
                'value' => 14,
                'type' => 'number',
                'is_public' => false,
                'description' => 'Dias de teste padrão para novos cadastros',
                'sort_order' => 1,
            ],
            [
                'group' => 'subscription',
                'key' => 'subscription_hide_store_when_expired',
                'value' => true,
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Retirar vitrine do ar quando assinatura expirar',
                'sort_order' => 2,
            ],
            [
                'group' => 'subscription',
                'key' => 'subscription_keep_panel_access_when_expired',
                'value' => true,
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Permitir acesso ao painel quando assinatura expirar',
                'sort_order' => 3,
            ],
            [
                'group' => 'subscription',
                'key' => 'subscription_allow_plan_new_user_discounts',
                'value' => false,
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Permitir regras automáticas de desconto por plano (legado)',
                'sort_order' => 4,
            ],

            // Provedor de IA (único)
            [
                'group' => 'ai',
                'key' => 'active_provider',
                'value' => env('PRISM_PROVIDER', 'openai'),
                'type' => 'select',
                'options' => ['openai', 'anthropic', 'ollama'],
                'is_public' => false,
                'description' => 'Provedor de IA Ativo',
            ],

            // APIs Externas (múltiplas)
            [
                'group' => 'apis',
                'key' => 'github_enabled',
                'value' => true,
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Habilitar GitHub',
            ],
            [
                'group' => 'apis',
                'key' => 'google_enabled',
                'value' => true,
                'type' => 'boolean',
                'is_public' => false,
                'description' => 'Habilitar Google',
            ],

            // Aparência
            [
                'group' => 'appearance',
                'key' => 'favicon',
                'value' => null,
                'type' => 'file',
                'is_public' => true,
                'description' => 'Favicon do site',
                'accept' => '.ico,.png',
            ],
            [
                'group' => 'appearance',
                'key' => 'logo_light',
                'value' => null,
                'type' => 'file',
                'is_public' => true,
                'description' => 'Logo (modo claro)',
                'accept' => '.svg,.png',
            ],
            [
                'group' => 'appearance',
                'key' => 'logo_dark',
                'value' => null,
                'type' => 'file',
                'is_public' => true,
                'description' => 'Logo (modo escuro)',
                'accept' => '.svg,.png',
            ],
            [
                'group' => 'appearance',
                'key' => 'primary_color',
                'value' => '#0EA5E9',
                'type' => 'color',
                'is_public' => true,
                'description' => 'Cor primária',
                'sort_order' => 1,
            ],
            [
                'group' => 'appearance',
                'key' => 'secondary_color',
                'value' => '#64748B',
                'type' => 'color',
                'is_public' => true,
                'description' => 'Cor secundária',
                'sort_order' => 2,
            ],
            [
                'group' => 'appearance',
                'key' => 'accent_color',
                'value' => '#F59E0B',
                'type' => 'color',
                'is_public' => true,
                'description' => 'Cor de destaque',
                'sort_order' => 3,
            ],
            [
                'group' => 'appearance',
                'key' => 'background_color_light',
                'value' => '#FFFFFF',
                'type' => 'color',
                'is_public' => true,
                'description' => 'Cor de fundo (modo claro)',
                'sort_order' => 4,
            ],
            [
                'group' => 'appearance',
                'key' => 'background_color_dark',
                'value' => '#1E293B',
                'type' => 'color',
                'is_public' => true,
                'description' => 'Cor de fundo (modo escuro)',
                'sort_order' => 5,
            ],
            // Configurações de APIs
            // OpenAI
            [
                'group' => 'ai',
                'key' => 'openai_api_key',
                'value' => env('OPENAI_API_KEY'),
                'type' => 'password',
                'is_public' => false,
                'description' => 'OpenAI API Key',
                'sort_order' => 2,
            ],
            [
                'group' => 'ai',
                'key' => 'openai_model',
                'value' => 'gpt-4',
                'type' => 'select',
                'options' => ['gpt-4', 'gpt-3.5-turbo'],
                'is_public' => false,
                'description' => 'Modelo OpenAI',
                'sort_order' => 3,
            ],
            // Anthropic
            [
                'group' => 'ai',
                'key' => 'anthropic_api_key',
                'value' => env('ANTHROPIC_API_KEY'),
                'type' => 'password',
                'is_public' => false,
                'description' => 'Anthropic API Key',
                'sort_order' => 4,
            ],
            [
                'group' => 'ai',
                'key' => 'anthropic_model',
                'value' => 'claude-3-opus',
                'type' => 'select',
                'options' => ['claude-3-opus', 'claude-3-sonnet', 'claude-3-haiku'],
                'is_public' => false,
                'description' => 'Modelo Anthropic',
                'sort_order' => 5,
            ],

            // Configurações de APIs Externas
            // GitHub
            [
                'group' => 'apis',
                'key' => 'github_client_id',
                'value' => env('GITHUB_CLIENT_ID'),
                'type' => 'password',
                'is_public' => false,
                'description' => 'GitHub Client ID',
                'sort_order' => 2,
            ],
            [
                'group' => 'apis',
                'key' => 'github_client_secret',
                'value' => env('GITHUB_CLIENT_SECRET'),
                'type' => 'password',
                'is_public' => false,
                'description' => 'GitHub Client Secret',
                'sort_order' => 3,
            ],
            // Google
            [
                'group' => 'apis',
                'key' => 'google_client_id',
                'value' => env('GOOGLE_CLIENT_ID'),
                'type' => 'password',
                'is_public' => false,
                'description' => 'Google Client ID',
                'sort_order' => 4,
            ],
            [
                'group' => 'apis',
                'key' => 'google_client_secret',
                'value' => env('GOOGLE_CLIENT_SECRET'),
                'type' => 'password',
                'is_public' => false,
                'description' => 'Google Client Secret',
                'sort_order' => 5,
            ],
            // Stripe
            [
                'group' => 'payment',
                'key' => 'stripe_key',
                'value' => null,
                'type' => 'password',
                'is_public' => false,
                'description' => 'Stripe Public Key (configurada via ambiente)',
                'sort_order' => 2,
            ],
            [
                'group' => 'payment',
                'key' => 'stripe_secret',
                'value' => null,
                'type' => 'password',
                'is_public' => false,
                'description' => 'Stripe Secret Key (configurada via ambiente)',
                'sort_order' => 3,
            ],
            [
                'group' => 'payment',
                'key' => 'stripe_webhook_secret',
                'value' => null,
                'type' => 'password',
                'is_public' => false,
                'description' => 'Stripe Webhook Secret (configurada via ambiente)',
                'sort_order' => 4,
            ],
        ];

        foreach ($settings as $setting) {
            // Converter options para JSON se existir
            if (isset($setting['options'])) {
                $setting['options'] = json_encode($setting['options']);
            }

            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
