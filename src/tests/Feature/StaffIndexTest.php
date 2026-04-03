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
    private $attendance;
    private $breakTime;

    protected function setUp(): void{
        //テストする環境を初期化
        parent::setUp();

         //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 02, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        //ユーザー3人と管理者を作る
        $members = $this->createAdminAndUsers();

        $this->admin = $members['admin']; //管理者を入れる
        $this->users = $members['users']; //ユーザー3人を入れる
        $this->user = $members['users'][0]; //1人分のユーザーを入れる

        $this->attendance = $this->createAttendance($this->user,[
            'date' => '2026-03-02',
            'in_at' => '2026-03-02 09:00',
            'out_at' => '2026-03-02 18:00',
            'note' => 'テスト備考'
        ]);

        $this->breaTime = $this->createBreakTime($this->attendance,[
            'in_at' => '2026-03-02 11:00',
            'out_at' => '2026-03-02 12:00',
        ]);
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

        //ログインしてページを表示
        $response = $this->actingAs($this->admin)->get('/admin/attendance/staff/' . $this->user->id);

        //表示が成功したか確認
        $response->assertStatus(200);

        $response->assertSee('09:00'); //出勤時間
        $response->assertSee('18:00'); //退勤時間
        $response->assertSee('1:00'); //休憩時間合計
        $response->assertSee('8:00'); //労働時間合計

    }

    public function test_前月ボタン押下で前月の情報が表示される(): void
    {
        //前月の勤怠情報
        $previousAttendance = $this->createAttendance($this->user, [
            'date' => '2026-02-10',
            'in_at' => '2026-02-10 08:30:00',
            'out_at' => '2026-02-10 19:00:00',
        ]);

        //勤怠情報に休憩情報を作成
        $this->createBreakTime($previousAttendance,[
            'in_at' => '2026-03-10 10:30',
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
        $response->assertSee('02/10 (火)'); //日付と曜日
        $response->assertSee('08:30'); //前月出勤時間
        $response->assertSee('19:00'); //前月退勤時間
        $response->assertSee('0:30'); //前月休憩時間合計
        $response->assertSee('10:00'); //前月労働時間合計

        //表示されてほしくない当月情報
        $response->assertDontSee('2026/03'); //当月
        $response->assertDontSee('03/02 (月)');//日付と曜日
        $response->assertDontSee('09:00'); //当月出勤時間
        $response->assertDontSee('18:00'); //当月退勤時間
        $response->assertDontSee('1:00'); //当月休憩時間
        $response->assertDontSee('8:00'); //当月労働時間
    }

    public function test_翌月ボタン押下で翌月の情報が表示される(): void
    {
        //翌月の勤怠情報
        $nextAttendance = $this->createAttendance($this->user, [
            'date' => '2026-04-10',
            'in_at' => '2026-04-10 10:00:00',
            'out_at' => '2026-04-10 19:00:00',
        ]);

        //翌月に休憩情報を作成
        $this->createBreakTime($nextAttendance,[
            'in_at' => '2026-04-10 10:30',
            'out_at' => '2026-04-10 11:00',
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
        $response->assertSee('04/10 (金)'); //日付と曜日
        $response->assertSee('10:00'); //翌月出勤時間
        $response->assertSee('19:00'); //翌月退勤時間
        $response->assertSee('0:30'); //翌月休憩時間合計
        $response->assertSee('8:30'); //翌月労働時間合計

        //表示されてほしくない当月情報
        $response->assertDontSee('2026/03'); //当月
        $response->assertDontSee('03/02 (月)');//日付と曜日
        $response->assertDontSee('09:00'); //当月出勤時間
        $response->assertDontSee('18:00'); //当月退勤時間
        $response->assertDontSee('1:00'); //当月休憩時間合計
        $response->assertDontSee('8:00'); //当月労働時間合計

        Carbon::setTestNow();
    }


    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        //ログインしてスタッフのページを表示
        $response = $this->actingAs($this->admin)
            ->get('/admin/attendance/staff/' . $this->user->id . '?month=' .'2026-03');

        //表示成功したか確認
        $response->assertStatus(200);
        $response->assertSee('詳細'); //詳細のボタンがあるか確認
        $response->assertSee('/admin/attendance/' . $this->attendance->id, false); //リンクがあるか確認


        //詳細リンクでページを移動
        $response = $this->get('/admin/attendance/' . $this->attendance->id);

        //表示成功か確認
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee('2026年'); //対象年
        $response->assertSee('3月2日'); //対象月日
        $response->assertSee('09:00'); //出勤時間
        $response->assertSee('18:00'); // 退勤時間
        $response->assertSee('11:00'); //休憩開始時間
        $response->assertSee('12:00'); //休憩終わり時間
        $response->assertSee('テスト備考'); //備考
    }

}
