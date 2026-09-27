<?php

namespace App\Services;

use App\Models\Barber;
use App\Models\Schedule;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\WahaService;

class WalkInService
{
    public function processWebhook(string $body, ?string $participant)
    {
        Log::info('WalkInService processing message', ['body' => $body, 'participant' => $participant]);

        // 1. Ambil nomor sender WhatsApp untuk fallback barber
        $senderBarber = null;
        if ($participant) {
            $participantNum = str_replace('@c.us', '', $participant);
            if (str_starts_with($participantNum, '62')) {
                $participantNum = '0' . substr($participantNum, 2);
            }
            $participantTail = substr($participantNum, 1);
            $senderBarber = Barber::where('phone', 'LIKE', '%' . $participantTail . '%')->first();
        }

        $allBarbers = Barber::all();
        $slotPattern = '/^(?:(?<barber_prefix>.*?)\s*[:\s]\s*)?(?<time>\d{1,2}[:.]\d{2})\s*:\s*(?<name>.+?)\s*-\s*(?<payment_type>FP|DP)\s*-\s*(?<nominal>\d+(?:[.,]\d+)?\s*[kK]?|\d+)\s*-\s*(?<service_code>[a-zA-Z0-9_-]+)$/i';

        $lines = preg_split('/\r\n|\r|\n/', trim($body));
        $activeBarber = null;
        $slotsToProcess = [];
        $hasInvalidAttempt = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // A. Cek apakah baris ini adalah header nama barber (misal: "Rizal:" atau "Sogi:")
            $candidateName = rtrim($trimmed, ': ');
            $matchedHeaderBarber = $allBarbers->first(function ($b) use ($candidateName) {
                return strcasecmp($b->name, $candidateName) === 0 || str_starts_with(strtolower($candidateName), strtolower($b->name));
            });

            if ($matchedHeaderBarber && !preg_match('/^\d{1,2}[:.]\d{2}/', $trimmed)) {
                $activeBarber = $matchedHeaderBarber;
                continue;
            }

            // B. Cek apakah baris ini cocok dengan format walk-in baru
            if (preg_match($slotPattern, $trimmed, $matches)) {
                $barber = null;
                if (!empty($matches['barber_prefix'])) {
                    $prefixName = trim($matches['barber_prefix']);
                    $barber = $allBarbers->first(fn ($b) => strcasecmp($b->name, $prefixName) === 0 || stripos($b->name, $prefixName) !== false);
                }
                if (!$barber) {
                    $barber = $activeBarber ?? $senderBarber;
                }

                $slotsToProcess[] = [
                    'barber' => $barber,
                    'time' => str_replace('.', ':', $matches['time']),
                    'name' => trim($matches['name']),
                    'payment_type' => strtoupper(trim($matches['payment_type'])),
                    'nominal' => strtolower(trim($matches['nominal'])),
                    'service_code' => trim($matches['service_code']),
                ];
                continue;
            }

            // C. Cek apakah ada upaya penulisan walk-in yang salah format
            // (diawali jam tapi bukan slot kosong bawaan recap e.g. "10:00:" atau booking lama recap)
            if (preg_match('/^\d{1,2}[:.]\d{2}/', $trimmed)) {
                // Abaikan slot kosong bawaan template seperti "10:00:" atau "10:00"
                if (preg_match('/^\d{1,2}[:.]\d{2}:?\s*$/', $trimmed)) {
                    continue;
                }
                // Abaikan booking lama bawaan recap seperti "11:00: budi FP 70k"
                if (preg_match('/^\d{1,2}[:.]\d{2}\s*:\s*.+?\s+(?:FP|DP)\s+\d+/i', $trimmed)) {
                    continue;
                }
                // Jika bukan slot kosong dan bukan booking lama, maka upaya walk-in salah format
                $hasInvalidAttempt = true;
            }
        }

        // Jika tidak ada slot walk-in yang cocok
        if (empty($slotsToProcess)) {
            if ($hasInvalidAttempt) {
                Log::info('WalkInService: Invalid walk-in format attempt detected, sending guide');
                $this->replyFormatGuide();
            } else {
                Log::info('WalkInService: No walk-in slot found, ignoring message');
            }
            return;
        }

        $today = Carbon::today()->toDateString();
        $successfulBookings = [];

