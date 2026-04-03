<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    //DBを毎回フレッシュする
    use RefreshDatabase;

    //setupで使う箱
    private $user;
    private $attendance;
    private $breakTime;

    protected function setUp(): void
    {
        //テスト環境を初期化
        parent::setUp();

        //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 02, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        //ユーザーを1人作成
        $this->user = $this->createRoleUser();

        //ユーザーの勤怠情報作成
        $this->attendance = $this->createAttendance($this->user,[
            'date' => '2026-03-01',
            'in_at' => '2026-03-01 09:00:00',
            'out_at' => '2026-03-01 18:00:00',
        ]);

        //勤怠に紐づく休憩情報を作成
        $this->breakTime = $this->createBreakTime($this->attendance,[
            'in_at' => '2026-03-02 10:00:00',
            'out_at' => '2026-03-02 11:00:00',
        ]);

    }

    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_自分が行った勤怠情報が全て表示される(): void
    {
        //２件目の勤怠情報作成
        $secondAttendance = $this->createAttendance($this->user,[
            'date' => '2026-03-03',
            'in_at' => '2026-03-03 7:00:00',
            'out_at' => '2026-03-03 16:00:00',
        ]);

        //２件目の休憩情報作成
        $secondBreakTime = $this->createBreakTime($secondAttendance,[
            'in_at' => '2026-03-03 12:00:00',
            'out_at' => '2026-03-03 14:00:00',
        ]);

        //ユーザーログイン、勤怠一覧ページへ移動
        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . now()->format('Y-m'));
        $response->assertStatus(200);

        $response->assertSee('03/02 (月)');
        $response->assertSee('09:00'); //1件目出勤時間
        $response->assertSee('18:00'); //１件目退勤時間
        $response->assertSee('1:00'); //１件目休憩時間
        $response->assertSee('8:00'); //１件目労働時間

        $response->assertSee('03/03 (火)');
        $response->assertSee('07:00'); //2件目出勤時間
        $response->assertSee('16:00'); //2件目退勤時間
        $response->assertSee('2:00'); //2件目休憩時間
        $response->assertSee('7:00'); //2件目労働時間
    }

    public function test_勤怠一覧画面に遷移した際に現在の月の情報が表示される(): void
    {
        //ユーザーログイン、勤怠一覧ページに移動
        $response = $this->actingAs($this->user)->get('/attendance/list');
        $response->assertStatus(200);

        $response->assertSee('2026/03');
        $response->assertSee('09:00'); //出勤時間
        $response->assertSee('18:00'); //退勤時間
        $response->assertSee('1:00'); //休憩時間
        $response->assertSee('8:00'); //労働時間
    }

    public function test_前月ボタン押下で前月の情報が表示される(): void
    {
        //前月の勤怠情報作成
        $previousAttendance = $this->createAttendance($this->user, [
            'date' => '2026-02-10',
            'in_at' => '2026-02-10 10:00:00',
            'out_at' => '2026-02-10 19:00:00',
        ]);

        //前月の休憩情報を作成
        $this->createBreakTime($previousAttendance,[
            'in_at' => '2026-02-10 10:00:00',
            'out_at' => '2026-02-10 12:00:00',
        ]);

        //前月リンクに使用する
        $previousMonth = now()->copy()->subMonth()->format('Y-m');

        //ユーザーログイン、勤怠一覧ページに移動して前月リンクがあるか確認
        $this->actingAs($this->user)
            ->get('/attendance/list')
            ->assertSee('/attendance/list?month=' . $previousMonth, false);

        //ユーザーログイン、前月ページに移動
        $response = $this->actingAs($this->user)->get('/attendance/list?month=' . $previousMonth);
        $response->assertStatus(200);

        $response->assertSee('2026/02');
        $response->assertSee('02/10 (火)');
        $response->assertSee('10:00'); //前月の出勤時間
        $response->assertSee('19:00'); //前月の退勤時間
        $response->assertSee('2:00'); //前月の休憩時間
        $response->assertSee('7:00'); //前月の労働時間
    }

    public function test_翌月ボタン押下で翌月の情報が表示される(): void
    {
        //翌月の勤怠情報作成
        $nextAttendance = $this->createAttendance($this->user, [
            'date' => '2026-04-06',
            'in_at' => '2026-04-06 10:00:00',
            'out_at' => '2026-04-06 19:00:00',
        ]);

        //翌月の休憩情報を作成
        $this->createBreakTime($nextAttendance,[
            'in_at' => '2026-04-06 10:00:00',
            'out_at' => '2026-04-06 12:00:00',
        ]);

        //翌月リンクに使用
        $nextMonth = now()->copy()->addMonth()->format('Y-m');

        //ユーザーログイン、勤怠一覧ページに移動、翌月リンクがあるか確認
        $this->actingAs($this->user)
            ->get('/attendance/list')
            ->assertSee('/attendance/list?month=' . $nextMonth, false);

        //ユーザーログイン、翌月ページに移動
        $response = $this->actingAs($this->user)
            ->get('/attendance/list?month=' . $nextMonth)
            ->assertStatus(200);

        $response->assertSee('2026/04');
        $response->assertSee('04/06 (月)');
        $response->assertSee('10:00'); //翌月の出勤時間
        $response->assertSee('19:00'); //翌月の退勤時間
        $response->assertSee('2:00'); //翌月の休憩時間
        $response->assertSee('7:00'); //翌月の労働時間
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        //ユーザーログイン、勤怠一覧ページに移動
        $response = $this->actingAs($this->user)->get('/attendance/list');
        $response->assertStatus(200);

        //詳細リンクと文字があるか確認
        $response->assertSee('/attendance/detail/' . $this->attendance->id, false);
        $response->assertSee('詳細');

        //詳細ページへ移動
        $response = $this->get('/attendance/detail/' . $this->attendance->id);
        $response->assertStatus(200);

        $response->assertSee('勤怠詳細');
        $response->assertSee('2026年');
        $response->assertSee('3月1日');
    }

}
