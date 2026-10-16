<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barber extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'photo',
        'phone',
        'instagram',
        'is_active',
        'schedule_template',
    ];

    protected $appends = [
        'photo_url',
        'instagram_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'schedule_template' => 'array',
        ];
    }

    /**
     * Dapatkan template jadwal default bawaan sistem berdasarkan ID atau nama barber.
     */
    public static function defaultScheduleTemplateFor(int|string $barberId, ?string $name = null): array
    {
        $nameLower = strtolower($name ?? '');

        if ((int) $barberId === 1 || str_contains($nameLower, 'rizal')) {
            return [
                0 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                1 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                2 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                3 => ['14:00', '14:50', '15:40', '16:30', '18:10', '19:00', '19:50', '20:40', '21:30'],
                4 => ['10:00', '10:50', '13:20', '14:00', '14:50', '15:40', '16:30', '17:20', '18:10', '19:00'],
                5 => ['10:00', '10:50', '11:40', '12:30', '13:20', '14:10', '15:00', '15:50', '16:40', '17:30'],
                6 => ['10:00', '10:50', '11:40', '12:30', '13:20', '14:10', '15:00', '15:50', '16:40', '17:30'],
            ];
        }

        if ((int) $barberId === 2 || str_contains($nameLower, 'sogi')) {
            return [
                0 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                1 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                2 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                3 => ['10:00', '10:40', '11:20', '12:00', '12:40', '13:20', '14:00', '14:40', '16:20', '17:00', '17:40'],
                4 => ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
                5 => ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
                6 => ['14:00', '14:40', '15:20', '17:00', '17:40', '18:20', '19:00', '19:40', '20:20', '21:00', '21:40'],
            ];
        }

        return [
            0 => [], 1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [],
        ];
    }

    /**
     * Dapatkan template jadwal mingguan efektif untuk barber ini (dari database atau fallback bawaan).
     */
    public function getWeeklyTemplate(): array
    {
        if (! empty($this->schedule_template) && is_array($this->schedule_template)) {
            // Normalisasi key integer 0..6
            $result = [];
            for ($offset = 0; $offset <= 6; $offset++) {
                $result[$offset] = $this->schedule_template[$offset] ?? $this->schedule_template[(string) $offset] ?? [];
            }
            return $result;
        }

        return self::defaultScheduleTemplateFor($this->id, $this->name);
    }

    // URL foto lengkap agar bisa langsung dipakai di frontend (mis. Alpine.js)
    // tanpa perlu membangun path storage secara manual di setiap view.
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? asset('storage/' . $this->photo) : null,
        );
    }

    protected function instagramUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->instagram) {
                    return null;
                }

                return str_starts_with($this->instagram, 'http://') || str_starts_with($this->instagram, 'https://')
                    ? $this->instagram
                    : 'https://www.instagram.com/' . ltrim($this->instagram, '@/');
            },
        );
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

}
