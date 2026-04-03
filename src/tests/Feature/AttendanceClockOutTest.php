<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
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

    public function test_退勤ボタンが正しく機能()
    {
        //1時間前に出勤したよ
        $this->createAttendance($this->user, [
            'in_at' => now()->subHour(1),
        ]);

        //打刻画面に移動、表示確認
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);

        //退勤ボタンがあるの確認
        $response->assertSee('退勤');

        //退勤処理をする、リダイレクトされるか確認
        $response = $this->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

        //打刻画面に移動して退勤済が表示されてるか確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }

    public function test_出勤と退勤時刻を一覧で確認()
    {
        //打刻画面に移動、表示確認
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);

        //出勤処理をする、リダイレクトされるか確認
        $this->actingAs($this->user)
            ->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        //退勤処理用の時間を作成、Carbonでセット
        $clockOut = Carbon::create(2026, 3, 2, 18, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($clockOut);

        //退勤処理をする、リダイレクト確認
        $response = $this->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

        //打刻画面に移動、表示確認
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);

        //日付と退勤時刻を確認
        $response->assertSee('03/02 (月)');
        $response->assertSee('18:00');
    }
}
