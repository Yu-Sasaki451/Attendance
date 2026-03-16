<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;

    protected function setUp(): void{
        parent::setUp();

        $members = $this->createAdminAndUsers();

        $this->admin = $members['admin'];
        $this->user = $members['users'][0];
    }

    public function test_勤怠詳細の内容が選択したものと一致する(){

        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('11:00');
        $response->assertSee('12:00');
    }

    public function test_出勤時間が退勤時間より遅くてエラーメッセージ表示(){
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $attendance->id, [
            'in_at' => '19:00',
            'out_at' => '18:00',
            'note' => 'テスト用の備考',
        ]);

        $response->assertSessionHasErrors([
            'attendance_time' =>'出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    public function test_休憩開始時間が退勤時間より遅くてエラーメッセージ表示(){
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $attendance->id,[
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
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $attendance->id,[
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
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/attendance/' . $attendance->id, [
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' =>'備考を記入してください'
        ]);
    }
}
