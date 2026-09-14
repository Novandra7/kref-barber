<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'method',
        'provider',
        'purpose',
        'status',
        'payment_source',
        'partner_reference_no',
        'doku_reference_no',
        'payment_url',
        'qr_content',
        'expires_at',
        'provider_payload',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relasi Models
    |--------------------------------------------------------------------------
    */

    /**
     * Satu Payment bisa memiliki banyak Booking
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
