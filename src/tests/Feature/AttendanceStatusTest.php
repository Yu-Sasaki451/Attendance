<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    //DBを毎回フレッシュする
    use RefreshDatabase;

    //setupで使う箱
    private $user;

    protected function setUp(): void
    {
        //テスト環境を初期化
        parent::setUp();

        //ユーザーを1人作成
        $this->user = $this->createRoleUser();
    }

    public function test_勤務外の場合_勤怠ステータスが勤務外と表示(): void
    {
        //ユーザーログイン、打刻画面に移動
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合_勤怠ステータスが出勤中と表示(): void
    {
        //ユーザーは1時間前に出勤
        $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHour(1),
        ]);

        //ログイン後、打刻画面に移動
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合_勤怠ステータスが休憩中と表示(): void
    {
        //ユーザーは2時間前に出勤してる
        $attendance = $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHours(2),
        ]);

        //ユーザーは30分前から休憩してる
        $this->createBreakTimeFor($attendance, [
            'in_at' => now()->subMinutes(30),
        ]);

        //ユーザーログイン、打刻画面に移動
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合_勤怠ステータスが退勤済と表示(): void
    {
        //ユーザーは9時間前に出勤して、退勤は今
        $this->createAttendanceFor($this->user, [
            'in_at' => now()->subHours(9),
            'out_at' => now(),
        ]);

        //ユーザーログイン、打刻画面に移動
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('退勤済');
    }
}
