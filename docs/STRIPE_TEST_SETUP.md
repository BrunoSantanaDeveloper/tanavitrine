# Stripe Test Setup (Fake Checkout)

Este guia prepara o fluxo de assinatura para teste com Stripe em modo sandbox.

## 1) Preencher variáveis no `.env`

Defina as chaves de teste da sua conta Stripe:

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=brl
CASHIER_CURRENCY_LOCALE=pt_BR
```

## 2) Limpar cache de configuração

```bash
php artisan config:clear
php artisan cache:clear
```

## 3) Validar configuração local

Use o comando de diagnóstico:

```bash
php artisan subscriptions:stripe-check
```

Para validar conexão real com a API Stripe:

```bash
php artisan subscriptions:stripe-check --ping
```

Para validar também os `price_id` dos planos pagos:

```bash
php artisan subscriptions:stripe-check --validate-prices
```

## 4) Configurar webhook (recomendado)

Endpoint da aplicação:

```text
POST /api/stripe/webhook
```

Exemplo local:

```text
http://localhost:8010/api/stripe/webhook
```

Se usar Stripe CLI, faça o forward para esse endpoint e copie o `whsec_...` para `STRIPE_WEBHOOK_SECRET`.

Se não usar CLI, você pode cadastrar esse endpoint direto no dashboard Stripe (ambiente de teste).

## 5) Teste do checkout

1. Entrar no painel de assinatura (`/subscriptions/create`).
2. Clicar em assinar/upgrade.
3. No Stripe Checkout, usar cartão de teste `4242 4242 4242 4242`.
4. Confirmar retorno para `subscriptions.success`.
5. Conferir assinatura ativa no banco/painel.

## 6) Cenários esperados

- Sem cupom: redireciona para Stripe Checkout.
- Cupom 100% com duração: redireciona para Stripe Checkout com benefício aplicado.
- Cupom parcial: vai para Stripe com valor descontado.
- Cupom inválido: exibe erro de cupom inválido/expirado.

## 7) Observação de produção

Em produção, é obrigatório:

- usar chaves `pk_live_` e `sk_live_`
- configurar `STRIPE_WEBHOOK_SECRET`

O comando `subscriptions:stripe-check` valida essas regras automaticamente quando executado em produção.
