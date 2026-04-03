<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
{
   //DBを毎回フレッシュ
    use RefreshDatabase;

    //セットアップで使う箱
    private $user;

    protected function setUp(): void
    {
        //テスト環境を初期化
        parent::setUp();

        //ユーザーを1人作成
        $this->user = $this->createRoleUser();

        //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 02, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);
    }

    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_出勤機能(): void
    {
        //打刻画面に移動、表示を確認、出勤ボタンがあるの確認
        $attendancePage = $this->actingAs($this->user)->get('/attendance');
        $attendancePage->assertStatus(200);
        $attendancePage->assertSee('出勤');

        //出勤処理をする、リダイレクト確認
        $response = $this->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        //打刻画面に移動、出勤中の表示を確認
        $response = $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }

    public function test_出勤は1日1回(): void
    {
        //出勤と退勤情報を作成
        $this->createAttendance($this->user, [
            'in_at' => now(),
            'out_at' => now()->addHour(8),
        ]);

        //打刻画面に移動
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);

        //退勤済の表示があり、出勤ボタンが表示されないの確認
        $response->assertSee('退勤済');
        $response->assertDontSee('出勤');
    }

    public function test_出勤時刻を勤怠一覧画面で確認(): void
    {
        //ログインして出勤処理をする、リダイレクト確認
        $this->actingAs($this->user)
            ->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        //勤怠一覧ページに移動、表示確認
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        //日付と出勤時間確認
        $response->assertSee('03/02 (月)');
        $response->assertSee('09:00');
    }
}
