<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Service;
use App\Services\BookingNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkInTest extends TestCase
{
    use RefreshDatabase;

    private Barber $barber1;
    private Barber $barber2;
    private Service $service1;
    private Service $service2;
    private string $today;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock notification service so WhatsApp messages aren't actually fired
        $this->mock(BookingNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendDailyRecapToOpsGroup')->zeroOrMoreTimes();
        });

        $this->today = Carbon::today()->toDateString();

        $this->barber1 = Barber::create([
            'name' => 'Barber Satu',
            'role' => 'senior',
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        $this->barber2 = Barber::create([
            'name' => 'Barber Dua',
            'role' => 'barber',
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        $this->service1 = Service::create([
            'name' => 'Regular Haircut',
            'code' => 'RH',
            'price' => 50000,
            'category' => 'Haircut',
            'is_active' => true,
        ]);

        $this->service2 = Service::create([
            'name' => 'Beard Trim',
            'code' => 'BT',
            'price' => 25000,
            'category' => 'Grooming',
            'is_active' => true,
        ]);
    }

    public function test_walkin_index_displays_walkin_and_confirmed_online_bookings_and_excludes_pending_online(): void
    {
        $schedule1 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $schedule2 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '11:00:00',
            'is_available' => false,
        ]);

        $schedule3 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '12:00:00',
            'is_available' => false,
        ]);

        // 1. Walk-in booking (confirmed)
        Booking::create([
            'schedule_id' => $schedule1->id,
            'barber_id' => $this->barber1->id,
            'name' => 'Customer Walkin',
            'source' => 'walk_in',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
            'total_amount' => 50000,
            'outstanding_amount' => 50000,
        ]);

        // 2. Online booking (confirmed)
        Booking::create([
            'schedule_id' => $schedule2->id,
            'barber_id' => $this->barber1->id,
            'name' => 'Customer Online Confirmed',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 11:00:00',
            'total_amount' => 50000,
            'outstanding_amount' => 0,
        ]);

        // 3. Online booking (pending - unpaid online checkout)
        Booking::create([
            'schedule_id' => $schedule3->id,
            'barber_id' => $this->barber1->id,
            'name' => 'Customer Online Pending',
            'source' => 'online',
            'status' => 'pending',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 12:00:00',
            'total_amount' => 50000,
            'outstanding_amount' => 50000,
        ]);

        $response = $this->get(route('walkin.index'));

        $response->assertOk();
        $response->assertSee('Customer Walkin');
        $response->assertSee('Customer Online Confirmed');
        $response->assertDontSee('Customer Online Pending');
    }

    public function test_walkin_index_filters_by_barber_and_source(): void
    {
        $sched1 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);
        $sched2 = Schedule::create([
            'barber_id' => $this->barber2->id,
            'date' => $this->today,
            'slot_time' => '11:00:00',
            'is_available' => false,
        ]);

        // Booking 1: Barber 1, walk_in
        Booking::create([
            'schedule_id' => $sched1->id,
            'barber_id' => $this->barber1->id,
            'name' => 'Walkin Barber 1',
            'source' => 'walk_in',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
        ]);

        // Booking 2: Barber 2, online
        Booking::create([
            'schedule_id' => $sched2->id,
            'barber_id' => $this->barber2->id,
            'name' => 'Online Barber 2',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 11:00:00',
        ]);

        // Filter by Barber 1
        $resBarber1 = $this->get(route('walkin.index', ['barber' => $this->barber1->id]));
        $resBarber1->assertOk();
        $resBarber1->assertSee('Walkin Barber 1');
        $resBarber1->assertDontSee('Online Barber 2');

        // Filter by Source = online
        $resOnline = $this->get(route('walkin.index', ['source' => 'online']));
        $resOnline->assertOk();
        $resOnline->assertSee('Online Barber 2');
        $resOnline->assertDontSee('Walkin Barber 1');

        // Filter by Source = walk_in
        $resWalkIn = $this->get(route('walkin.index', ['source' => 'walk_in']));
        $resWalkIn->assertOk();
        $resWalkIn->assertSee('Walkin Barber 1');
        $resWalkIn->assertDontSee('Online Barber 2');
    }

    public function test_complete_online_prepaid_full_booking_without_extra_services(): void
    {
        $sched = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $payment = Payment::create([
            'amount' => 50000,
            'method' => 'qris_doku',
            'provider' => 'doku',
            'purpose' => 'full_payment',
            'status' => 'paid',
        ]);

        $booking = Booking::create([
            'schedule_id' => $sched->id,
            'barber_id' => $this->barber1->id,
            'payment_id' => $payment->id,
            'name' => 'Andi Online',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
            'total_amount' => 50000,
            'outstanding_amount' => 0,
        ]);

        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $this->service1->id,
            'item_type' => 'service',
            'qty' => 1,
            'service_name_snapshot' => $this->service1->name,
            'price_snapshot' => 50000,
        ]);

        $response = $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids' => [$this->service1->id],
            'description' => 'Customer request: potong pendek samping',
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
        $this->assertEquals(50000, $booking->total_amount);
        $this->assertEquals(0, $booking->outstanding_amount);
        $this->assertEquals('Customer request: potong pendek samping', $booking->description);

        // No new pelunasan payment was needed
        $this->assertEquals(1, Payment::count());
    }

    public function test_complete_online_prepaid_full_booking_with_extra_services(): void
    {
        $sched = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $payment = Payment::create([
            'amount' => 50000,
            'method' => 'qris_doku',
            'provider' => 'doku',
            'purpose' => 'full_payment',
            'status' => 'paid',
        ]);

        $booking = Booking::create([
            'schedule_id' => $sched->id,
            'barber_id' => $this->barber1->id,
            'payment_id' => $payment->id,
            'name' => 'Budi Online Extra',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
            'total_amount' => 50000,
            'outstanding_amount' => 0,
        ]);

        BookingItem::create([
            'booking_id' => $booking->id,
            'service_id' => $this->service1->id,
            'item_type' => 'service',
            'qty' => 1,
            'service_name_snapshot' => $this->service1->name,
            'price_snapshot' => 50000,
        ]);

        // In addition to service1 (50000), customer adds service2 (25000). Total = 75000.
        // Already prepaid 50000. Remaining balance = 25000.
        $response = $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids' => [$this->service1->id, $this->service2->id],
            'settlement_payment_method' => 'cash',
            'description' => 'Tambah rapikan janggut',
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
        $this->assertEquals(75000, $booking->total_amount);
        $this->assertEquals(0, $booking->outstanding_amount);
        $this->assertEquals('Tambah rapikan janggut', $booking->description);

        // Check that pelunasan payment of 25000 was created
        $pelunasan = Payment::where('purpose', 'pelunasan')->first();
        $this->assertNotNull($pelunasan);
        $this->assertEquals(25000, $pelunasan->amount);
        $this->assertEquals('cash', $pelunasan->method);
    }

    public function test_complete_booking_with_dp_creates_settlement_payment(): void
    {
        $sched = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $dpPayment = Payment::create([
            'amount' => 40000,
            'method' => 'qris_static',
            'provider' => 'manual',
            'purpose' => 'dp',
            'status' => 'paid',
        ]);

        $booking = Booking::create([
            'schedule_id' => $sched->id,
            'barber_id' => $this->barber1->id,
            'payment_id' => $dpPayment->id,
            'name' => 'Charlie DP',
            'source' => 'walk_in',
            'status' => 'confirmed',
            'payment_type' => 'dp',
            'scheduled_at' => $this->today . ' 10:00:00',
            'total_amount' => 40000,
            'outstanding_amount' => 1,
        ]);

        // Service1 is 50000. DP was 40000. Sisa = 10000.
        $response = $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids' => [$this->service1->id],
            'settlement_payment_method' => 'qris_static',
            'description' => 'DP lunas via QRIS',
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
        $this->assertEquals(50000, $booking->total_amount);
        $this->assertEquals(0, $booking->outstanding_amount);
        $this->assertEquals('DP lunas via QRIS', $booking->description);

        $pelunasan = Payment::where('purpose', 'pelunasan')->first();
        $this->assertNotNull($pelunasan);
        $this->assertEquals(10000, $pelunasan->amount);
        $this->assertEquals('qris_static', $pelunasan->method);
    }

    public function test_cancel_online_booking_updates_status_and_frees_schedule(): void
    {
        $sched = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $booking = Booking::create([
            'schedule_id' => $sched->id,
            'barber_id' => $this->barber1->id,
            'name' => 'Doni Batal',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
        ]);

        $response = $this->delete(route('walkin.cancel', ['booking' => $booking->id]));
        $response->assertSessionHas('success');

        $booking->refresh();
        $sched->refresh();

        $this->assertEquals('cancelled', $booking->status);
        $this->assertTrue((bool) $sched->is_available);
    }

    public function test_complete_booking_with_custom_price_for_long_trim(): void
    {
        $sched = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $longTrim = Service::create([
            'name' => 'Long Trim',
            'code' => 'LT',
            'price' => 80000,
            'category' => 'Haircut',
            'is_active' => true,
        ]);

        $payment = Payment::create([
            'amount' => 80000,
            'method' => 'qris_doku',
            'provider' => 'doku',
            'purpose' => 'full_payment',
            'status' => 'paid',
        ]);

        $booking = Booking::create([
            'schedule_id' => $sched->id,
            'barber_id' => $this->barber1->id,
            'payment_id' => $payment->id,
            'name' => 'Dani Long Trim',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
            'total_amount' => 80000,
            'outstanding_amount' => 0,
        ]);

        // Long Trim price adjusted from 80000 to 105000 in the field.
        // Customer already prepaid 80000, so remaining balance is 25000.
        $response = $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids' => [$longTrim->id],
            'custom_prices' => [
                $longTrim->id => 105000,
            ],
            'settlement_payment_method' => 'cash',
            'description' => 'Rambut sangat tebal dan panjang',
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
        $this->assertEquals(105000, $booking->total_amount);
        $this->assertEquals(0, $booking->outstanding_amount);
        $this->assertEquals('Rambut sangat tebal dan panjang', $booking->description);

        // Check snapshot in BookingItem
        $item = $booking->items()->where('service_id', $longTrim->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(105000, $item->price_snapshot);

        // Check settlement payment
        $pelunasan = Payment::where('purpose', 'pelunasan')->first();
        $this->assertNotNull($pelunasan);
        $this->assertEquals(25000, $pelunasan->amount);
        $this->assertEquals('cash', $pelunasan->method);
    }

    public function test_custom_price_cannot_lower_base_price(): void
    {
        $sched = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $longTrim = Service::create([
            'name' => 'Long Trim',
            'code' => 'LT',
            'price' => 80000,
            'category' => 'Haircut',
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'schedule_id' => $sched->id,
            'barber_id' => $this->barber1->id,
            'name' => 'Walkin LT Diskon Ilegal',
            'source' => 'walk_in',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
            'total_amount' => 80000,
            'outstanding_amount' => 80000,
        ]);

        // Attempting to send custom price 50000 (below base 80000)
        $response = $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids' => [$longTrim->id],
            'custom_prices' => [
                $longTrim->id => 50000,
            ],
            'payment_type' => 'full',
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        // Price should remain at base price (80000), not lowered to 50000
        $this->assertEquals(80000, $booking->total_amount);
        $item = $booking->items()->where('service_id', $longTrim->id)->first();
        $this->assertEquals(80000, $item->price_snapshot);
    }

    public function test_complete_booking_with_tip_preserves_tip_and_calculates_correct_settlement(): void
    {
        $sched = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        // Customer booked online: Service 50000 + Tip 10000 = Total Paid 60000
        $payment = Payment::create([
            'amount' => 60000,
            'method' => 'qris_doku',
            'provider' => 'doku',
            'purpose' => 'full_payment',
            'status' => 'paid',
        ]);

        $booking = Booking::create([
            'schedule_id' => $sched->id,
            'barber_id' => $this->barber1->id,
            'payment_id' => $payment->id,
            'name' => 'Budi Beri Tip',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'scheduled_at' => $this->today . ' 10:00:00',
            'total_amount' => 50000,
            'tip_amount' => 10000,
            'outstanding_amount' => 0,
        ]);

        // At barbershop, customer adds service2 (25000). Total service = 75000.
        // Grand total with tip = 75000 + 10000 = 85000.
        // Prepaid online was 60000. Remaining balance to pay = 25000.
        $response = $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids' => [$this->service1->id, $this->service2->id],
            'settlement_payment_method' => 'cash',
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
        $this->assertEquals(75000, $booking->total_amount);
        $this->assertEquals(10000, $booking->tip_amount);
        $this->assertEquals(85000, $booking->grand_total);
        $this->assertEquals(0, $booking->outstanding_amount);

        // Check that pelunasan is exactly 25000 (not reduced by the barber's tip)
        $pelunasan = Payment::where('purpose', 'pelunasan')->first();
        $this->assertNotNull($pelunasan);
        $this->assertEquals(25000, $pelunasan->amount);
        $this->assertEquals('cash', $pelunasan->method);
    }
}
