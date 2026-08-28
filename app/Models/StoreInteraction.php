<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StoreInteraction extends Model
{
    public const TYPE_WHATSAPP = 'whatsapp';

    public const TYPE_WEBSITE = 'website';

    public const TYPE_PHONE = 'phone';

    public const TYPE_MAP = 'map';

    public const TYPE_INSTAGRAM = 'instagram';

    public const TYPE_FACEBOOK = 'facebook';

    public const TYPE_TIKTOK = 'tiktok';

    public $timestamps = false;

    protected $fillable = [
        'team_id',
        'type',
        'occurred_at',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
