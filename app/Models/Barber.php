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
        'is_active',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // URL foto lengkap agar bisa langsung dipakai di frontend (mis. Alpine.js)
    // tanpa perlu membangun path storage secara manual di setiap view.
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? asset('storage/' . $this->photo) : null,
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
