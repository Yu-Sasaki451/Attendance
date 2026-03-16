<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createRoleUser();
        
    }

    public function test_出勤時間が退勤時間より遅くてエラーメッセージ表示(){
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $response = $this->actingAs($this->user)->post('/attendance/detail/' . $attendance->id, [
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

        $response = $this->actingAs($this->user)->post('/attendance/detail/' . $attendance->id,[
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

        $response = $this->actingAs($this->user)->post('/attendance/detail/' . $attendance->id,[
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

        $response = $this->actingAs($this->user)->post('/attendance/detail/' . $attendance->id, [
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' =>'備考を記入してください'
        ]);
    }

    public function test_申請が承認待ちに表示される(){
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);
        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($this->user)->post('/attendance/detail/' . $attendance->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'テスト用備考',
        ]);

        $response = $this->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee($this->user->name);
        $response->assertSee('テスト用備考');
        $response->assertSee('2026/03/10');

    }

    public function test_承認された申請が承認済みに表示される(){
        $admin = $this->createRoleAdmin();

        $attendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $this->createBreakTimeFor($attendance, [
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $this->actingAs($this->user)->post('/attendance/detail/' . $attendance->id, [
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'テスト用備考',
        ]);

        $correctionRequest = \App\Models\CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->first();

        $this->actingAs($admin)->post('/stamp_correction_request/approve/' . $correctionRequest->id);

        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee('テスト用備考');
        $response->assertSee('2026/03/10');
    }

    public function test_申請一覧の詳細を押すと勤怠詳細に遷移する(){
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);
        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($this->user)->post('/attendance/detail/' . $attendance->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'テスト用備考',
        ]);

        $response = $this->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);
        $response->assertSee('/attendance/detail/' . $attendance->id, false);
        
        $response = $this->get('/attendance/detail/' . $attendance->id);
        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee('2026年');
        $response->assertSee('3月10日');

    }

}
