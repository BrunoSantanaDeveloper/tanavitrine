<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Services\SubscriptionAccessRuleService;

final class SubscriptionRules extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Regras de Assinatura';

    protected static ?string $title = 'Regras de Assinatura';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.subscription-rules';

    public ?array $data = [];

    public function mount(SubscriptionAccessRuleService $rules): void
    {
        $this->form->fill($rules->getRules());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Regras Gerais')
                    ->description('Defina como a assinatura funciona antes da integração com Stripe.')
                    ->schema([
                        Forms\Components\TextInput::make('default_trial_days')
                            ->label('Dias de teste padrão')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText('Quantidade de dias grátis para novos cadastros online.'),
                        Forms\Components\Toggle::make('hide_store_when_expired')
                            ->label('Tirar vitrine do ar ao expirar')
                            ->helperText('Quando expirar, a vitrine não aparece mais no site público.')
                            ->default(true),
                        Forms\Components\Toggle::make('keep_panel_access_when_expired')
                            ->label('Manter acesso ao painel após expirar')
                            ->helperText('O lojista continua acessando o painel mesmo com vitrine fora do ar.')
                            ->default(true),
                        Forms\Components\Toggle::make('allow_plan_new_user_discounts')
                            ->label('Permitir descontos automáticos do plano (legado)')
                            ->helperText('Desative para usar apenas teste padrão + cupom/admin.')
                            ->default(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setValueByKey(
            SubscriptionAccessRuleService::KEY_DEFAULT_TRIAL_DAYS,
            max(0, (int) ($state['default_trial_days'] ?? 14)),
            'subscription',
            'number'
        );
        Setting::setValueByKey(
            SubscriptionAccessRuleService::KEY_HIDE_STORE_WHEN_EXPIRED,
            (bool) ($state['hide_store_when_expired'] ?? true),
            'subscription',
            'boolean'
        );
        Setting::setValueByKey(
            SubscriptionAccessRuleService::KEY_KEEP_PANEL_ACCESS_WHEN_EXPIRED,
            (bool) ($state['keep_panel_access_when_expired'] ?? true),
            'subscription',
            'boolean'
        );
        Setting::setValueByKey(
            SubscriptionAccessRuleService::KEY_ALLOW_PLAN_NEW_USER_DISCOUNTS,
            (bool) ($state['allow_plan_new_user_discounts'] ?? false),
            'subscription',
            'boolean'
        );

        Notification::make()
            ->title('Regras de assinatura atualizadas')
            ->success()
            ->send();
    }
}
