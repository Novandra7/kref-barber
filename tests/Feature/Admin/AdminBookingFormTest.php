<?php

namespace Tests\Feature\Admin;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_booking_create_and_edit_forms_render_cleanly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $barber = Barber::create([
            'name'      => 'Sogi',
            'role'      => 'Senior Barber',
            'phone'     => '081234567891',
            'is_active' => true,
        ]);
        $service = Service::create([
            'name'      => 'Regular Haircut',
            'code'      => 'RH',
            'price'     => 60000,
            'category'  => 'Haircut',
            'is_active' => true,
        ]);

        $date = Carbon::tomorrow()->toDateString();
        $schedule = Schedule::create([
            'barber_id'    => $barber->id,
            'date'         => $date,
            'slot_time'    => '10:00:00',
            'is_available' => false,
        ]);

        $booking = Booking::create([
            'schedule_id'        => $schedule->id,
            'barber_id'          => $barber->id,
            'name'               => 'Customer Test',
            'phone'              => '81234567890',
            'source'             => 'walk_in',
            'payment_type'       => 'full',
            'status'             => 'confirmed',
            'total_amount'       => $service->price,
            'outstanding_amount' => 0,
            'scheduled_at'       => $date . ' 10:00:00',
        ]);

        $booking->items()->create([
            'item_type'             => 'service',
            'service_id'            => $service->id,
            'qty'                   => 1,
            'service_name_snapshot' => $service->name,
            'price_snapshot'        => $service->price,
        ]);

        $this->actingAs($admin)
            ->get('http://admin.kref.test/bookings/create')
            ->assertOk()
            ->assertSee('x-data="adminBookingForm(', false)
            ->assertSee('id="time-rule-feedback"', false)
            ->assertSee(':disabled="timeRuleState.hasConflict"', false)
            ->assertSee('timelineSchedules', false)
            ->assertSee('slotRules', false);

        $this->actingAs($admin)
            ->get("http://admin.kref.test/bookings/{$booking->id}/edit")
            ->assertOk()
            ->assertSee('x-data="adminBookingForm(', false)
            ->assertSee('id="time-rule-feedback"', false)
            ->assertSee(':disabled="timeRuleState.hasConflict"', false)
            ->assertSee('10:00');
    }
}
