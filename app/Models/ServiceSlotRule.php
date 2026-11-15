<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceSlotRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'barber_id',
        'slots_required',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'barber_id' => 'integer',
            'slots_required' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceSlotRuleItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
