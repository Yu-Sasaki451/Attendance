<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createRoleUser();
    }

    public function test_休憩ボタン正しく機能(): void
    {
        $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHour(),
        ]);

        $attendancePage = $this->actingAs($this->user)->get('/attendance');

        $attendancePage->assertStatus(200);
        $attendancePage->assertSee('休憩入');

        $this->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中');
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHours(2),
        ]);

        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中')
            ->assertSee('休憩入');
    }

    public function test_休憩戻ボタンが正しく機能(): void
    {
        $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHours(2),
        ]);

        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        $breakPage = $this->get('/attendance');
        $breakPage->assertStatus(200);
        $breakPage->assertSee('休憩戻');

        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHours(3),
        ]);

        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        $this->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中')
            ->assertSee('休憩戻');
    }

    public function test_休憩時刻を勤怠一覧画面で確認(): void
    {
        $clockInAt = Carbon::create(2026, 3, 8, 9, 0, 0, 'Asia/Tokyo');
        $breakStartAt = Carbon::create(2026, 3, 8, 12, 0, 0, 'Asia/Tokyo');
        $breakEndAt = Carbon::create(2026, 3, 8, 12, 30, 0, 'Asia/Tokyo');

        $this->createAttendanceFor($this->user, [
            'date' => $clockInAt->toDateString(),
            'in_at' => $clockInAt,
        ]);

        Carbon::setTestNow($breakStartAt);
        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        Carbon::setTestNow($breakEndAt);
        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($clockInAt->format('m/d') . '(' . $this->jpWeekday($clockInAt->dayOfWeek) . ')');
        $response->assertSee('0:30');

        Carbon::setTestNow();
    }
}
