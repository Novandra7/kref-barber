<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'name',
        'phone',
        'description',
        'barber_id',
        'created_by',
        'source',
        'payment_type',
        'status',
        'payment_status',
        'total_amount',
        'outstanding_amount',
        'scheduled_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'total_amount' => 'integer',
            'outstanding_amount' => 'integer',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
    
    public function isPaidFull(): bool
    {
        return $this->payment_status === 'paid_full';
    }

    // Helper untuk mengecek apakah ada sisa tagihan
    public function hasOutstanding(): bool
    {
        return $this->outstanding_amount > 0;
    }

    // Scope untuk filter berdasarkan status pembayaran
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid_full');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('payment_status', ['unpaid', 'partial']);
    }
}
