<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlanInterval;
use Illuminate\Console\Command;
use Stripe\StripeClient;

final class StripeConfigCheckCommand extends Command
{
    protected $signature = 'subscriptions:stripe-check
        {--ping : Faz chamada na API do Stripe para validar a chave secreta}
        {--validate-prices : Valida os Stripe Price IDs cadastrados nos planos pagos}';

    protected $description = 'Valida configuração Stripe/Cashier para teste e produção';

    public function handle(): int
    {
        $environment = app()->environment();
        $isProduction = app()->isProduction();

        $appUrl = (string) config('app.url');
        $stripeKey = (string) config('cashier.key');
        $stripeSecret = (string) config('cashier.secret');
        $webhookSecret = (string) config('cashier.webhook.secret');
        $webhookUrl = rtrim($appUrl, '/') . '/api/stripe/webhook';

        $this->info('Diagnóstico Stripe');
        $this->line('Ambiente: ' . $environment);
        $this->line('APP_URL: ' . ($appUrl !== '' ? $appUrl : '[vazio]'));
        $this->line('Webhook endpoint esperado: ' . $webhookUrl);
        $this->newLine();

        $this->line('STRIPE_KEY: ' . ($stripeKey !== '' ? 'OK' : 'AUSENTE'));
        $this->line('STRIPE_SECRET: ' . ($stripeSecret !== '' ? 'OK' : 'AUSENTE'));
        $this->line('STRIPE_WEBHOOK_SECRET: ' . ($webhookSecret !== '' ? 'OK' : 'AUSENTE'));

        $keyMode = $this->detectStripeKeyMode($stripeKey, 'pk_');
        $secretMode = $this->detectStripeKeyMode($stripeSecret, 'sk_');
        $mode = $keyMode === $secretMode ? $keyMode : 'mixed';
        $this->line('Modo das chaves detectado: ' . match ($mode) {
            'test' => 'TEST',
            'live' => 'LIVE',
            'empty' => 'NÃO CONFIGURADO',
            default => 'INVÁLIDO/MISTO',
        });

        $this->newLine();

        if ($stripeKey === '' || $stripeSecret === '') {
            $this->warn('Preencha STRIPE_KEY e STRIPE_SECRET no .env para habilitar checkout.');
            return self::FAILURE;
        }

        if ($mode === 'mixed' || $mode === 'unknown') {
            $this->error('As chaves Stripe estão inconsistentes. Use par correspondente: pk_test+sk_test ou pk_live+sk_live.');
            return self::FAILURE;
        }

        if ($isProduction) {
            if ($mode !== 'live') {
                $this->error('Produção exige chaves Stripe LIVE (pk_live_ e sk_live_).');
                return self::FAILURE;
            }

            if ($webhookSecret === '') {
                $this->error('Produção exige STRIPE_WEBHOOK_SECRET configurado.');
                return self::FAILURE;
            }

            $this->info('Validação de produção: OK (LIVE + webhook assinado).');
        } else {
            if ($mode === 'live') {
                $this->warn('Chaves LIVE detectadas fora de produção. Confirme se isso é intencional.');
            } else {
                $this->info('Chaves de teste detectadas.');
            }

            if ($webhookSecret === '') {
                $this->warn('STRIPE_WEBHOOK_SECRET ausente. Em produção ele será obrigatório.');
            }
        }

        $stripe = null;
        $mustPingStripe = (bool) $this->option('ping') || (bool) $this->option('validate-prices');
        if ($mustPingStripe) {
            try {
                $stripe = new StripeClient($stripeSecret);
                $account = $stripe->accounts->retrieve();
                $this->info('Conexão Stripe OK. Conta: ' . ($account->id ?? 'N/A'));
            } catch (\Throwable $e) {
                $this->error('Falha ao conectar na API Stripe: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        if ((bool) $this->option('validate-prices')) {
            if (!$stripe) {
                $this->error('Não foi possível validar prices sem conexão Stripe.');
                return self::FAILURE;
            }

            $pricesOk = $this->validateConfiguredPrices($stripe);
            if (!$pricesOk) {
                return self::FAILURE;
            }
        }

        $this->info(
            $isProduction
                ? 'Configuração Stripe pronta para produção.'
                : 'Configuração Stripe pronta para testes.'
        );

        return self::SUCCESS;
    }

    private function detectStripeKeyMode(string $key, string $expectedPrefix): string
    {
        if ($key === '') {
            return 'empty';
        }

        if (str_starts_with($key, $expectedPrefix . 'test_')) {
            return 'test';
        }

        if (str_starts_with($key, $expectedPrefix . 'live_')) {
            return 'live';
        }

        return 'unknown';
    }

    private function validateConfiguredPrices(StripeClient $stripe): bool
    {
        $intervals = PlanInterval::query()
            ->with(['plan', 'interval'])
            ->where('price', '>', 0)
            ->orderBy('plan_id')
            ->orderBy('interval_id')
            ->get();

        if ($intervals->isEmpty()) {
            $this->warn('Nenhum plano pago encontrado para validar price_id.');
            return true;
        }

        $hasError = false;
        $this->newLine();
        $this->info('Validando Stripe Price IDs dos planos pagos...');

        foreach ($intervals as $interval) {
            $planName = (string) ($interval->plan?->name ?? 'Plano');
            $intervalName = (string) ($interval->interval?->name ?? 'Intervalo');
            $priceId = trim((string) ($interval->stripe_price_id ?? ''));
            $label = sprintf('%s (%s)', $planName, $intervalName);

            if ($priceId === '') {
                $this->error("- {$label}: sem stripe_price_id.");
                $hasError = true;
                continue;
            }

            try {
                $stripePrice = $stripe->prices->retrieve($priceId, []);
                $isRecurring = (string) ($stripePrice->type ?? '') === 'recurring';
                $isActive = (bool) ($stripePrice->active ?? false);

                if (!$isRecurring) {
                    $this->error("- {$label}: {$priceId} não é recorrente no Stripe.");
                    $hasError = true;
                    continue;
                }

                if (!$isActive) {
                    $this->error("- {$label}: {$priceId} está inativo no Stripe.");
                    $hasError = true;
                    continue;
                }

                $this->line("- {$label}: OK ({$priceId})");
            } catch (\Throwable $e) {
                $this->error("- {$label}: erro ao validar {$priceId} ({$e->getMessage()}).");
                $hasError = true;
            }
        }

        if ($hasError) {
            $this->newLine();
            $this->error('Foram encontrados problemas nos price IDs. Ajuste antes de seguir para produção.');
            return false;
        }

        $this->newLine();
        $this->info('Todos os planos pagos possuem Stripe Price ID recorrente e ativo.');

        return true;
    }
}
