<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'requested_schedule_id',
        'payment_id',
        'name',
        'phone',
        'description',
        'barber_id',
        'created_by',
        'source',
        'payment_type',
        'status',
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

    /*
    |--------------------------------------------------------------------------
    | Relasi Models
    |--------------------------------------------------------------------------
    */

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function requestedSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'requested_schedule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * Booking belongs to satu Payment (FK payment_id ada di tabel bookings)
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Satu booking hanya punya satu refund record
     */
    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Accessor dinamis untuk mendapatkan status transaksi dari relasi Payment
     * Memungkinkan pemanggilan $booking->payment_status di Blade
     */
    public function getPaymentStatusAttribute(): ?string
    {
        return $this->payment?->status;
    }

    public function isPaidFull(): bool
    {
        return $this->outstanding_amount === 0;
    }

    public function hasOutstanding(): bool
    {
        return $this->outstanding_amount > 0;
    }

    public function isCancellationRequested(): bool
    {
        return $this->payment?->status === 'cancel_requested';
    }

    protected function formattedPhone(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->phone ? (str_starts_with($this->phone, '0') ? $this->phone : '0' . $this->phone) : '-'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Local Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePaid($query)
    {
        return $query->where('outstanding_amount', 0);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('outstanding_amount', '>', 0);
    }
}