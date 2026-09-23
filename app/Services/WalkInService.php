<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Schedule;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\WahaService;

class WalkInService
{
    public function processWebhook(string $body, ?string $participant)
    {
        Log::info('WalkInService processing message', ['body' => $body, 'participant' => $participant]);

        // Parse pesan (HH:mm: nama)
        if (!preg_match('/(\d{1,2}:\d{2})\s*:\s*(.+)$/s', trim($body), $matches, PREG_OFFSET_CAPTURE)) {
            Log::info('WalkInService: Message format not matched');
            return; // ignore
        }

        $timeStr = $matches[1][0]; // "10:00"
        $customerName = trim($matches[2][0]);
        $barberName = trim(substr(trim($body), 0, $matches[0][1]));

        $barber = null;

        if (!empty($barberName)) {
            $barber = Barber::where('name', 'LIKE', '%' . $barberName . '%')->first();
        }

        if (!$barber && $participant) {
            $participantNum = str_replace('@c.us', '', $participant);
            if (str_starts_with($participantNum, '62')) {
                $participantNum = '0' . substr($participantNum, 2);
            }
            // Barber phone may be 0812... or 62812...
            // Let's strip first digit to match last 9-10 digits safely
            $participantTail = substr($participantNum, 1);
            $barber = Barber::where('phone', 'LIKE', '%' . $participantTail . '%')->first();
        }

        if (!$barber) {
            Log::error('WalkInService: Barber not found', ['name' => $barberName, 'participant' => $participant]);
            $this->replyToGroup("⚠️ Walk-in gagal: Barber tidak ditemukan. Pastikan nomor pengirim terdaftar atau sebutkan nama barber.");
            return;
        }

        // Parse slot time
        try {
            $slotTime = Carbon::createFromFormat('H:i', $timeStr)->format('H:i:00');
        } catch (\Exception $e) {
            Log::error('WalkInService: Invalid time format', ['time' => $timeStr]);
            return;
        }

        $today = Carbon::today()->toDateString();

        // Cari schedule
        $schedule = Schedule::where('barber_id', $barber->id)
            ->where('date', $today)
            ->where('slot_time', $slotTime)
            ->where('is_available', true)
            ->first();

        $originalTime = $timeStr;
        $switched = false;

        if (!$schedule) {
            // Fallback: cari slot kosong terdekat hari ini
            $availableSchedules = Schedule::where('barber_id', $barber->id)
                ->where('date', $today)
                ->where('is_available', true)
                ->get();

            if ($availableSchedules->isEmpty()) {
                Log::warning('WalkInService: No available slots', ['barber_id' => $barber->id]);
                $this->replyToGroup("⚠️ Walk-in gagal: Tidak ada slot kosong hari ini untuk {$barber->name}.");
                return;
            }

            // Cari yg paling dekat dengan $slotTime
            $targetCarbon = Carbon::createFromFormat('H:i', $timeStr);
            $closestSchedule = null;
            $minDiff = PHP_INT_MAX;

            foreach ($availableSchedules as $s) {
                // Determine format
                $sCarbon = $s->slot_time instanceof \Carbon\Carbon 
                    ? clone $s->slot_time 
                    : Carbon::parse($s->slot_time);
                
                // Align date to match targetCarbon (which uses today as default)
                $sCarbon->setDate($targetCarbon->year, $targetCarbon->month, $targetCarbon->day);

                $diff = abs($sCarbon->diffInMinutes($targetCarbon));
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $closestSchedule = $s;
                }
            }

            $schedule = $closestSchedule;
            $switched = true;
        }
        
        // Buat booking
        try {
            // schedule_id = ID schedule yang ditemukan
            $sTimeObj = $schedule->slot_time instanceof \Carbon\Carbon 
                ? $schedule->slot_time 
                : Carbon::parse($schedule->slot_time);
            
            $scheduledAtStr = $today . ' ' . $sTimeObj->format('H:i:s');
            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i:s', $scheduledAtStr);

            Booking::create([
                'schedule_id' => $schedule->id,
                'barber_id' => $barber->id,
                'name' => $customerName,
                'phone' => null, // now nullable
                'source' => 'walk_in',
                'status' => 'confirmed',
                'payment_type' => 'full',
                'total_amount' => 0,
                'outstanding_amount' => 0,
                'scheduled_at' => $scheduledAt,
            ]);

            $schedule->update(['is_available' => false]);

            $assignedTime = $sTimeObj->format('H:i');

            if ($switched) {
                $msg = "✅ Walk-in *$customerName* terdaftar di slot *$assignedTime* untuk barber *{$barber->name}*.\n⚠️ Slot $originalTime tidak tersedia, dialihkan ke *$assignedTime*.";
            } else {
                $msg = "✅ Walk-in *$customerName* terdaftar di slot *$assignedTime* untuk barber *{$barber->name}*.";
            }

            $this->replyToGroup($msg);
            Log::info('WalkInService: Booking created successfully', ['schedule_id' => $schedule->id]);

        } catch (\Exception $e) {
            Log::error('WalkInService: Failed to create booking: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->replyToGroup("⚠️ Terjadi kesalahan sistem saat membuat booking walk-in.");
        }
    }

    protected function replyToGroup(string $message): void
    {
        $opsGroupId = config('services.kref.ops_group_id');

        if (!$opsGroupId) {
            Log::error('WalkInService: Missing KREF_OPS_GROUP_ID configuration');
            return;
        }

        try {
            $waha = app(WahaService::class);
            $waha->sendMessage($opsGroupId, $message);
        } catch (\Throwable $e) {
            Log::error('WalkInService: Failed to send WA message: ' . $e->getMessage());
        }
    }
}
