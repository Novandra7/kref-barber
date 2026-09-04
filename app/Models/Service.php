<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'category',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
    
    protected function formattedPrice(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $price = $attributes['price'] ?? 0;

                if (!is_numeric($price)) {
                    return $price;
                }

                if ($price >= 1000) {
                    // Mensupport pecahan seperti 150000 -> 150k atau 155000 -> 155k
                    // Jika ada koma/desimal misal 12.500 -> 12.5k
                    $formatted = $price / 1000;
                    return (floor($formatted) == $formatted ? (int)$formatted : number_format($formatted, 1)) . 'K';
                }

                return $price;
            }
        );
    }
}
