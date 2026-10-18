<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedSlot extends Model
{
    use HasFactory;

    public const REASON_CHEMICAL = 'chemical_service';
    public const REASON_MANUAL = 'manual_block';
    public const REASON_BREAK = 'break_time';
    public const REASON_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'schedule_id',
        'booking_id',
        'reason',
        'notes',
        'created_by',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
