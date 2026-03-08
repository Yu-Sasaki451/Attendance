<?php

namespace Tests\Feature;

use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合_勤怠ステータスが勤務外と表示(): void
    {
        $user = $this->createRoleUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合_勤怠ステータスが出勤中と表示(): void
    {
        $user = $this->createRoleUser();

        $this->createAttendanceFor($user, [
            'in_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合_勤怠ステータスが休憩中と表示(): void
    {
        $user = $this->createRoleUser();

        $attendance = $this->createAttendanceFor($user, [
            'in_at' => now()->subHours(2),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'in_at' => now()->subMinutes(30),
            'out_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合_勤怠ステータスが退勤済と表示(): void
    {
        $user = $this->createRoleUser();

        $this->createAttendanceFor($user, [
            'in_at' => now()->subHours(9),
            'out_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
}
