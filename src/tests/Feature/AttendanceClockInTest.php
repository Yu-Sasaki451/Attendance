<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤機能(): void
    {
        $user = $this->createRoleUser();

        $attendancePage = $this->actingAs($user)->get('/attendance');

        $attendancePage->assertStatus(200);
        $attendancePage->assertSee('出勤');

        $this->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }

    public function test_出勤は1日1回(): void
    {
        $user = $this->createRoleUser();

        $this->createAttendanceFor($user, [
            'in_at' => now()->subHours(9),
            'out_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
        $response->assertDontSee('出勤');
    }

    public function test_出勤時刻を勤怠一覧画面で確認(): void
    {
        $user = $this->createRoleUser();

        $fixedNow = Carbon::create(2026, 3, 8, 9, 5, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $this->actingAs($user)
            ->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($fixedNow->format('m/d') . '(' . $this->jpWeekday($fixedNow->dayOfWeek) . ')');
        $response->assertSee($fixedNow->format('H:i'));

        Carbon::setTestNow();
    }
}
