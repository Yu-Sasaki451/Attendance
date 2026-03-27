<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createRoleUser();
    }

    public function test_勤怠詳細画面の名前がログインユーザー氏名になる(): void
    {
        $user = $this->createRoleUser([
            'name' => '山田 太郎',
        ]);

        $attendance = $this->createAttendanceFor($user);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('名前');
        $response->assertSee('山田 太郎');
    }

    public function test_勤怠詳細画面の日付が選択した日付になってる(): void
    {
        $fixedNow = Carbon::create(2026, 3, 8, 9, 5, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $attendance = $this->createAttendanceFor($this->user);

        $response = $this->actingAs($this->user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('日付');
        $response->assertSee($fixedNow->format('Y年'));
        $response->assertSee($fixedNow->format('n月j日'));

        Carbon::setTestNow();
    }

    public function test_勤怠詳細画面の出退勤時刻が打刻通り(): void
    {
        $clockInAt = Carbon::create(2026, 3, 8, 9, 5, 0, 'Asia/Tokyo');
        $clockOutAt = Carbon::create(2026, 3, 8, 18, 30, 0, 'Asia/Tokyo');

        $attendance = $this->createAttendanceFor($this->user, [
            'date' => $clockInAt->toDateString(),
            'in_at' => $clockInAt,
            'out_at' => $clockOutAt,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('出勤・退勤');
        $response->assertSee($clockInAt->format('H:i'));
        $response->assertSee($clockOutAt->format('H:i'));
    }

    public function test_勤怠詳細画面の休憩時刻が打刻通り(): void
    {
        $clockInAt = Carbon::create(2026, 3, 8, 9, 0, 0, 'Asia/Tokyo');
        $breakStartAt = Carbon::create(2026, 3, 8, 12, 34, 0, 'Asia/Tokyo');
        $breakEndAt = Carbon::create(2026, 3, 8, 12, 56, 0, 'Asia/Tokyo');

        $attendance = $this->createAttendanceFor($this->user, [
            'date' => $clockInAt->toDateString(),
            'in_at' => $clockInAt,
        ]);

        $this->createBreakTimeFor($attendance, [
            'in_at' => $breakStartAt,
            'out_at' => $breakEndAt,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('休憩');
        $response->assertSee($breakStartAt->format('H:i'));
        $response->assertSee($breakEndAt->format('H:i'));
    }
}
