<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSlotRuleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_slot_rule_id',
        'service_id',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'service_slot_rule_id' => 'integer',
            'service_id' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(ServiceSlotRule::class, 'service_slot_rule_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->service) {
            return $this->service->name;
        }

        if ($this->category) {
            return 'Kategori: ' . ucfirst($this->category);
        }

        return '-';
    }

    public function getTargetKeyAttribute(): string
    {
        if ($this->service_id !== null) {
            return 'service:' . $this->service_id;
        }

        if ($this->category !== null && $this->category !== '') {
            return 'category:' . $this->category;
        }

        return '';
    }
}
