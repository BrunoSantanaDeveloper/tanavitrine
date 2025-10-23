<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Services\PlanService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlan extends EditRecord
{
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
        $data['limits'] = $plan?->limits_for_form ?? [];
        return $data;
    }
}
