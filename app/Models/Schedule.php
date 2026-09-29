<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'barber_id',
        'date',
        'slot_time',
        'is_available',
    ];

    /**
     * Scope query untuk hanya mengambil slot jadwal yang berada di masa mendatang
     * (hari ini dengan jam ke depan, atau tanggal setelah hari ini).
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString();

        return $query->where(function (Builder $q) use ($today, $currentTime): void {
            $q->where('date', '>', $today)
                ->orWhere(function (Builder $sub) use ($today, $currentTime): void {
                    $sub->where('date', '=', $today)
                        ->where('slot_time', '>', $currentTime);
                });
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'slot_time' => 'datetime:H:i',
            'is_available' => 'boolean',
        ];
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(Barber::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}