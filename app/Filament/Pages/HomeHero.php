<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Services\HomeHeroSettings;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Concerns\InteractsWithForms;

final class HomeHero extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Hero da página inicial';

    protected static ?string $title = 'Hero da página inicial';

    protected static ?string $slug = 'hero-pagina-inicial';

    protected static string $view = 'filament.pages.home-hero';

    public static function canAccess(): bool
    {
        return auth()->user()?->is_superadmin === true;
    }

    public function mount(HomeHeroSettings $settings): void
    {
        abort_unless(self::canAccess(), 403);
        $this->form->fill($settings->get());
    }

    public function form(Form $form): Form
    {
        $colors = [
            'background_color' => 'Fundo',
            'title_color' => 'Título',
            'highlight_color' => 'Segunda linha do título',
            'description_color' => 'Descrição e legenda dos atalhos',
            'button_color' => 'Botão de busca',
            'button_text_color' => 'Texto do botão',
        ];

        return $form->schema([
            Forms\Components\Section::make('Textos')
                ->schema([
                    Forms\Components\TextInput::make('badge')->label('Selo')->maxLength(80),
                    Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(120),
                    Forms\Components\TextInput::make('highlight')->label('Segunda linha do título')->required()->maxLength(120),
                    Forms\Components\TextInput::make('button_label')->label('Texto do botão de busca')->required()->maxLength(30),
                    Forms\Components\Textarea::make('description')->label('Descrição')->required()->maxLength(500)->rows(3)->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Fundo e mídia')
                ->description('Escolha um fundo simples, uma foto ou um vídeo. As mídias preenchem a área e podem ser recortadas conforme a tela.')
                ->schema([
                    Forms\Components\Select::make('background_type')
                        ->label('Tipo de fundo')
                        ->options(['color' => 'Cor', 'image' => 'Foto', 'video' => 'Vídeo'])
                        ->in(['color', 'image', 'video'])->required()->live(),
                    Forms\Components\FileUpload::make('video_path')
                        ->label('Vídeo de fundo — até 50 MB')
                        ->visible(fn (Forms\Get $get): bool => $get('background_type') === 'video')
                        ->dehydratedWhenHidden()
                        ->disk('public')->directory('hero/videos')->visibility('public')
                        ->acceptedFileTypes(['video/mp4', 'video/webm'])->maxSize(51200)
                        ->required(fn (Forms\Get $get): bool => $get('background_type') === 'video')
                        ->helperText('MP4 ou WebM, sem som e em loop. Ao salvar, a imagem do início do vídeo é gerada automaticamente para aparecer durante o carregamento.'),
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Foto de fundo — até 5 MB')
                        ->visible(fn (Forms\Get $get): bool => $get('background_type') === 'image')
                        ->dehydratedWhenHidden()
                        ->disk('public')->directory('hero/images')->visibility('public')
                        ->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)->required(fn (Forms\Get $get): bool => $get('background_type') === 'image')
                        ->helperText('Imagem JPG, PNG ou WebP, até 5 MB.'),
                    Forms\Components\Toggle::make('overlay_enabled')
                        ->label('Ativar cor de sobreposição')
                        ->helperText('Ativa um degradê atrás dos textos para melhorar a leitura. Desative para mostrar a mídia sem sobreposição.')
                        ->default(true)->live()->columnSpanFull(),
                    Forms\Components\ColorPicker::make('overlay_color')
                        ->label('Cor da camada sobre a mídia')->hex()->required()->regex('/^#[0-9a-fA-F]{6}$/')
                        ->disabled(fn (Forms\Get $get): bool => ! $get('overlay_enabled'))->dehydrated(),
                    Forms\Components\TextInput::make('overlay_opacity')
                        ->label('Opacidade da camada (%)')->numeric()->integer()->minValue(0)->maxValue(100)->required()
                        ->disabled(fn (Forms\Get $get): bool => ! $get('overlay_enabled'))->dehydrated()
                        ->helperText('0 = transparente; 100 = intensidade máxima no centro do degradê. Aplicada apenas sobre foto ou vídeo.'),
                ])->columns(2),
            Forms\Components\Section::make('Cores')
                ->schema(array_map(
                    fn (string $key, string $label) => Forms\Components\ColorPicker::make($key)
                        ->label($label)->hex()->required()->regex('/^#[0-9a-fA-F]{6}$/'),
                    array_keys($colors),
                    array_values($colors),
                ))->columns(3),
        ])->statePath('data');
    }

    public function save(HomeHeroSettings $settings): void
    {
        abort_unless(self::canAccess(), 403);
        $settings->save($this->form->getState());

        Notification::make()->title('Hero atualizada com sucesso')->success()->send();
    }
}
