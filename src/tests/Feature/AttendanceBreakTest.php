<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
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
        $fixedNow = Carbon::create(2026, 3, 02, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        //ユーザーを1人作成
        $this->user = $this->createRoleUser();

        //出勤情報を作成
        $this->attendance = $this->createAttendance($this->user,[
            'in_at' => '2026-03-02 09:00'
        ]);

    }

    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_休憩ボタン正しく機能(): void
    {
        //打刻画面に移動、表示を確認、休憩入ボタンがあることを確認
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        //休憩の処理実行、リダイレクト確認
        $this->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        //打刻画面に移動、休憩中の表示確認
        $response = $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中');
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        //休憩処理実行、リダイレクト確認
        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        //休憩終了処理実行、リダイレクト確認
        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        //打刻画面に移動、出勤中の表示と休憩入ボタンを確認
        $response = $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中')
            ->assertSee('休憩入');
    }

    public function test_休憩戻ボタンが正しく機能(): void
    {
        //打刻画面に移動、休憩入処理を実行、リダイレクト確認
        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        //打刻画面に移動、休憩戻のボタンがあるの確認
        $response = $this->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        //休憩終了の処理を実行、リダイレクト確認
        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        //打刻画面に移動、出勤中の表示を確認
        $response = $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤中');
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        //休憩開始の処理を行う、リダイレクト確認
        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        //休憩終了の処理を行う、リダイレクト確認
        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        //2回目の休憩開始処理を行う、リダイレクト確認
        $this->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        //打刻画面に移動、休憩中の表示と休憩戻ボタンを確認
        $response = $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中')
            ->assertSee('休憩戻');
    }

    public function test_休憩時刻を勤怠一覧画面で確認(): void
    {
        //休憩開始と終了時間を作成
        $breakStart = Carbon::create(2026, 3, 2, 12, 0, 0, 'Asia/Tokyo');
        $breakEnd = Carbon::create(2026, 3, 2, 13, 0, 0, 'Asia/Tokyo');


        //休憩開始時間をCarbonでセット、休憩開始処理を実行
        Carbon::setTestNow($breakStart);
        $this->actingAs($this->user)
            ->post('/attendance/break-start')
            ->assertRedirect('/attendance');

        //休憩終了時間をCarbonでセット、休憩終了処理を実行
        Carbon::setTestNow($breakEnd);
        $this->post('/attendance/break-end')
            ->assertRedirect('/attendance');

        //勤怠一覧画面に移動、休憩時間
        $response = $this->get('/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('03/02 (月)');
        $response->assertSee('1:00');
    }
}
