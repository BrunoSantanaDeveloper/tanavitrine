# Stripe Live Setup (Produção)

Guia rápido para migrar de teste (fake) para Stripe real com segurança.

## 1) Criar preços no modo LIVE

No dashboard Stripe (modo live), crie os produtos/preços recorrentes dos planos.

Importante: IDs de teste não funcionam no modo live.

## 2) Atualizar Price IDs no sistema

No painel admin, edite os planos e preencha os `stripe_price_id` LIVE.

## 3) Configurar variáveis de ambiente (produção)

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=brl
CASHIER_CURRENCY_LOCALE=pt_BR
```

## 4) Cadastrar webhook no Stripe (modo LIVE)

Endpoint:

```text
https://SEU_DOMINIO/api/stripe/webhook
```

Eventos recomendados:

- `checkout.session.completed`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.paid`
- `invoice.payment_failed`

## 5) Limpar/cachear config no servidor

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

## 6) Validar antes de abrir para clientes

```bash
php artisan subscriptions:stripe-check --ping --validate-prices
```

Esperado:

- ambiente de produção com chaves LIVE
- `STRIPE_WEBHOOK_SECRET` configurado
- todos os `price_id` pagos válidos, recorrentes e ativos

## 7) Checklist final

- Checkout abre Stripe live
- Pagamento aprovado retorna para `/subscriptions/success`
- Webhook atualiza assinatura sem duplicar registros
- Upgrade/downgrade/cancelar/reativar funcionando no painel
