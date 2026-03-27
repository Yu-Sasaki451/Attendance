<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createRoleUser();
    }

    public function test_退勤ボタンが正しく機能()
    {
        $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHour(),
        ]);

        $attendancePage = $this->actingAs($this->user)->get('/attendance');

        $attendancePage->assertStatus(200);
        $attendancePage->assertSee('退勤');

        $this->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

            $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤済');
    }

    public function test_出勤と退勤時刻を一覧で確認()
    {
        $clockInAt = Carbon::create(2026, 3, 8, 9, 0, 0, 'Asia/Tokyo');
        $clockOutAt = Carbon::create(2026, 3, 8, 18, 0, 0, 'Asia/Tokyo');

        $this->createAttendanceFor($this->user, [
            'date' => $clockInAt->toDateString(),
            'in_at' => $clockInAt,
            'out_at' => $clockOutAt,
        ]);

        Carbon::setTestNow($clockInAt);
        $this->actingAs($this->user)
            ->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        Carbon::setTestNow($clockOutAt);
        $this->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($clockInAt->format('m/d') . ' ' . '(' . $this->jpWeekday($clockInAt->dayOfWeek) . ')');
        $response->assertSee($clockInAt->format('H:i'));
        $response->assertSee($clockOutAt->format('H:i'));

        Carbon::setTestNow();
    }
}
