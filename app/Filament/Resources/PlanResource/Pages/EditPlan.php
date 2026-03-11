<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Services\PlanService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
    private const ALLOWED_ANALYTICS_METRICS = [
        'views',
        'whatsapp_clicks',
        'website_clicks',
        'map_clicks',
        'shares',
        'instagram_clicks',
        'facebook_clicks',
        'tiktok_clicks',
    ];

    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            \Log::info('EditPlan Data before update: ' . json_encode($data));

            // Atualizar o plano
            $plan = app(PlanService::class)->updatePlan($this->record, $data);

            \Log::info('Plan updated successfully: ' . $plan->id);

            // Retornar os dados do plano atualizado
            return $plan->toArray();
        } catch (\Exception $e) {
            \Log::error('Error updating plan: ' . $e->getMessage());
            session()->flash('error', $e->getMessage());
            return $data;
        }
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Retornar o plano já atualizado
        return $record->fresh();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $plan = $this->record;
        $data['intervals'] = $plan?->intervals_for_form ?? [];
        $data['store_limit_photos_per_vitrine'] = $this->getStoreLimitForForm($plan, 'photos_per_vitrine', 3);
        $data['store_limit_collections_per_vitrine'] = $this->getStoreLimitForForm($plan, 'collections_per_vitrine', -1);
        $data['store_limit_photos_per_collection'] = $this->getStoreLimitForForm($plan, 'photos_per_collection', -1);
        $data['store_limit_videos_per_collection'] = $this->getStoreLimitForForm($plan, 'videos_per_collection', 0);

        // Load analytics metrics from features array
        $features = $plan->features ?? [];
        if (is_array($features) && isset($features['analytics']) && is_array($features['analytics'])) {
            $data['analytics_metrics'] = array_values(array_intersect(
                self::ALLOWED_ANALYTICS_METRICS,
                $features['analytics']
            ));
        } else {
            $data['analytics_metrics'] = [];
        }

        return $data;
    }

    private function getStoreLimitForForm($plan, string $resource, int $default): int
    {
        if (!$plan) {
            return $default;
        }

        return $plan->limits()
            ->where('module', 'store')
            ->where('resource', $resource)
            ->value('limit_value') ?? $default;
    }
}
