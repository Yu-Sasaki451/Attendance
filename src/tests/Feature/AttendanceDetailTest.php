<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    //DBを毎回フレッシュ
    use RefreshDatabase;

    //セットアップで使う箱
    private $user;
    private $attendance;

    protected function setUp(): void
    {
        //テスト環境を初期化
        parent::setUp();

        //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        //ユーザーを1人作成
        $this->user = $this->createRoleUser([
            'name' => '山田 太郎'
        ]);

        $this->attendance = $this->createAttendance($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);
    }

    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_勤怠詳細画面の名前がログインユーザー氏名になる(): void
    {
            $response = $this->actingAs($this->user)->get('/attendance/detail/' . $this->attendance->id);
        $response->assertStatus(200);

        $response->assertSee('名前');
        $response->assertSee('山田 太郎');
    }

    public function test_勤怠詳細画面の日付が選択した日付になってる(): void
    {

        $response = $this->actingAs($this->user)->get('/attendance/detail/' . $this->attendance->id);
        $response->assertStatus(200);

        $response->assertSee('日付');
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
    }

    public function test_勤怠詳細画面の出退勤時刻が打刻通り(): void
    {

        $response = $this->actingAs($this->user)->get('/attendance/detail/' . $this->attendance->id);
        $response->assertStatus(200);

        $response->assertSee('出勤・退勤');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_勤怠詳細画面の休憩時刻が打刻通り(): void
    {
        $attendance = $this->attendance;

        $this->createBreakTime($attendance, [
            'in_at' => '2026-03-10 10:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/detail/' . $this->attendance->id);
        $response->assertStatus(200);

        $response->assertSee('休憩');
        $response->assertSee('10:00');
        $response->assertSee('12:00');
    }
}
