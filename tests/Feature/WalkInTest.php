<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\BlockedSlot;
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
            $mock->shouldReceive('bookingReminder')->zeroOrMoreTimes();
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

    public function test_register_now_walkin_triggers_ops_group_notification_at_creation(): void
    {
        $mock = $this->mock(BookingNotificationService::class);
        $mock->shouldReceive('sendDailyRecapToOpsGroup')
            ->with($this->today)
            ->once();

        $response = $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Walkin Customer Now',
            'booking_type' => 'now',
        ]);

        $response->assertRedirect(route('walkin.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'name' => 'Walkin Customer Now',
            'source' => 'walk_in',
            'barber_id' => $this->barber1->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_register_scheduled_walkin_triggers_ops_group_notification_at_creation(): void
    {
        $mock = $this->mock(BookingNotificationService::class);
        $mock->shouldReceive('bookingReminder')->zeroOrMoreTimes();
        $mock->shouldReceive('sendDailyRecapToOpsGroup')
            ->with($this->today)
            ->once();

        $response = $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Walkin Customer Scheduled',
            'booking_type' => 'scheduled',
            'scheduled_time' => '14:00',
            'phone' => '081234567890',
            'payment_type' => 'full',
        ]);

        $response->assertRedirect(route('walkin.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'name' => 'Walkin Customer Scheduled',
            'source' => 'walk_in',
            'barber_id' => $this->barber1->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_now_walkin_triggers_notification_on_registration_but_not_on_completion(): void
    {
        $mock = $this->mock(BookingNotificationService::class);
        $mock->shouldReceive('sendDailyRecapToOpsGroup')
            ->with($this->today)
            ->once();

        // 1. Didaftarkan (Notifikasi ke-1)
        $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Walkin Customer No Spam Notif',
            'booking_type' => 'now',
        ])->assertRedirect(route('walkin.index'));

        $booking = Booking::where('name', 'Walkin Customer No Spam Notif')->firstOrFail();

        // 2. Diselesaikan (Tidak mengirim notifikasi ke grup)
        $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids'    => [$this->service1->id],
            'payment_type'   => 'full',
            'payment_method' => 'cash',
        ])->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
    }

    public function test_manual_send_recap_button_triggers_ops_group_notification(): void
    {
        $mock = $this->mock(BookingNotificationService::class);
        $mock->shouldReceive('sendDailyRecapToOpsGroup')
            ->with($this->today)
            ->once();

        $response = $this->post(route('walkin.send-recap'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Rekapan hari ini berhasil dikirim ke grup WhatsApp.');
    }

    public function test_register_scheduled_walkin_with_chemical_locks_two_slots_and_creates_blocked_slot(): void
    {
        $sched14 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '14:00:00',
            'is_available' => true,
        ]);

        $sched15 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '15:00:00',
            'is_available' => true,
        ]);

        $response = $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Budi Chemical',
            'booking_type' => 'scheduled',
            'scheduled_time' => '14:00',
            'phone' => '081234567890',
            'payment_type' => 'full',
            'is_chemical' => 1,
        ]);

        $response->assertRedirect(route('walkin.index'));
        $response->assertSessionHas('success');

        $sched14->refresh();
        $sched15->refresh();
        $this->assertFalse((bool) $sched14->is_available);
        $this->assertFalse((bool) $sched15->is_available);

        $booking = Booking::where('name', 'Budi Chemical')->firstOrFail();
        $this->assertEquals($sched14->id, $booking->schedule_id);

        $blockedSlot = BlockedSlot::where('booking_id', $booking->id)->first();
        $this->assertNotNull($blockedSlot);
        $this->assertEquals($sched15->id, $blockedSlot->schedule_id);
        $this->assertEquals(BlockedSlot::REASON_CHEMICAL, $blockedSlot->reason);
    }

    public function test_register_scheduled_walkin_with_chemical_fails_if_next_slot_already_taken(): void
    {
        Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '14:00:00',
            'is_available' => true,
        ]);

        // Slot 15:00 sudah terisi
        Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '15:00:00',
            'is_available' => false,
        ]);

        $response = $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Budi Chemical Bentrok',
            'booking_type' => 'scheduled',
            'scheduled_time' => '14:00',
            'phone' => '081234567890',
            'payment_type' => 'full',
            'is_chemical' => 1,
        ]);

        $response->assertSessionHasErrors('scheduled_time');
        $this->assertDatabaseMissing('bookings', [
            'name' => 'Budi Chemical Bentrok',
        ]);
    }

    public function test_cancel_walkin_releases_both_primary_and_blocked_slots(): void
    {
        $sched14 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '14:00:00',
            'is_available' => true,
        ]);

        $sched15 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '15:00:00',
            'is_available' => true,
        ]);

        $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Budi Batal Chemical',
            'booking_type' => 'scheduled',
            'scheduled_time' => '14:00',
            'phone' => '081234567890',
            'payment_type' => 'full',
            'is_chemical' => 1,
        ])->assertRedirect(route('walkin.index'));

        $booking = Booking::where('name', 'Budi Batal Chemical')->firstOrFail();
        $this->assertEquals(1, BlockedSlot::where('booking_id', $booking->id)->count());

        // Batalkan booking
        $response = $this->delete(route('walkin.cancel', ['booking' => $booking->id]));
        $response->assertSessionHas('success');

        $booking->refresh();
        $sched14->refresh();
        $sched15->refresh();

        $this->assertEquals('cancelled', $booking->status);
        $this->assertTrue((bool) $sched14->is_available);
        $this->assertTrue((bool) $sched15->is_available);
        $this->assertEquals(0, BlockedSlot::where('booking_id', $booking->id)->count());
    }

    public function test_register_now_walkin_with_chemical_locks_next_chronological_slot(): void
    {
        Carbon::setTestNow(Carbon::parse($this->today . ' 12:37:00'));

        $sched1320 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '13:20:00',
            'is_available' => true,
        ]);

        $response = $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Novan Walkin Chemical',
            'booking_type' => 'now',
            'is_chemical' => 1,
        ]);

        $response->assertRedirect(route('walkin.index'));
        $response->assertSessionHas('success');

        $sched1320->refresh();
        $this->assertFalse((bool) $sched1320->is_available);

        $booking = Booking::where('name', 'Novan Walkin Chemical')->firstOrFail();
        $blockedSlot = BlockedSlot::where('booking_id', $booking->id)->first();
        $this->assertNotNull($blockedSlot);
        $this->assertEquals($sched1320->id, $blockedSlot->schedule_id);
        $this->assertEquals(BlockedSlot::REASON_CHEMICAL, $blockedSlot->reason);

        Carbon::setTestNow();
    }

    public function test_register_scheduled_walkin_with_chemical_locks_next_slot_across_break_gap(): void
    {
        $sched1520 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '15:20:00',
            'is_available' => true,
        ]);

        $sched1700 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '17:00:00',
            'is_available' => true,
        ]);

        $response = $this->post(route('walkin.store'), [
            'barber_id' => $this->barber1->id,
            'name' => 'Walkin Break Gap Chem',
            'booking_type' => 'scheduled',
            'scheduled_time' => '15:20',
            'phone' => '081234567890',
            'payment_type' => 'full',
            'is_chemical' => 1,
        ]);

        $response->assertRedirect(route('walkin.index'));
        $response->assertSessionHas('success');

        $sched1520->refresh();
        $sched1700->refresh();
        $this->assertFalse((bool) $sched1520->is_available);
        $this->assertFalse((bool) $sched1700->is_available);

        $booking = Booking::where('name', 'Walkin Break Gap Chem')->firstOrFail();
        $blockedSlot = BlockedSlot::where('booking_id', $booking->id)->first();
        $this->assertNotNull($blockedSlot);
        $this->assertEquals($sched1700->id, $blockedSlot->schedule_id);
        $this->assertEquals(BlockedSlot::REASON_CHEMICAL, $blockedSlot->reason);
    }

    public function test_scheduled_walkin_chemical_validation_failure_preserves_form_inputs_in_session_and_view(): void
    {
        $sched1520 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '15:20:00',
            'is_available' => true,
        ]);

        $sched1700 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '17:00:00',
            'is_available' => false, // next slot is already taken
        ]);

        $response = $this->from(route('walkin.index'))
            ->followingRedirects()
            ->post(route('walkin.store'), [
                'barber_id' => $this->barber1->id,
                'name' => 'Budi Preserved',
                'booking_type' => 'scheduled',
                'scheduled_time' => '15:20',
                'phone' => '81234567890',
                'is_chemical' => 1,
                'payment_type' => 'dp',
                'dp_amount' => 50000,
                'dp_payment_method' => 'cash',
            ]);

        $response->assertOk();
        $response->assertSee('Slot jadwal berikutnya (17:00) untuk layanan chemical sudah terisi. Silakan pilih jam lain.');

        // Blade's @js directive encodes quotes as \u0022 inside JSON.parse('...')
        $response->assertSee('\u0022bookingType\u0022:\u0022scheduled\u0022', false);
        $response->assertSee('\u0022selectedScheduledTime\u0022:\u002215:20\u0022', false);
        $response->assertSee('\u0022customerName\u0022:\u0022Budi Preserved\u0022', false);
        $response->assertSee('\u0022customerPhone\u0022:\u002281234567890\u0022', false);
        $response->assertSee('\u0022isChemical\u0022:true', false);
        $response->assertSee('\u0022scheduledPaymentType\u0022:\u0022dp\u0022', false);
        $response->assertSee('\u0022scheduledDpAmount\u0022:50000', false);
    }

    public function test_complete_booking_preserves_customer_description_and_saves_barber_notes(): void
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
            'name' => 'John Customer',
            'phone' => '081234567890',
            'description' => 'Original Customer Note (Jangan pendek)',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'total_amount' => 50000,
            'outstanding_amount' => 0,
            'scheduled_at' => $this->today . ' 10:00:00',
        ]);

        $payment = Payment::create([
            'amount' => 50000,
            'method' => 'qris_static',
            'purpose' => 'full_payment',
            'status' => 'paid',
        ]);
        $booking->update(['payment_id' => $payment->id]);

        // Lengkapi booking dengan mengisi barber_notes
        $response = $this->patch(route('walkin.complete', ['booking' => $booking->id]), [
            'service_ids' => [$this->service1->id],
            'barber_notes' => 'Catatan pengerjaan barber (Rambut tebal)',
        ]);

        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertEquals('completed', $booking->status);
        // Customer description tetap utuh tidak tertimpa
        $this->assertEquals('Original Customer Note (Jangan pendek)', $booking->description);
        // Barber notes tersimpan dengan benar
        $this->assertEquals('Catatan pengerjaan barber (Rambut tebal)', $booking->barber_notes);
    }

    public function test_ops_recap_message_includes_booking_notes_when_present_and_omits_when_empty(): void
    {
        $sched1 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '10:00:00',
            'is_available' => false,
        ]);

        $sched2 = Schedule::create([
            'barber_id' => $this->barber1->id,
            'date' => $this->today,
            'slot_time' => '11:00:00',
            'is_available' => false,
        ]);

        $booking1 = Booking::create([
            'schedule_id' => $sched1->id,
            'barber_id' => $this->barber1->id,
            'name' => 'John With Note',
            'phone' => '081234567890',
            'description' => 'Minta potong rapi',
            'barber_notes' => 'Grooming selesai',
            'source' => 'online',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'total_amount' => 50000,
            'outstanding_amount' => 0,
            'scheduled_at' => $this->today . ' 10:00:00',
        ]);

        $booking2 = Booking::create([
            'schedule_id' => $sched2->id,
            'barber_id' => $this->barber1->id,
            'name' => 'Budi No Note',
            'phone' => '081234567891',
            'description' => null,
            'barber_notes' => null,
            'source' => 'walk_in',
            'status' => 'confirmed',
            'payment_type' => 'full',
            'total_amount' => 50000,
            'outstanding_amount' => 0,
            'scheduled_at' => $this->today . ' 11:00:00',
        ]);

        $capturedMessage = '';
        $wahaMock = $this->mock(\App\Services\WahaService::class);
        $wahaMock->shouldReceive('sendMessage')
            ->once()
            ->andReturnUsing(function ($phone, $message) use (&$capturedMessage) {
                $capturedMessage = $message;
            });

        // Unbind mock BookingNotificationService to test real BookingNotificationService with mocked WahaService
        $this->app->forgetInstance(BookingNotificationService::class);
        $service = app(BookingNotificationService::class);
        $service->sendDailyRecapToOpsGroup($this->today);

        $this->assertNotEmpty($capturedMessage);
        // Booking 1 memuat catatan
        $this->assertStringContainsString('(catatan: Minta potong rapi | barber: Grooming selesai)', $capturedMessage);
        // Booking 2 TIDAK memuat catatan
        $this->assertStringNotContainsString('budi no note (catatan:', strtolower($capturedMessage));
    }
}

