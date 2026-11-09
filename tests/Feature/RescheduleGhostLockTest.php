<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingNotificationService;
use App\Services\DokuService;
use App\Services\PaymentExpirationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RescheduleGhostLockTest extends TestCase
{
    use RefreshDatabase;

    private Barber $barber;
    private string $futureDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->mock(BookingNotificationService::class, function ($mock) {
            $mock->shouldReceive('bookingExpired')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingRescheduled')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingRescheduleRejected')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingReminder')->zeroOrMoreTimes();
            $mock->shouldReceive('sendDailyRecapToOpsGroup')->zeroOrMoreTimes();
        });

        $this->mock(DokuService::class);

        $this->barber = Barber::create([
            'name'      => 'Rizal',
            'role'      => 'owner',
            'phone'     => '08989835533',
            'is_active' => true,
        ]);

        $this->futureDate = Carbon::tomorrow()->toDateString();
    }

    public function test_pending_payment_booking_cannot_request_reschedule(): void
    {
        $slot1230 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '12:30:00',
            'is_available' => false,
        ]);

        $slot1050 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '10:50:00',
            'is_available' => true,
        ]);

        $payment = Payment::create([
            'amount'               => 80000,
            'method'               => 'qris_doku',
            'provider'             => 'doku',
            'purpose'              => 'full_payment',
            'status'               => 'pending',
            'partner_reference_no' => 'KREF-PENDING123',
            'expires_at'           => now()->addHour(),
        ]);

        $booking = Booking::create([
            'schedule_id'        => $slot1230->id,
            'payment_id'         => $payment->id,
            'barber_id'          => $this->barber->id,
            'name'               => 'Andi Asbih',
            'phone'              => '895327984463',
            'source'             => 'online',
            'payment_type'       => 'full',
            'status'             => 'pending',
            'total_amount'       => 80000,
            'outstanding_amount' => 80000,
            'scheduled_at'       => $this->futureDate . ' 12:30:00',
        ]);

        $response = $this->post(route('booking.reschedule', ['reference' => 'KREF-PENDING123']), [
            'booking_id'  => $booking->id,
            'schedule_id' => $slot1050->id,
        ]);

        $response->assertRedirect(route('booking.payment.detail', ['reference' => 'KREF-PENDING123']));
        $response->assertSessionHas('error');

        $this->assertTrue($slot1050->fresh()->is_available);
        $this->assertNull($booking->fresh()->requested_schedule_id);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_expiring_payment_releases_both_schedule_and_requested_schedule(): void
    {
        $slot1230 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '12:30:00',
            'is_available' => false,
        ]);

        $slot1050 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '10:50:00',
            'is_available' => false,
        ]);

        $payment = Payment::create([
            'amount'               => 80000,
            'method'               => 'qris_doku',
            'provider'             => 'doku',
            'purpose'              => 'full_payment',
            'status'               => 'pending',
            'partner_reference_no' => 'KREF-EXP123',
            'expires_at'           => now()->subMinutes(5),
        ]);

        $booking = Booking::create([
            'schedule_id'           => $slot1230->id,
            'requested_schedule_id' => $slot1050->id,
            'payment_id'            => $payment->id,
            'barber_id'             => $this->barber->id,
            'name'                  => 'Andi Asbih',
            'phone'                 => '895327984463',
            'source'                => 'online',
            'payment_type'          => 'full',
            'status'                => 'reschedule_requested',
            'total_amount'          => 80000,
            'outstanding_amount'    => 80000,
            'scheduled_at'          => $this->futureDate . ' 12:30:00',
        ]);

        app(PaymentExpirationService::class)->expirePayment($payment);

        $this->assertSame('expired', $payment->fresh()->status);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->requested_schedule_id);
        $this->assertTrue($slot1230->fresh()->is_available);
        $this->assertTrue($slot1050->fresh()->is_available);
    }

    public function test_paid_booking_reschedule_and_admin_approval_flow(): void
    {
        $slot1230 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '12:30:00',
            'is_available' => false,
        ]);

        $slot1050 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '10:50:00',
            'is_available' => true,
        ]);

        $payment = Payment::create([
            'amount'               => 80000,
            'method'               => 'qris_doku',
            'provider'             => 'doku',
            'purpose'              => 'full_payment',
            'status'               => 'paid',
            'partner_reference_no' => 'KREF-PAID123',
        ]);

        $booking = Booking::create([
            'schedule_id'        => $slot1230->id,
            'payment_id'         => $payment->id,
            'barber_id'          => $this->barber->id,
            'name'               => 'Andi Asbih',
            'phone'              => '895327984463',
            'source'             => 'online',
            'payment_type'       => 'full',
            'status'             => 'confirmed',
            'total_amount'       => 80000,
            'outstanding_amount' => 0,
            'scheduled_at'       => $this->futureDate . ' 12:30:00',
        ]);

        $this->post(route('booking.reschedule', ['reference' => 'KREF-PAID123']), [
            'booking_id'  => $booking->id,
            'schedule_id' => $slot1050->id,
        ])->assertRedirect(route('booking.payment.detail', ['reference' => 'KREF-PAID123']));

        $this->assertFalse($slot1050->fresh()->is_available);
        $this->assertSame($slot1050->id, $booking->fresh()->requested_schedule_id);
        $this->assertSame('reschedule_requested', $booking->fresh()->status);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->patch(route('admin.bookings.update-status', $booking), [
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $this->assertTrue($slot1230->fresh()->is_available);
        $this->assertFalse($slot1050->fresh()->is_available);
        $this->assertSame($slot1050->id, $booking->fresh()->schedule_id);
        $this->assertNull($booking->fresh()->requested_schedule_id);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_admin_drag_drop_reschedule_releases_unselected_requested_schedule(): void
    {
        $slot1230 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '12:30:00',
            'is_available' => false,
        ]);

        $slot1050 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '10:50:00',
            'is_available' => false,
        ]);

        $slot1410 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '14:10:00',
            'is_available' => true,
        ]);

        $booking = Booking::create([
            'schedule_id'           => $slot1230->id,
            'requested_schedule_id' => $slot1050->id,
            'barber_id'             => $this->barber->id,
            'name'                  => 'Andi Asbih',
            'phone'                 => '895327984463',
            'source'                => 'online',
            'payment_type'          => 'full',
            'status'                => 'reschedule_requested',
            'total_amount'          => 80000,
            'outstanding_amount'    => 0,
            'scheduled_at'          => $this->futureDate . ' 12:30:00',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('admin.schedules.reschedule-booking'), [
                'booking_id'         => $booking->id,
                'target_schedule_id' => $slot1410->id,
                'release_old_slot'   => true,
                'notify_customer'    => false,
            ])
            ->assertRedirect();

        $this->assertTrue($slot1230->fresh()->is_available);
        $this->assertTrue($slot1050->fresh()->is_available);
        $this->assertFalse($slot1410->fresh()->is_available);
        $this->assertSame($slot1410->id, $booking->fresh()->schedule_id);
        $this->assertNull($booking->fresh()->requested_schedule_id);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }
}
