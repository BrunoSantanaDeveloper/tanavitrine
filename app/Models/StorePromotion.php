<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

final class StorePromotion extends Model
{
    protected $attributes = ['is_active' => true];

    protected $fillable = ['team_id', 'position', 'starts_at', 'ends_at', 'is_active', 'notes'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'Cancelado';
        }

        if ($this->ends_at->lessThanOrEqualTo(now())) {
            return 'Encerrado';
        }

        return $this->starts_at->isFuture() ? 'Agendado' : 'Em exibição';
    }

    protected static function booted(): void
    {
        static::creating(function (self $promotion): void {
            $promotion->created_by = auth()->id();
        });

        static::saving(function (self $promotion): void {
            if (! $promotion->starts_at || ! $promotion->ends_at || $promotion->ends_at->lessThanOrEqualTo($promotion->starts_at)) {
                throw ValidationException::withMessages(['ends_at' => 'O término deve ser posterior ao início.']);
            }

            if (! in_array($promotion->position, [1, 2, 3], true)) {
                throw ValidationException::withMessages(['position' => 'Escolha uma posição de 1 a 3.']);
            }

            if (! $promotion->is_active) {
                return;
            }

            if (! Team::active()->where('personal_team', false)->whereKey($promotion->team_id)->exists()) {
                throw ValidationException::withMessages(['team_id' => 'Selecione uma loja disponível publicamente.']);
            }

            $overlapping = self::query()
                ->where('is_active', true)
                ->when($promotion->exists, fn (Builder $query) => $query->where('id', '!=', $promotion->id))
                ->where('starts_at', '<', $promotion->ends_at)
                ->where('ends_at', '>', $promotion->starts_at);

            if ((clone $overlapping)->where('position', $promotion->position)->exists()) {
                throw ValidationException::withMessages(['position' => 'Esta posição já está reservada nesse período. Escolha outra posição ou ajuste as datas.']);
            }

            if ($overlapping->where('team_id', $promotion->team_id)->exists()) {
                throw ValidationException::withMessages(['team_id' => 'Esta loja já tem um destaque reservado nesse período.']);
            }
        });
    }
}
