<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createRoleUser();
    }

    public function test_自分が行った勤怠情報が全て表示される(): void
    {
        $otherUser = $this->createRoleUser();

        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $firstAttendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-03-01',
            'in_at' => '2026-03-01 09:00:00',
            'out_at' => '2026-03-01 18:00:00',
        ]);

        $secondAttendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 10:00:00',
            'out_at' => '2026-03-10 19:00:00',
        ]);

        $otherAttendance = $this->createAttendanceFor($otherUser, [
            'date' => '2026-03-20',
            'in_at' => '2026-03-20 08:00:00',
            'out_at' => '2026-03-20 17:00:00',
        ]);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('03/01(' . $this->jpWeekday(Carbon::parse($firstAttendance->date)->dayOfWeek) . ')');
        $response->assertSee('03/10(' . $this->jpWeekday(Carbon::parse($secondAttendance->date)->dayOfWeek) . ')');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');

        Carbon::setTestNow();
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        $fixedNow = Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $response = $this->actingAs($this->user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($fixedNow->format('Y/m'));

        Carbon::setTestNow();
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

        $this->actingAs($this->user)
            ->get('/attendance/list')
            ->assertSee('/attendance/list?month=' . $previousMonth, false);

        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $previousMonth);

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
            'in_at' => '2026-03-10 10:00:00',
            'out_at' => '2026-03-10 19:00:00',
        ]);

        $this->createAttendanceFor($this->user, [
            'date' => '2026-04-05',
            'in_at' => '2026-04-05 09:00:00',
            'out_at' => '2026-04-05 18:00:00',
        ]);

        $nextMonth = $fixedNow->copy()->addMonth()->format('Y-m');

        $this->actingAs($this->user)
            ->get('/attendance/list')
            ->assertSee('/attendance/list?month=' . $nextMonth, false);

        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $nextMonth);

        $response->assertStatus(200);
        $response->assertSee('2026/04');
        $response->assertSee('04/05(' . $this->jpWeekday(Carbon::create(2026, 4, 5)->dayOfWeek) . ')');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
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

        $listResponse = $this->actingAs($this->user)->get('/attendance/list?month=2026-03');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('/attendance/detail/' . $attendance->id, false);
        $listResponse->assertSee('詳細');

        $detailResponse = $this->get('/attendance/detail/' . $attendance->id);

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee('2026/03/10');
    }

}