        foreach ($slotsToProcess as $slotData) {
            $barber = $slotData['barber'];

            if (!$barber) {
                Log::error('WalkInService: Barber not identified for slot', $slotData);
                $this->replyToGroup("⚠️ Walk-in gagal untuk jam {$slotData['time']}: Barber tidak dapat diidentifikasi dari template maupun nomor pengirim.");
                continue;
            }

            // 1. Cari Service
            $service = Service::whereRaw('LOWER(code) = ?', [strtolower($slotData['service_code'])])
                ->where('is_active', true)
                ->first();

            if (!$service) {
                Log::error('WalkInService: Service not found', ['code' => $slotData['service_code']]);
                $this->replyFormatGuide("Walk-in gagal: Kode service '{$slotData['service_code']}' tidak ditemukan atau tidak aktif.");
                continue;
            }

            // 2. Parse waktu slot
            try {
                $slotTime = Carbon::createFromFormat('H:i', $slotData['time'])->format('H:i:00');
            } catch (\Exception $e) {
                Log::error('WalkInService: Invalid time format', ['time' => $slotData['time']]);
                $this->replyFormatGuide("Format jam '{$slotData['time']}' tidak valid.");
                continue;
            }

            // 3. Cek apakah slot ini SUDAH dibooking di database (menghindari duplikasi saat salin ulang template)
            $existingSchedule = Schedule::where('barber_id', $barber->id)
                ->where('date', $today)
                ->where('slot_time', $slotTime)
                ->first();

            if ($existingSchedule && !$existingSchedule->is_available) {
                $alreadyBooked = Booking::where('schedule_id', $existingSchedule->id)
                    ->whereNotIn('status', ['cancelled'])
                    ->exists();

                if ($alreadyBooked) {
                    Log::info("WalkInService: Slot {$slotData['time']} already booked in DB, skipping duplicate.", [
                        'barber_id' => $barber->id,
                        'schedule_id' => $existingSchedule->id,
                    ]);
                    continue;
                }
            }

            // 4. Resolusi Jadwal (Termasuk Jam Baru)
            $originalTime = $slotData['time'];
            $switched = false;
            $schedule = null;

            if (!$existingSchedule) {
                // Jam baru di luar template -> buat schedule baru otomatis
                $schedule = Schedule::create([
                    'barber_id' => $barber->id,
                    'date' => $today,
                    'slot_time' => $slotTime,
                    'is_available' => true,
                ]);
            } elseif ($existingSchedule->is_available) {
                $schedule = $existingSchedule;
            } else {
                // Cari slot kosong terdekat hari ini
                $availableSchedules = Schedule::where('barber_id', $barber->id)
                    ->where('date', $today)
                    ->where('is_available', true)
                    ->get();

                if ($availableSchedules->isEmpty()) {
                    Log::warning('WalkInService: No available slots', ['barber_id' => $barber->id]);
                    $this->replyToGroup("⚠️ Walk-in gagal: Tidak ada slot kosong hari ini untuk {$barber->name}.");
                    continue;
                }

                $targetCarbon = Carbon::createFromFormat('H:i', $slotData['time']);
                $closestSchedule = null;
                $minDiff = PHP_INT_MAX;

                foreach ($availableSchedules as $s) {
                    $sCarbon = $s->slot_time instanceof \Carbon\Carbon
                        ? clone $s->slot_time
                        : Carbon::parse($s->slot_time);

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

            // 5. Parse nominal & keuangan
            if (str_ends_with($slotData['nominal'], 'k')) {
                $val = (float) str_replace('k', '', $slotData['nominal']);
                $parsedNominal = (int) round($val * 1000);
            } else {
                $parsedNominal = (int) preg_replace('/[^\d]/', '', $slotData['nominal']);
            }

            $totalAmount = $service->price;
            if ($slotData['payment_type'] === 'FP') {
                $paymentType = 'full';
                $paidAmount = $service->price;
                $outstandingAmount = 0;
                $paymentPurpose = 'walk_in';
            } else {
                $paymentType = 'dp';
                $paidAmount = $parsedNominal;
                $outstandingAmount = max(0, $totalAmount - $paidAmount);
                $paymentPurpose = 'dp';
            }

            // 6. Simpan transaksi secara aman
            try {
                $sTimeObj = $schedule->slot_time instanceof \Carbon\Carbon
                    ? $schedule->slot_time
                    : Carbon::parse($schedule->slot_time);

                $scheduledAtStr = $today . ' ' . $sTimeObj->format('H:i:s');
                $scheduledAt = Carbon::createFromFormat('Y-m-d H:i:s', $scheduledAtStr);

                DB::transaction(function () use (
                    $schedule,
                    $barber,
                    $slotData,
                    $paymentType,
                    $totalAmount,
                    $outstandingAmount,
                    $scheduledAt,
                    $service,
                    $paidAmount,
                    $paymentPurpose
                ) {
                    $booking = Booking::create([
                        'schedule_id' => $schedule->id,
                        'barber_id' => $barber->id,
                        'name' => $slotData['name'],
                        'phone' => null,
                        'source' => 'walk_in',
                        'status' => 'confirmed',
                        'payment_type' => $paymentType,
                        'total_amount' => $totalAmount,
                        'outstanding_amount' => $outstandingAmount,
                        'scheduled_at' => $scheduledAt,
                    ]);

                    BookingItem::create([
                        'booking_id' => $booking->id,
                        'item_type' => 'service',
                        'service_id' => $service->id,
                        'qty' => 1,
                        'service_name_snapshot' => $service->name,
                        'price_snapshot' => $service->price,
                    ]);

                    $payment = Payment::create([
                        'amount' => $paidAmount,
                        'method' => 'cash',
                        'provider' => 'manual',
                        'purpose' => $paymentPurpose,
                        'status' => 'paid',
                        'recorded_by' => null,
                    ]);

                    $booking->update(['payment_id' => $payment->id]);
                    $schedule->update(['is_available' => false]);
                });

                $assignedTime = $sTimeObj->format('H:i');
                $successfulBookings[] = [
                    'name' => $slotData['name'],
                    'barber_name' => $barber->name,
                    'service_name' => $service->name,
                    'assigned_time' => $assignedTime,
                    'original_time' => $originalTime,
                    'switched' => $switched,
                    'payment_type' => $slotData['payment_type'],
                    'paid_amount' => $paidAmount,
                    'outstanding_amount' => $outstandingAmount,
                ];

                Log::info('WalkInService: Booking created successfully', ['schedule_id' => $schedule->id]);

            } catch (\Exception $e) {
                Log::error('WalkInService: Failed to create booking: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $this->replyToGroup("⚠️ Terjadi kesalahan sistem saat membuat booking walk-in untuk {$slotData['name']}.");
            }
        }

        // 7. Kirim pesan notifikasi sukses ke grup ops
        if (!empty($successfulBookings)) {
            if (count($successfulBookings) === 1) {
                $b = $successfulBookings[0];
                $formattedPaid = number_format($b['paid_amount'], 0, ',', '.');
                $formattedOutstanding = number_format($b['outstanding_amount'], 0, ',', '.');

                $msg = "✅ Walk-in *{$b['name']}* (*{$b['service_name']}*) terdaftar di slot *{$b['assigned_time']}* untuk barber *{$b['barber_name']}*.\n"
                    . "💳 Pembayaran: *{$b['payment_type']}* | Masuk: *Rp {$formattedPaid}* | Sisa: *Rp {$formattedOutstanding}*";

                if ($b['switched']) {
                    $msg .= "\n⚠️ Slot {$b['original_time']} tidak tersedia, dialihkan ke *{$b['assigned_time']}*.";
                }
            } else {
                $msg = "✅ *" . count($successfulBookings) . " Walk-in berhasil didaftarkan:*\n";
                foreach ($successfulBookings as $b) {
                    $formattedPaid = number_format($b['paid_amount'], 0, ',', '.');
                    $formattedOutstanding = number_format($b['outstanding_amount'], 0, ',', '.');

                    $msg .= "\n• *{$b['name']}* (*{$b['service_name']}*) - Slot *{$b['assigned_time']}* (Barber *{$b['barber_name']}*)\n"
                        . "  💳 *{$b['payment_type']}* | Masuk: *Rp {$formattedPaid}* | Sisa: *Rp {$formattedOutstanding}*";

                    if ($b['switched']) {
                        $msg .= "\n  ⚠️ Slot {$b['original_time']} dialihkan ke *{$b['assigned_time']}*.";
                    }
                }
            }

            $this->replyToGroup($msg);
        }
    }

    protected function replyFormatGuide(string $reason = 'Format walk-in tidak valid.'): void
    {
        $message = "⚠️ {$reason}\n\n"
            . "Format yang diterima:\n"
            . "HH:mm: {nama}-{FP/DP}-{nominal}-{kode service}\n\n"
            . "Contoh:\n"
            . "• 10:00: Budi-FP-70k-RH\n"
            . "• 14:00: Agus Pratama-DP-40k-HBR";

        $this->replyToGroup($message);
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
