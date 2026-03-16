<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceUpdateTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $users;

    protected function setUp(): void{
        parent::setUp();

        $members = $this->createAdminAndUsers();

        $this->admin = $members['admin'];
        $this->users = $members['users'];
    }
    
    public function test_申請が全て承認待ちに表示される(){

        $user1 = $this->users[0];
        $user2 = $this->users[1];


        $attendance1 = $this->createAttendanceFor($user1,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);
        $this->createBreakTimeFor($attendance1,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $attendance2 = $this->createAttendanceFor($user2,[
            'date' => '2026-03-09',
            'in_at' => '2026-03-09 10:00',
            'out_at' => '2026-03-09 18:00',
        ]);
        $this->createBreakTimeFor($attendance2,[
            'in_at' => '2026-03-09 10:00',
            'out_at' => '2026-03-09 12:00',
        ]);

        $response = $this->actingAs($user1)->post('/attendance/detail/' . $attendance1->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー1備考',
        ]);

        $response = $this->actingAs($user2)->post('/attendance/detail/' . $attendance2->id,[
            'in_at' => '09:00',
            'out_at' => '17:00',
            'break_in_at' => ['13:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー2備考',
        ]);

        $response = $this->actingAs($this->admin)->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertSee('ユーザー1備考');
        $response->assertSee('2026/03/10');

        $response->assertSee($user2->name);
        $response->assertSee('ユーザー2備考');
        $response->assertSee('2026/03/09');

    }


    public function test_承認された申請が全て承認済みに表示される(){
        $user1 = $this->users[0];
        $user2 = $this->users[1];


        $attendance1 = $this->createAttendanceFor($user1,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);
        $this->createBreakTimeFor($attendance1,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $attendance2 = $this->createAttendanceFor($user2,[
            'date' => '2026-03-09',
            'in_at' => '2026-03-09 10:00',
            'out_at' => '2026-03-09 18:00',
        ]);
        $this->createBreakTimeFor($attendance2,[
            'in_at' => '2026-03-09 10:00',
            'out_at' => '2026-03-09 12:00',
        ]);

        $response = $this->actingAs($user1)->post('/attendance/detail/' . $attendance1->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー1備考',
        ]);

        $response = $this->actingAs($user2)->post('/attendance/detail/' . $attendance2->id,[
            'in_at' => '09:00',
            'out_at' => '17:00',
            'break_in_at' => ['13:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー2備考',
        ]);

        $correctionRequest1 = \App\Models\CorrectionRequest::where('attendance_id', $attendance1->id)
            ->where('status', 'pending')
            ->first();

        $correctionRequest2 = \App\Models\CorrectionRequest::where('attendance_id', $attendance2->id)
            ->where('status', 'pending')
            ->first();

        $this->actingAs($this->admin)->post('/stamp_correction_request/approve/' . $correctionRequest1->id);
        $this->actingAs($this->admin)->post('/stamp_correction_request/approve/' . $correctionRequest2->id);

        $response = $this->actingAs($this->admin)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200);
        $response->assertSee($user1->name);
        $response->assertSee('ユーザー1備考');
        $response->assertSee('2026/03/10');

        $response->assertSee($user2->name);
        $response->assertSee('ユーザー2備考');
        $response->assertSee('2026/03/09');
    }

    public function test_修正申請の内容が正しく表示される(){
        $user = $this->users[0];

        $attendance = $this->createAttendanceFor($user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);
        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー備考',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee($user->name);
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
        $response->assertSee('08:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:30');
        $response->assertSee('ユーザー備考');
        $response->assertSee('承認');

    }

    public function test_修正申請の承認処理が正しく行われる(){
        $user = $this->users[0];

        $attendance = $this->createAttendanceFor($user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id,[
            'in_at' => '08:00',
            'out_at' => '17:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー備考',
        ]);

        $correctionRequest = \App\Models\CorrectionRequest::where('attendance_id',$attendance->id)
            ->where('status','pending')
            ->first();

        $response = $this->actingAs($this->admin)
            ->post('/stamp_correction_request/approve/' . $correctionRequest->id);

        $attendance->refresh();

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances',[
            'id' => $attendance->id,
            'in_at' => '2026-03-10 08:00:00',
            'out_at' => '2026-03-10 17:00:00',
            'note' => 'ユーザー備考',
        ]);

        $this->assertDatabaseHas('break_times',[
            'attendance_id' => $attendance->id,
            'in_at' => '2026-03-10 12:00:00',
            'out_at' => '2026-03-10 13:30:00'
        ]);
    }
}
