<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\BlockedSlot;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceSlotRule;
use App\Models\User;
use App\Services\BookingNotificationService;
use App\Services\DokuService;
use App\Services\SlotRuleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeRuleTest extends TestCase
{
    use RefreshDatabase;

    private Barber $sogi;
    private Barber $rizal;
    private Service $longTrim;
    private Service $regularHaircut;
    private Service $chemical;
    private Service $treatment;
    private string $futureDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(BookingNotificationService::class, function ($mock) {
            $mock->shouldReceive('bookingCreated')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingConfirmationCustomer')->zeroOrMoreTimes();
            $mock->shouldReceive('sendCustomerPaymentDetailsNotification')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingReminder')->zeroOrMoreTimes();
            $mock->shouldReceive('bookingRescheduled')->zeroOrMoreTimes();
            $mock->shouldReceive('paymentSucceeded')->zeroOrMoreTimes();
            $mock->shouldReceive('sendDailyRecapToOpsGroup')->zeroOrMoreTimes();
        });

        $this->mock(DokuService::class, function ($mock) {
            $mock->shouldReceive('createQrisPayment')->andReturn([
                'qrContent'   => 'dummy-qris-content',
                'referenceNo' => 'DOKU-RULE-12345',
                'paymentUrl'  => 'https://example.com/pay',
            ]);
        });

        $this->sogi = Barber::create([
            'name'      => 'Sogi',
            'role'      => 'Senior Barber',
            'phone'     => '081234567891',
            'is_active' => true,
        ]);

        $this->rizal = Barber::create([
            'name'      => 'Rizal',
            'role'      => 'Owner',
            'phone'     => '081234567892',
            'is_active' => true,
        ]);

        $this->longTrim = Service::create([
            'name'      => 'Long Trim',
            'code'      => 'LT',
            'price'     => 80000,
            'category'  => 'Haircut',
            'is_active' => true,
        ]);

        $this->regularHaircut = Service::create([
            'name'      => 'Regular Haircut',
            'code'      => 'RH',
            'price'     => 60000,
            'category'  => 'Haircut',
            'is_active' => true,
        ]);

        $this->chemical = Service::create([
            'name'      => 'Coloring',
            'code'      => 'CL',
            'price'     => 150000,
            'category'  => 'Chemical',
            'is_active' => true,
        ]);

        $this->treatment = Service::create([
            'name'      => 'Hair Spa',
            'code'      => 'HS',
            'price'     => 50000,
            'category'  => 'Treatment',
            'is_active' => true,
        ]);

        $sogiRule = ServiceSlotRule::create([
            'name'           => 'Long Trim (Khusus Sogi)',
            'barber_id'      => $this->sogi->id,
            'slots_required' => 2,
            'is_active'      => true,
        ]);
        $sogiRule->items()->create([
            'service_id' => $this->longTrim->id,
            'category'   => null,
        ]);

        $chemHaircutRule = ServiceSlotRule::create([
            'name'           => 'Kombinasi Chemical + Haircut (Semua Barber)',
            'barber_id'      => null,
            'slots_required' => 2,
            'is_active'      => true,
        ]);
        $chemHaircutRule->items()->createMany([
            ['service_id' => null, 'category' => 'Chemical'],
            ['service_id' => null, 'category' => 'Haircut'],
        ]);

        $chemTreatmentRule = ServiceSlotRule::create([
            'name'           => 'Kombinasi Chemical + Treatment (Semua Barber)',
            'barber_id'      => null,
            'slots_required' => 2,
            'is_active'      => true,
        ]);
        $chemTreatmentRule->items()->createMany([
            ['service_id' => null, 'category' => 'Chemical'],
            ['service_id' => null, 'category' => 'Treatment'],
        ]);

        $this->futureDate = Carbon::tomorrow()->toDateString();
    }

    public function test_slot_rule_service_evaluates_item_combinations_and_sum_case_study(): void
    {
        $service = app(SlotRuleService::class);

        // 1. Rule 1 item: Sogi + Long Trim = 2 slot
        $sogiLongTrim = $service->evaluate($this->sogi, [$this->longTrim], 'Budi');
        $this->assertTrue($sogiLongTrim['requires_multi_slot']);
        $this->assertSame(2, $sogiLongTrim['slots_required']);
        $this->assertSame(1, $sogiLongTrim['extra_slots']);

        // 2. Barber lain (Rizal) + Long Trim = 1 slot (tidak cocok rule Sogi)
        $rizalLongTrim = $service->evaluate($this->rizal, [$this->longTrim], 'Budi');
        $this->assertFalse($rizalLongTrim['requires_multi_slot']);
        $this->assertSame(1, $rizalLongTrim['slots_required']);

        // 3. Rule kombinasi 2 item: Chemical saja = 1 slot, Chemical + Haircut/Treatment = 2 slot
        $chemOnly = $service->evaluate($this->rizal, [$this->chemical], 'Budi');
        $this->assertSame(1, $chemOnly['slots_required']);

        $chemHaircut = $service->evaluate($this->rizal, [$this->chemical, $this->regularHaircut], 'Budi');
        $this->assertSame(2, $chemHaircut['slots_required']);

        $chemTreatment = $service->evaluate($this->rizal, [$this->chemical, $this->treatment], 'Budi');
        $this->assertSame(2, $chemTreatment['slots_required']);

        // 4. Studi Kasus Akumulasi (SUM): Sogi + Long Trim (2 slot) + Chemical & Haircut (2 slot) = 4 slot
        $caseStudy = $service->evaluate($this->sogi, [$this->longTrim, $this->chemical], 'Budi');
        $this->assertTrue($caseStudy['requires_multi_slot']);
        $this->assertSame(4, $caseStudy['slots_required']);
        $this->assertSame(3, $caseStudy['extra_slots']);
    }

    public function test_inactive_rule_does_not_lock_extra_slots(): void
    {
        ServiceSlotRule::where('name', 'Long Trim (Khusus Sogi)')->update(['is_active' => false]);

        $eval = app(SlotRuleService::class)->evaluate($this->sogi, [$this->longTrim], 'Budi');
        $this->assertFalse($eval['requires_multi_slot']);
        $this->assertSame(1, $eval['slots_required']);
    }

    public function test_online_checkout_locks_4_consecutive_slots_for_case_study(): void
    {
        foreach (['10:00:00', '10:40:00', '11:20:00', '12:00:00'] as $time) {
            Schedule::create([
                'barber_id'    => $this->sogi->id,
                'date'         => $this->futureDate,
                'slot_time'    => $time,
                'is_available' => true,
            ]);
        }

        $response = $this->postJson('http://kref.test/booking/checkout', [
            'payment_type' => 'DP',
            'guests' => [[
                'name'               => 'Budi Studi Kasus',
                'phone'              => '81234567890',
                'barber'             => (string) $this->sogi->id,
                'date'               => $this->futureDate,
                'time'               => '10:00',
                'selectedHaircut'    => 'Long Trim',
                'selectedChemical'   => 'Coloring',
                'selectedTreatments' => [],
            ]],
        ]);

        $response->assertCreated();

        $booking = Booking::where('name', 'Budi Studi Kasus')->firstOrFail();
        $this->assertCount(3, $booking->blockedSlots);
        $this->assertSame(0, Schedule::where('barber_id', $this->sogi->id)->where('is_available', true)->count());
    }

    public function test_online_checkout_fails_when_4_consecutive_slots_are_not_available(): void
    {
        // Hanya tersedia 3 slot (kurang 1 untuk kebutuhan 4 slot)
        foreach (['10:00:00', '10:40:00', '11:20:00'] as $time) {
            Schedule::create([
                'barber_id'    => $this->sogi->id,
                'date'         => $this->futureDate,
                'slot_time'    => $time,
                'is_available' => true,
            ]);
        }

        $response = $this->postJson('http://kref.test/booking/checkout', [
            'payment_type' => 'DP',
            'guests' => [[
                'name'               => 'Budi Bentrok',
                'phone'              => '81234567890',
                'barber'             => (string) $this->sogi->id,
                'date'               => $this->futureDate,
                'time'               => '10:00',
                'selectedHaircut'    => 'Long Trim',
                'selectedChemical'   => 'Coloring',
                'selectedTreatments' => [],
            ]],
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_crud_and_toggle_inline_time_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('http://admin.kref.test/rules/time')
            ->assertOk()
            ->assertSee('Time Rules')
            ->assertSee('Tambah Time Rule')
            ->assertSee('name="description"', false)
            ->assertSee('Long Trim (Khusus Sogi)');

        // Create new multi-item rule without modal
        $this->actingAs($admin)
            ->post('http://admin.kref.test/rules/time', [
                'name'           => 'Rizal Haircut + Coloring 3 Slot',
                'description'    => 'Butuh 3 slot untuk Rizal Haircut + Coloring.',
                'barber_id'      => $this->rizal->id,
                'items'          => [
                    'service:' . $this->regularHaircut->id,
                    'service:' . $this->chemical->id,
                ],
                'slots_required' => 3,
                'is_active'      => 1,
            ])
            ->assertRedirect('http://admin.kref.test/rules/time');

        $rule = ServiceSlotRule::with('items')->where('name', 'Rizal Haircut + Coloring 3 Slot')->firstOrFail();
        $this->assertSame('Butuh 3 slot untuk Rizal Haircut + Coloring.', $rule->description);
        $this->assertSame(3, $rule->slots_required);
        $this->assertTrue($rule->is_active);
        $this->assertCount(2, $rule->items);

        // Pastikan evaluasi hanya cocok jika kedua layanan dipilih
        $evalSingle = app(SlotRuleService::class)->evaluate($this->rizal, [$this->regularHaircut]);
        $this->assertSame(1, $evalSingle['slots_required']);

        $evalBoth = app(SlotRuleService::class)->evaluate($this->rizal, [$this->regularHaircut, $this->chemical]);
        // Rizal rule (3 slot) + Chemical+Haircut rule (2 slot) = 5 slot
        $this->assertSame(5, $evalBoth['slots_required']);

        // Toggle inactive
        $this->actingAs($admin)
            ->patch("http://admin.kref.test/rules/time/{$rule->id}/toggle")
            ->assertRedirect('http://admin.kref.test/rules/time');

        $this->assertFalse($rule->fresh()->is_active);

        // Update inline to 4 slots and 1 item
        $this->actingAs($admin)
            ->put("http://admin.kref.test/rules/time/{$rule->id}", [
                'name'           => 'Rizal Custom 4 Slot',
                'description'    => 'Deskripsi hasil edit inline.',
                'barber_id'      => $this->rizal->id,
                'items'          => ['service:' . $this->regularHaircut->id],
                'slots_required' => 4,
                'is_active'      => 1,
            ])
            ->assertRedirect('http://admin.kref.test/rules/time');

        $rule->refresh();
        $this->assertSame('Deskripsi hasil edit inline.', $rule->description);
        $this->assertSame(4, $rule->slots_required);
        $this->assertCount(1, $rule->items);

        // Delete rule
        $this->actingAs($admin)
            ->delete("http://admin.kref.test/rules/time/{$rule->id}")
            ->assertRedirect('http://admin.kref.test/rules/time');

        $this->assertDatabaseMissing('service_slot_rules', ['id' => $rule->id]);
    }

    public function test_walkin_supports_multiple_selected_time_rules_sum(): void
    {
        $today = Carbon::today()->toDateString();
        $base = Carbon::now()->addHours(1)->startOfHour();

        foreach ([0, 40, 80, 120] as $minutes) {
            Schedule::create([
                'barber_id'    => $this->sogi->id,
                'date'         => $today,
                'slot_time'    => $base->copy()->addMinutes($minutes)->format('H:i:00'),
                'is_available' => true,
            ]);
        }

        $chemHaircutRule = ServiceSlotRule::where('name', 'like', '%Haircut%')->firstOrFail();
        $sogiLongTrimRule = ServiceSlotRule::where('name', 'Long Trim (Khusus Sogi)')->firstOrFail();

        $response = $this->post('http://kref.test/vv4lk-1n', [
            'barber_id'           => $this->sogi->id,
            'name'                => 'Walkin 4 Slot',
            'booking_type'        => 'scheduled',
            'scheduled_time'      => $base->format('H:i'),
            'phone'               => '81234567899',
            'payment_type'        => 'dp',
            'dp_amount'           => 40000,
            'dp_payment_method'   => 'cash',
            'multi_slot_rule_ids' => [$chemHaircutRule->id, $sogiLongTrimRule->id],
        ]);

        $response->assertRedirect(route('walkin.index'));

        $booking = Booking::where('name', 'Walkin 4 Slot')->firstOrFail();
        $this->assertCount(3, $booking->blockedSlots);
        $this->assertSame(0, Schedule::where('barber_id', $this->sogi->id)->whereDate('date', $today)->where('is_available', true)->count());
    }

    public function test_booking_page_uses_api_init_data_and_returns_slot_rules(): void
    {
        $this->get('http://kref.test/booking')
            ->assertOk()
            ->assertSee('x-data="bookingForm()"', false)
            ->assertSee('disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:opacity-50', false)
            ->assertSee(':disabled="requiresMultiSlot() && isRequiredSlotsBlockedOrTaken()"', false);

        $this->getJson('http://kref.test/api/booking/data')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Long Trim (Khusus Sogi)', 'slots_required' => 2])
            ->assertJsonFragment(['name' => 'Kombinasi Chemical + Haircut (Semua Barber)', 'slots_required' => 2]);
    }

    public function test_admin_reschedule_moves_all_4_consecutive_slots(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $slots = collect(['10:00:00', '10:40:00', '11:20:00', '12:00:00', '14:00:00', '14:40:00', '15:20:00', '16:00:00'])
            ->mapWithKeys(fn (string $time) => [
                $time => Schedule::create([
                    'barber_id'    => $this->sogi->id,
                    'date'         => $this->futureDate,
                    'slot_time'    => $time,
                    'is_available' => true,
                ]),
            ]);

        // Buat booking 4 slot pada jam 10:00
        $this->postJson('http://kref.test/booking/checkout', [
            'payment_type' => 'DP',
            'guests' => [[
                'name'               => 'Budi Reschedule',
                'phone'              => '81234567890',
                'barber'             => (string) $this->sogi->id,
                'date'               => $this->futureDate,
                'time'               => '10:00',
                'selectedHaircut'    => 'Long Trim',
                'selectedChemical'   => 'Coloring',
                'selectedTreatments' => [],
            ]],
        ])->assertCreated();

        $booking = Booking::where('name', 'Budi Reschedule')->firstOrFail();
        $this->assertCount(3, $booking->blockedSlots);

        // Pindahkan ke jam 14:00 (butuh 14:00, 14:40, 15:20, 16:00)
        $this->actingAs($admin)
            ->post('http://admin.kref.test/schedules/reschedule-booking', [
                'booking_id'         => $booking->id,
                'target_schedule_id' => $slots['14:00:00']->id,
                'release_old_slot'   => 1,
            ])
            ->assertRedirect();

        // 4 slot lama harus kembali tersedia
        foreach (['10:00:00', '10:40:00', '11:20:00', '12:00:00'] as $oldTime) {
            $this->assertTrue($slots[$oldTime]->fresh()->is_available, "Old slot {$oldTime} should be released");
        }

        // 4 slot baru harus terkunci
        foreach (['14:00:00', '14:40:00', '15:20:00', '16:00:00'] as $newTime) {
            $this->assertFalse($slots[$newTime]->fresh()->is_available, "New slot {$newTime} should be locked");
        }

        $this->assertCount(3, $booking->fresh()->blockedSlots);
    }

    public function test_admin_walkin_create_locks_next_slot_and_rejects_if_next_slot_already_occupied(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $slot1320 = Schedule::create([
            'barber_id'    => $this->sogi->id,
            'date'         => $this->futureDate,
            'slot_time'    => '13:20:00',
            'is_available' => true,
        ]);
        $slot1400 = Schedule::create([
            'barber_id'    => $this->sogi->id,
            'date'         => $this->futureDate,
            'slot_time'    => '14:00:00',
            'is_available' => true,
        ]);
        $slot1440 = Schedule::create([
            'barber_id'    => $this->sogi->id,
            'date'         => $this->futureDate,
            'slot_time'    => '14:40:00',
            'is_available' => true,
        ]);

        // 1. Buat booking Admin Walk-in untuk Sogi + Long Trim di 14:00 -> mengunci 14:00 & 14:40
        $this->actingAs($admin)
            ->post('http://admin.kref.test/bookings', [
                'name'           => 'Test Admin Rule',
                'phone'          => '81234567890',
                'barber_id'      => $this->sogi->id,
                'date'           => $this->futureDate,
                'time'           => '14:00',
                'service_ids'    => [$this->longTrim->id],
                'payment_type'   => 'full',
                'payment_method' => 'cash',
            ])
            ->assertRedirect('http://admin.kref.test/bookings');

        $this->assertFalse($slot1400->fresh()->is_available);
        $this->assertFalse($slot1440->fresh()->is_available);

        $booking = Booking::where('name', 'Test Admin Rule')->firstOrFail();
        $blocked = BlockedSlot::where('booking_id', $booking->id)->first();
        $this->assertNotNull($blocked);
        $this->assertSame($slot1440->id, $blocked->schedule_id);

        // 2. Coba buat booking Sogi + Long Trim di 13:20 (padahal slot berikutnya 14:00 sudah terisi) -> harus ditolak dengan pesan Time Rule yang jelas
        $response = $this->actingAs($admin)
            ->from('http://admin.kref.test/bookings/create')
            ->post('http://admin.kref.test/bookings', [
                'name'           => 'Test Bentrok',
                'phone'          => '81234567891',
                'barber_id'      => $this->sogi->id,
                'date'           => $this->futureDate,
                'time'           => '13:20',
                'service_ids'    => [$this->longTrim->id],
                'payment_type'   => 'full',
                'payment_method' => 'cash',
            ]);

        $response->assertRedirect('http://admin.kref.test/bookings/create')
            ->assertSessionHasErrors('time');

        $timeError = session('errors')->first('time');
        $this->assertStringContainsString('Bentrok Time Rule', $timeError);
        $this->assertStringContainsString('Long Trim (Khusus Sogi)', $timeError);
        $this->assertStringContainsString('13:20, 14:00 WITA', $timeError);
        $this->assertStringContainsString('14:00 WITA', $timeError);

        $this->actingAs($admin)
            ->get('http://admin.kref.test/bookings/create')
            ->assertOk()
            ->assertSee('Bentrok Time Rule');

        $this->assertTrue($slot1320->fresh()->is_available);
        $this->assertDatabaseMissing('bookings', ['name' => 'Test Bentrok']);

        // 3. Coba buat booking Sogi + Long Trim di slot baru 15:20 tanpa slot lanjutan -> ditolak dengan pesan slot tidak mencukupi
        $slot1520 = Schedule::create([
            'barber_id'    => $this->sogi->id,
            'date'         => $this->futureDate,
            'slot_time'    => '15:20:00',
            'is_available' => true,
        ]);

        $insufficientResponse = $this->actingAs($admin)
            ->from('http://admin.kref.test/bookings/create')
            ->post('http://admin.kref.test/bookings', [
                'name'           => 'Test Slot Kurang',
                'phone'          => '81234567892',
                'barber_id'      => $this->sogi->id,
                'date'           => $this->futureDate,
                'time'           => '15:20',
                'service_ids'    => [$this->longTrim->id],
                'payment_type'   => 'full',
                'payment_method' => 'cash',
            ]);

        $insufficientResponse->assertSessionHasErrors('time');
        $insufficientError = session('errors')->first('time');
        $this->assertStringContainsString('Bentrok Time Rule', $insufficientError);
        $this->assertStringContainsString('tidak mencukupi', $insufficientError);
        $this->assertTrue($slot1520->fresh()->is_available);
    }
}
