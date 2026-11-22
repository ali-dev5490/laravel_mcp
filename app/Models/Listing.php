<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ListingCategory;
use App\Enums\ListingStatus;
use App\Support\Money;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'created_by',
        'title',
        'slug',
        'summary',
        'description',
        'category',
        'price_cents',
        'currency',
        'status',
        'location',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => ListingCategory::class,
            'status' => ListingStatus::class,
            'price_cents' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function price(): string
    {
        return Money::format($this->price_cents);
    }
}
