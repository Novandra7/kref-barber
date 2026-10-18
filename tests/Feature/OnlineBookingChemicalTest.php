<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\BookingNotificationService;
use App\Services\DokuService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineBookingChemicalTest extends TestCase
{
    use RefreshDatabase;

    private Barber $barber;
    private Service $haircut;
    private Service $chemical;
    private string $futureDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(BookingNotificationService::class, function ($mock) {
            $mock->shouldReceive('bookingCreated')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingConfirmationCustomer')->zeroOrMoreTimes();
            $mock->shouldReceive('sendCustomerPaymentDetailsNotification')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingReminder')->zeroOrMoreTimes();
        });

        $this->mock(DokuService::class, function ($mock) {
            $mock->shouldReceive('createQrisPayment')->andReturn([
                'qrContent'   => 'dummy-qris-content',
                'referenceNo' => 'DOKU-REF-12345',
                'paymentUrl'  => 'https://example.com/pay',
            ]);
        });

        $this->barber = Barber::create([
            'name'      => 'Barber Test',
            'role'      => 'Senior Barber',
            'phone'     => '081234567890',
            'is_active' => true,
        ]);

        $this->haircut = Service::create([
            'name'      => 'Regular Haircut',
            'code'      => 'RH',
            'price'     => 60000,
            'category'  => 'Haircut',
            'is_active' => true,
        ]);

        $this->chemical = Service::create([
            'name'      => 'Down Perm',
            'code'      => 'DP',
            'price'     => 150000,
            'category'  => 'Chemical',
            'is_active' => true,
        ]);

        $this->futureDate = Carbon::tomorrow()->toDateString();
    }

    public function test_online_booking_chemical_combo_locks_next_slot_and_creates_blocked_slot(): void
    {
        $slot1 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '10:00:00',
            'is_available' => true,
        ]);

        $slot2 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '11:00:00',
            'is_available' => true,
        ]);

        $payload = [
            'payment_type' => 'DP',
            'guests'       => [
                [
                    'barber'           => $this->barber->id,
                    'date'             => $this->futureDate,
                    'time'             => '10:00',
                    'name'             => 'Customer Chem',
                    'phone'            => '081234567890',
                    'selectedHaircut'  => $this->haircut->name,
                    'selectedChemical' => $this->chemical->name,
                ],
            ],
        ];

        $response = $this->postJson('/booking/checkout', $payload);

        $response->assertStatus(201);

        $slot1->refresh();
        $slot2->refresh();

        $this->assertFalse($slot1->is_available);
        $this->assertFalse($slot2->is_available);

        $booking = Booking::where('name', 'Customer Chem')->firstOrFail();

        $blockedSlot = BlockedSlot::where('schedule_id', $slot2->id)->first();
        $this->assertNotNull($blockedSlot);
        $this->assertEquals($booking->id, $blockedSlot->booking_id);
        $this->assertEquals(BlockedSlot::REASON_CHEMICAL, $blockedSlot->reason);
    }

    public function test_online_booking_chemical_combo_fails_if_next_slot_is_unavailable(): void
    {
        Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '10:00:00',
            'is_available' => true,
        ]);

        Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '11:00:00',
            'is_available' => false,
        ]);

        $payload = [
            'payment_type' => 'DP',
            'guests'       => [
                [
                    'barber'           => $this->barber->id,
                    'date'             => $this->futureDate,
                    'time'             => '10:00',
                    'name'             => 'Customer Chem Bentrok',
                    'phone'            => '081234567890',
                    'selectedHaircut'  => $this->haircut->name,
                    'selectedChemical' => $this->chemical->name,
                ],
            ],
        ];

        $response = $this->postJson('/booking/checkout', $payload);

        $response->assertStatus(422);
        $this->assertStringContainsString('chemical', strtolower($response->getContent()));
    }

    public function test_online_booking_chemical_combo_fails_if_no_next_slot_exists(): void
    {
        // Only one slot for the day (e.g. last slot)
        Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '20:00:00',
            'is_available' => true,
        ]);

        $payload = [
            'payment_type' => 'DP',
            'guests'       => [
                [
                    'barber'           => $this->barber->id,
                    'date'             => $this->futureDate,
                    'time'             => '20:00',
                    'name'             => 'Customer Chem No Next',
                    'phone'            => '081234567890',
                    'selectedHaircut'  => $this->haircut->name,
                    'selectedChemical' => $this->chemical->name,
                ],
            ],
        ];

        $response = $this->postJson('/booking/checkout', $payload);

        $response->assertStatus(422);
    }

    public function test_online_booking_chemical_only_without_haircut_or_treatment_does_not_lock_next_slot(): void
    {
        $slot1 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '10:00:00',
            'is_available' => true,
        ]);

        $slot2 = Schedule::create([
            'barber_id'    => $this->barber->id,
            'date'         => $this->futureDate,
            'slot_time'    => '11:00:00',
            'is_available' => true,
        ]);

        $payload = [
            'payment_type' => 'DP',
            'guests'       => [
                [
                    'barber'           => $this->barber->id,
                    'date'             => $this->futureDate,
                    'time'             => '10:00',
                    'name'             => 'Customer Chem Solo',
                    'phone'            => '081234567890',
                    'selectedHaircut'  => null,
                    'selectedChemical' => $this->chemical->name,
                ],
            ],
        ];

        $response = $this->postJson('/booking/checkout', $payload);

        $response->assertStatus(201);

        $slot1->refresh();
        $slot2->refresh();

        $this->assertFalse($slot1->is_available);
        $this->assertTrue($slot2->is_available);

        $this->assertEquals(0, BlockedSlot::where('schedule_id', $slot2->id)->count());
    }
}
