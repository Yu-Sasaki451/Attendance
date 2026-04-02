<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class StaffIndexTest extends TestCase
{
    //DBを毎回フレッシュにする
    use RefreshDatabase;

     //setUpで使うための箱を用意する
    private $admin;
    private $users;
    private $user;

    protected function setUp(): void{
        //テストする環境を初期化
        parent::setUp();

        //ユーザー3人と管理者を作る
        $members = $this->createAdminAndUsers();

        $this->admin = $members['admin']; //管理者を入れる
        $this->users = $members['users']; //ユーザー3人を入れる
        $this->user = $members['users'][0]; //1人分のユーザーを入れる

         //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);
    }

    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_全ユーザーのアドレスと名前が表示される(){

        //ログインしてページを表示
        $response = $this->actingAs($this->admin)->get('/admin/staff/list');

        //表示が成功したか確認
        $response->assertStatus(200);

        //foreachで回して3人の情報が表示されるか確認
        foreach($this->users as $user){
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    public function test_ユーザーの勤怠情報が表示される(){
        //ユーザー1人の勤怠情報を作成
        $attendance = $this->createAttendanceFor($this->user,[
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
        ]);

        //勤怠情報に休憩情報を作成
        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);

        //ログインしてページを表示
        $response = $this->actingAs($this->admin)->get('/admin/attendance/staff/' . $this->user->id);

        //表示が成功したか確認
        $response->assertStatus(200);
        
        $response->assertSee('09:00'); //出勤時間
        $response->assertSee('18:00'); //退勤時間
        $response->assertSee('1:00'); //休憩時間合計
        $response->assertSee('8:00'); //労働時間合計

        Carbon::setTestNow();
    }

    public function test_前月ボタン押下で前月の情報が表示される(): void
    {        //今月の勤怠情報
        $thisAttendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 10:00:00',
            'out_at' => '2026-03-10 17:00:00',
        ]);

        //勤怠情報に休憩情報を作成
        $this->createBreakTimeFor($thisAttendance,[
            'in_at' => '2026-03-10 11:30',
            'out_at' => '2026-03-10 12:00',
        ]);

        //前月の勤怠情報
        $previousAttendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-02-10',
            'in_at' => '2026-02-10 08:00:00',
            'out_at' => '2026-02-10 18:00:00',
        ]);

        //勤怠情報に休憩情報を作成
        $this->createBreakTimeFor($previousAttendance,[
            'in_at' => '2026-03-10 10:00',
            'out_at' => '2026-03-10 11:00',
        ]);

        //前月を作成
        $previousMonth = now()->copy()->subMonth()->format('Y-m');

        //ログインしてスタッフページを表示、前月のリンクがあるか確認
        $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id)
            ->assertSee('/admin/attendance/staff/' . $this->user->id . '?month=' . $previousMonth, false);

        //前月のページへ移動
        $response = $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id . '?month=' . $previousMonth);

        //表示されたかを確認
        $response->assertStatus(200);

        //表示されてほしい前月情報
        $response->assertSee('2026/02'); //前月
        $response->assertSee('02/10 (' . $this->jpWeekday(Carbon::create(2026, 2, 10)->dayOfWeek) . ')'); //日付と曜日
        $response->assertSee('08:00'); //前月出勤時間
        $response->assertSee('18:00'); //前月退勤時間
        $response->assertSee('1:00'); //前月休憩時間合計
        $response->assertSee('9:00'); //前月労働時間合計

        //表示されてほしくない当月情報
        $response->assertDontSee('2026/03'); //当月
        $response->assertDontSee('03/10 (' . $this->jpWeekday(Carbon::create(2026, 3, 10)->dayOfWeek) . ')');//日付と曜日
        $response->assertDontSee('10:00'); //当月出勤時間
        $response->assertDontSee('17:00'); //当月退勤時間
        $response->assertDontSee('0:30'); //当月休憩時間合計
        $response->assertDontSee('6:30'); //当月労働時間合計

        Carbon::setTestNow();
    }

    public function test_翌月ボタン押下で翌月の情報が表示される(): void
    {
        //今月の勤怠情報
        $thisAttendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00:00',
            'out_at' => '2026-03-10 18:00:00',
        ]);

        //当月に休憩情報を作成
        $this->createBreakTimeFor($thisAttendance,[
            'in_at' => '2026-03-10 11:30',
            'out_at' => '2026-03-10 12:00',
        ]);

        //翌月の勤怠情報
        $nextAttendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-04-10',
            'in_at' => '2026-04-10 10:00:00',
            'out_at' => '2026-04-10 19:00:00',
        ]);

        //翌月に休憩情報を作成
        $this->createBreakTimeFor($nextAttendance,[
            'in_at' => '2026-03-10 10:00',
            'out_at' => '2026-03-10 11:00',
        ]);

        //翌月を作成
        $nextMonth = now()->copy()->addMonth()->format('Y-m');

        //ログインしてスタッフページを表示、翌月のリンクがあるか確認
        $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id)
            ->assertSee('/admin/attendance/staff/' . $this->user->id . '?month=' . $nextMonth, false);

        //翌月のページへ移動
        $response = $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id . '?month=' . $nextMonth);

        //表示されてほしい翌月情報
        $response->assertSee('2026/04'); //翌月
        $response->assertSee('04/10 (' . $this->jpWeekday(Carbon::create(2026, 4, 10)->dayOfWeek) . ')'); //日付と曜日
        $response->assertSee('10:00'); //翌月出勤時間
        $response->assertSee('19:00'); //翌月退勤時間
        $response->assertSee('1:00'); //翌月休憩時間合計
        $response->assertSee('8:00'); //翌月労働時間合計

        //表示されてほしくない当月情報
        $response->assertDontSee('2026/03'); //当月
        $response->assertDontSee('03/10 (' . $this->jpWeekday(Carbon::create(2026, 3, 10)->dayOfWeek) . ')');//日付と曜日
        $response->assertDontSee('09:00'); //当月出勤時間
        $response->assertDontSee('18:00'); //当月退勤時間
        $response->assertDontSee('0:30'); //当月休憩時間合計
        $response->assertDontSee('8:30'); //当月労働時間合計

        Carbon::setTestNow();
    }


    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        //ユーザー1人の勤怠情報を作成
        $attendance = $this->createAttendanceFor($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00:00',
            'out_at' => '2026-03-10 18:00:00',
            'note' => 'テスト備考'
        ]);

        //$attendanceに休憩情報を作成
        $this->createBreakTimeFor($attendance,[
            'in_at' => '2026-03-10 10:00',
            'out_at' => '2026-03-10 11:00',
        ]);

        //ログインしてスタッフのページを表示
        $response = $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id . '?month=' .'2026-03');

        //表示成功したか確認
        $response->assertStatus(200);
        $response->assertSee('詳細'); //詳細のボタンがあるか確認
        $response->assertSee('/admin/attendance/' . $attendance->id, false); //リンクがあるか確認


        //詳細リンクでページを移動
        $response = $this->get('/admin/attendance/' . $attendance->id);

        //表示成功か確認
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee('2026年'); //対象年
        $response->assertSee('3月10日'); //対象月日
        $response->assertSee('09:00'); //出勤時間
        $response->assertSee('18:00'); // 退勤時間
        $response->assertSee('10:00'); //休憩開始時間
        $response->assertSee('11:00'); //休憩終わり時間
        $response->assertSee('テスト備考'); //備考
    }

}
