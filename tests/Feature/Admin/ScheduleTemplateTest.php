<?php

namespace Tests\Feature\Admin;

use App\Models\Barber;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Barber $barber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->barber = Barber::create([
            'name' => 'Rizal',
            'role' => 'owner',
            'phone' => '081234567890',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_save_schedule_template(): void
    {
        $response = $this->postJson(route('admin.schedules.save-template'), [
            'templates' => [
                $this->barber->id => [
                    'days' => [
                        0 => ['10:00', '11:00'],
                    ],
                ],
            ],
        ]);

        $response->assertUnauthorized();
    }

    public function test_admin_can_view_schedules_page_with_template_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.schedules.index'));

        $response->assertOk()
            ->assertViewHas('templateData')
            ->assertViewHas('templateBarbers');
    }

    public function test_admin_can_save_schedule_template_via_ajax(): void
    {
        $newTemplateDays = [
            0 => ['14:00', '15:00', '16:00'],
            1 => ['10:00', '11:00'],
            2 => [],
            3 => ['13:00'],
            4 => ['09:00', '10:00'],
            5 => ['11:00', '12:00'],
            6 => ['14:00'],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.schedules.save-template'), [
                'templates' => [
                    $this->barber->id => [
                        'active' => true,
                        'days' => $newTemplateDays,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Template jadwal mingguan berhasil disimpan!',
            ]);

        $this->barber->refresh();
        $this->assertNotNull($this->barber->schedule_template);
        $this->assertEquals(['14:00', '15:00', '16:00'], $this->barber->getWeeklyTemplate()[0]);
        $this->assertEquals(['10:00', '11:00'], $this->barber->getWeeklyTemplate()[1]);
    }

    public function test_save_template_validates_request(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.schedules.save-template'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['templates']);
    }

    public function test_apply_template_also_synchronizes_saved_template_on_barber(): void
    {
        $customDays = [
            0 => ['10:00', '11:00'],
            1 => ['10:00', '11:00'],
            2 => ['10:00'],
            3 => ['10:00'],
            4 => ['10:00'],
            5 => ['10:00'],
            6 => ['10:00'],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.schedules.apply-template'), [
                'week' => now()->startOfWeek()->toDateString(),
                'template_data' => json_encode([
                    $this->barber->id => [
                        'active' => true,
                        'days' => $customDays,
                    ],
                ]),
                'clean_unbooked' => 1,
            ]);

        $response->assertRedirect();
        $this->barber->refresh();

        // Verifikasi bahwa slot jadwal tercipta di tabel schedules
        $this->assertGreaterThan(0, Schedule::where('barber_id', $this->barber->id)->count());

        $firstSchedule = Schedule::where('barber_id', $this->barber->id)->first();
        $this->assertNotNull($firstSchedule);
        $this->assertEquals('10:00', $firstSchedule->slot_time->format('H:i'));

        // Verifikasi template barber di DB juga tersinkronisasi
        $this->assertEquals(['10:00', '11:00'], $this->barber->getWeeklyTemplate()[0]);
    }
}
