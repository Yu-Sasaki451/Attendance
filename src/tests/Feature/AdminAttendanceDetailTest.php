<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;
    private $attendance;
    private $breakTime;

    protected function setUp(): void{
         //テスト環境を初期化
        parent::setUp();

        //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 4, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $this->admin = $this->createRoleAdmin();
        $this->user = $this->createRoleUser();

        $this->attendance = $this->createAttendance($this->user,[
            'date' => '2026-03-03',
            'in_at' => '2026-03-03 09:00',
            'out_at' => '2026-03-03 18:00',
        ]);

        $this->breakTime = $this->createBreakTime($this->attendance,[
            'in_at' => '2026-03-03 11:00',
            'out_at' => '2026-03-03 12:00',
        ]);

    }

    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_勤怠詳細の内容が選択したものと一致する(){

        //管理者ログイン、ユーザーの勤怠詳細ページへ移動
        $response = $this->actingAs($this->admin)->get('/admin/attendance/' . $this->attendance->id);
        $response->assertStatus(200);

        $response->assertSee($this->user->name);
        $response->assertSee('2026年');
        $response->assertSee('3月3日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('11:00');
        $response->assertSee('12:00');
    }

    public function test_出勤時間が退勤時間より遅くてエラーメッセージ表示(){

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $this->attendance->id, [
            'in_at' => '19:00',
            'out_at' => '18:00',
            'note' => 'テスト用の備考',
        ]);

        $response->assertSessionHasErrors([
            'attendance_time' =>'出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    public function test_休憩開始時間が退勤時間より遅くてエラーメッセージ表示(){

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $this->attendance->id,[
            'in_at' => '09:00',
            'out_at' => '18:00',
            'break_in_at' => ['19:00'],
            'break_out_at' => ['19:30'],
            'note' => 'テスト用備考',
        ]);

        $response->assertSessionHasErrors([
            'break_time.0' =>'休憩時間が不適切な値です'
        ]);
    }

    public function test_休憩終了時間が退勤時間より遅くてエラーメッセージ表示(){

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $this->attendance->id,[
            'in_at' => '09:00',
            'out_at' => '18:00',
            'break_in_at' => ['11:00'],
            'break_out_at' => ['19:30'],
            'note' => 'テスト用備考',
        ]);

        $response->assertSessionHasErrors([
            'break_time.0' =>'休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    public function test_備考欄空白でエラーメッセージ表示(){

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $this->attendance->id, [
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' =>'備考を記入してください'
        ]);
    }

    public function test_備考欄が100文字超過でエラーメッセージ表示(){

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $this->attendance->id, [
            'note' => str_repeat('あ', 101),
        ]);

        $response->assertSessionHasErrors([
            'note' =>'100文字以内で記入してください'
        ]);
    }
}
