<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'barber_id',
        'date',
        'slot_time',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'slot_time' => 'datetime:H:i',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }
}