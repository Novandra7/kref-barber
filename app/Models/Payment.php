<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * Booking utama (pertama) terkait Payment ini (helper backward-compatibility)
     */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class)->oldestOfMany();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Total amount yang sudah di-refund (completed) dari tabel refunds
     */
    public function totalRefunded(): int
    {
        return (int) $this->refunds()->where('status', 'completed')->sum('amount');
    }

    /**
     * Sisa amount yang masih bisa di-refund
     */
    public function remainingRefundable(): int
    {
        return max(0, (int) $this->amount - $this->totalRefunded());
    }

    /**
     * Mengecek apakah sumber pembayaran didukung oleh SNAP DOKU QRIS MPM Refund
     */
    public function canBeRefundedViaDoku(): bool
    {
        if ($this->provider !== 'doku') {
            return false;
        }

        $source = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string) $this->payment_source));
        if (empty($source)) {
            return false;
        }

        $supportedIssuers = config('services.doku.supported_refund_issuers');

        foreach ($supportedIssuers as $issuer) {
            $normalizedIssuer = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', (string) $issuer));
            if (!empty($normalizedIssuer) && str_contains($source, $normalizedIssuer)) {
                return true;
            }
        }

        return false;
    }
}
