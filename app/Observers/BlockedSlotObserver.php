<?php

namespace App\Observers;

use App\Models\BlockedSlot;

class BlockedSlotObserver
{
    /**
     * Handle the BlockedSlot "created" event.
     */
    public function created(BlockedSlot $blockedSlot): void
    {
        Schedule::where('barber_id', $blockedSlot->barber_id)
            ->where('date', $blockedSlot->date)
            ->where('slot_time', $blockedSlot->slot_time)
            ->update(['is_available' => false]);
    }

    /**
     * Handle the BlockedSlot "updated" event.
     */
    public function updated(BlockedSlot $blockedSlot): void
    {
        //
    }

    /**
     * Handle the BlockedSlot "deleted" event.
     */
    public function deleted(BlockedSlot $blockedSlot): void
    {
        Schedule::where('barber_id', $blockedSlot->barber_id)
            ->where('date', $blockedSlot->date)
            ->where('slot_time', $blockedSlot->slot_time)
            ->update(['is_available' => true]);
    }

    /**
     * Handle the BlockedSlot "restored" event.
     */
    public function restored(BlockedSlot $blockedSlot): void
    {
        //
    }

    /**
     * Handle the BlockedSlot "force deleted" event.
     */
    public function forceDeleted(BlockedSlot $blockedSlot): void
    {
        //
    }
}
