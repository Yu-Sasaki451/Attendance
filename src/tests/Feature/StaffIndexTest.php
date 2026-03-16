<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class StaffIndexTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $users;
    private $user;

    protected function setUp(): void{
        parent::setUp();

        $members = $this->createAdminAndUsers();

        $this->admin = $members['admin'];
        $this->users = $members['users'];
        $this->user = $members['users'][0];
    }

    public function test_全ユーザーのアドレスと名前が表示される(){

        $response = $this->actingAs($this->admin)->get('/admin/staff/list');

        $response->assertStatus(200);

        foreach($this->users as $user){
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    public function test_ユーザーの勤怠情報が表示される(){

        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/staff/' . $this->user->id);

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    public function test_前月ボタン押下で前月の情報が表示される(): void
    {
        $fixedNow = Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $this->createAttendanceFor($this->user, [
            'date' => '2026-02-10',
            'in_at' => '2026-02-10 09:00:00',
            'out_at' => '2026-02-10 18:00:00',
        ]);

        $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 10:00:00',
            'out_at' => '2026-03-10 19:00:00',
        ]);

        $previousMonth = $fixedNow->copy()->subMonth()->format('Y-m');

        $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id)
            ->assertSee('/admin/attendance/staff/' . $this->user->id . '?month=' . $previousMonth, false);

        $response = $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id . '?month=' . $previousMonth);

        $response->assertStatus(200);
        $response->assertSee('2026/02');
        $response->assertSee('02/10(' . $this->jpWeekday(Carbon::create(2026, 2, 10)->dayOfWeek) . ')');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertDontSee('03/10(' . $this->jpWeekday(Carbon::create(2026, 3, 10)->dayOfWeek) . ')');

        Carbon::setTestNow();
    }

    public function test_翌月ボタン押下で翌月の情報が表示される(): void
    {
        $fixedNow = Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00:00',
            'out_at' => '2026-03-10 18:00:00',
        ]);

        $this->createAttendanceFor($this->user, [
            'date' => '2026-04-10',
            'in_at' => '2026-04-10 10:00:00',
            'out_at' => '2026-04-10 19:00:00',
        ]);

        $nextMonth = $fixedNow->copy()->addMonth()->format('Y-m');

        $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id)
            ->assertSee('/admin/attendance/staff/' . $this->user->id . '?month=' . $nextMonth, false);

        $response = $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id . '?month=' . $nextMonth);

        $response->assertStatus(200);
        $response->assertSee('2026/04');
        $response->assertSee('04/10(' . $this->jpWeekday(Carbon::create(2026, 4, 10)->dayOfWeek) . ')');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertDontSee('03/10(' . $this->jpWeekday(Carbon::create(2026, 3, 10)->dayOfWeek) . ')');

        Carbon::setTestNow();
    }


    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $attendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00:00',
            'out_at' => '2026-03-10 18:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id . '?month=' .'2026-03');

        $response->assertStatus(200);
        $response->assertSee('/admin/attendance/' . $attendance->id, false);
        $response->assertSee('詳細');

        $response = $this->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('勤怠詳細');
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
    }

}
