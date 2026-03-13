<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user1;
    private $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $members = $this->createAdminAndUsers();

        $this->admin = $members['admin'];
        $this->user1 = $members['users'][0];
        $this->user2 = $members['users'][1];
    }

    public function test_全ユーザーのその日の勤怠情報が全部見れる(){
        $user1Attendance = $this->createAttendanceFor($this->user1,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00:00',
            'out_at' => '2026-03-10 18:00:00',
        ]);

        $user2Attendance = $this->createAttendanceFor($this->user2,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 08:30:00',
            'out_at' => '2026-03-10 19:00:00',
        ]);

        $this->createBreakTimeFor($user1Attendance,[
            'in_at' => '2026-03-10 11:00:00',
            'out_at' => '2026-03-10 12:00:00',
        ]);

        $this->createBreakTimeFor($user2Attendance,[
            'in_at' => '2026-03-10 10:00:00',
            'out_at' => '2026-03-10 12:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-10');

        $response->assertStatus(200);
        $response->assertSee('テストユーザー1');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        $response->assertSee('テストユーザー2');
        $response->assertSee('08:30');
        $response->assertSee('19:00');
        $response->assertSee('2:00');
        $response->assertSee('8:30');
    }

    public function test_勤怠一覧画面にその日の日付が表示される(){
        $fixedNow = Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($fixedNow->format('Y/m/d'));

        Carbon::setTestNow();

    }

    public function test_前日ボタンを押すと勤怠一覧画面に前日の勤怠情報が表示される(){
        $user1Attendance = $this->createAttendanceFor($this->user1,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00:00',
            'out_at' => '2026-03-10 18:00:00',
        ]);

        $user2Attendance = $this->createAttendanceFor($this->user2,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 08:30:00',
            'out_at' => '2026-03-10 19:00:00',
        ]);

        $this->createBreakTimeFor($user1Attendance,[
            'in_at' => '2026-03-10 11:00:00',
            'out_at' => '2026-03-10 12:00:00',
        ]);

        $this->createBreakTimeFor($user2Attendance,[
            'in_at' => '2026-03-10 10:00:00',
            'out_at' => '2026-03-10 12:00:00',
        ]);

        $fixedNow = Carbon::create(2026, 3, 11, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $this->actingAs($this->admin)
            ->get('/admin/attendance/list')
            ->assertSee('/admin/attendance/list?date=2026-03-10', false);


        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-10');

        $response->assertStatus(200);
        $response->assertSee('2026/03/10');
        $response->assertSee('テストユーザー1');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('テストユーザー2');
        $response->assertSee('08:30');
        $response->assertSee('19:00');
        $response->assertSee('2:00');
        $response->assertSee('8:30');

        Carbon::setTestNow();

    }

    public function test_翌日ボタンを押すと勤怠一覧画面に翌日の勤怠情報が表示される(){
        $user1Attendance = $this->createAttendanceFor($this->user1,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00:00',
            'out_at' => '2026-03-10 18:00:00',
        ]);

        $user2Attendance = $this->createAttendanceFor($this->user2,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 08:30:00',
            'out_at' => '2026-03-10 19:00:00',
        ]);

        $this->createBreakTimeFor($user1Attendance,[
            'in_at' => '2026-03-10 11:00:00',
            'out_at' => '2026-03-10 12:00:00',
        ]);

        $this->createBreakTimeFor($user2Attendance,[
            'in_at' => '2026-03-10 10:00:00',
            'out_at' => '2026-03-10 12:00:00',
        ]);

        $fixedNow = Carbon::create(2026, 3, 9, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $this->actingAs($this->admin)
            ->get('/admin/attendance/list')
            ->assertSee('/admin/attendance/list?date=2026-03-10', false);


        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-10');

        $response->assertStatus(200);
        $response->assertSee('2026/03/10');
        $response->assertSee('テストユーザー1');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('テストユーザー2');
        $response->assertSee('08:30');
        $response->assertSee('19:00');
        $response->assertSee('2:00');
        $response->assertSee('8:30');

        Carbon::setTestNow();

    }
}
