<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'from_status',
        'to_status',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeadActivityType::class,
            'from_status' => LeadStatus::class,
            'to_status' => LeadStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
