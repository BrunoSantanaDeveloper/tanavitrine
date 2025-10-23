<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Services\PlanService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            \Log::info('CreatePlan Data before creation: ' . json_encode($data));

            // Criar o plano
            $plan = app(PlanService::class)->createPlan($data);

            \Log::info('Plan created successfully: ' . $plan->id);

            // Retornar os dados do plano criado
            return $plan->toArray();
        } catch (\Exception $e) {
            \Log::error('Error creating plan: ' . $e->getMessage());
            session()->flash('error', $e->getMessage());
            return $data;
        }
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Retornar o plano já criado em vez de criar um novo
        return $this->getModel()::find($data['id']);
    }
}
