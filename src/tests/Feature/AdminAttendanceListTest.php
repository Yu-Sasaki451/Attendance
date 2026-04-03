<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    //DBを毎回フレッシュ
    use RefreshDatabase;

    //セットアップで使う箱
    private $admin;
    private $users;
    private $attendance1;
    private $attendance2;
    private $breakTime1;
    private $breakTime2;

    protected function setUp(): void{
        //テスト環境を初期化
        parent::setUp();

        //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 4, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        //ユーザー複数と管理者作成
        $members = $this->createAdminAndUsers();

        $this->admin = $members['admin'];
        $this->users = $members['users'];

        //1人目のユーザーに勤怠と休憩情報を作成
        $this->attendance1 = $this->createAttendance($this->users[0],[
            'date' => '2026-03-04',
            'in_at' => '2026-03-04 09:00',
            'out_at' => '2026-03-04 18:00',
        ]);
        $this->breakTime1 = $this->createBreakTime($this->attendance1,[
            'in_at' => '2026-03-04 11:00',
            'out_at' => '2026-03-04 12:00',
        ]);

        //2人目のユーザーに勤怠と休憩情報を作成
        $this->attendance2 = $this->createAttendance($this->users[1],[
            'date' => '2026-03-04',
            'in_at' => '2026-03-04 10:00',
            'out_at' => '2026-03-04 17:00',
        ]);
        $this->breakTime2 = $this->createBreakTime($this->attendance2,[
            'in_at' => '2026-03-04 13:00',
            'out_at' => '2026-03-04 13:30',
        ]);
    }
    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_全ユーザーのその日の勤怠情報が全部見れる(){

        //管理者ログイン、スタッフの勤怠ページへ、表示確認
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-04');
        $response->assertStatus(200);

        //ユーザー1の勤怠情報
        $response->assertSee($this->users[0]->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        //ユーザー２の勤怠情報
        $response->assertSee($this->users[1]->name);
        $response->assertSee('10:00');
        $response->assertSee('17:00');
        $response->assertSee('0:30');
        $response->assertSee('6:30');
    }

    public function test_勤怠一覧画面にその日の日付が表示される(){

        //管理者ログイン、スタッフの勤怠ページへ、日付確認
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('2026/03/04');

    }

    public function test_前日ボタンを押すと勤怠一覧画面に前日の勤怠情報が表示される(){

        //ユーザー1の前日勤怠作成
        $user1Attendance = $this->createAttendance($this->users[0],[
            'date' => '2026-03-03',
            'in_at' => '2026-03-03 09:00:00',
            'out_at' => '2026-03-03 18:00:00',
        ]);

        //ユーザー2の前日勤怠作成
        $user2Attendance = $this->createAttendance($this->users[1],[
            'date' => '2026-03-03',
            'in_at' => '2026-03-03 10:00:00',
            'out_at' => '2026-03-03 17:00:00',
        ]);

        //ユーザー１の前日休憩作成
        $this->createBreakTime($user1Attendance,[
            'in_at' => '2026-03-03 11:00:00',
            'out_at' => '2026-03-03 12:00:00',
        ]);

        //ユーザー2の前日休憩作成
        $this->createBreakTime($user2Attendance,[
            'in_at' => '2026-03-03 13:00:00',
            'out_at' => '2026-03-03 13:30:00',
        ]);


        //管理者ログイン、勤怠ページへ、前日リンク確認
        $this->actingAs($this->admin)
            ->get('/admin/attendance/list')
            ->assertSee('/admin/attendance/list?date=2026-03-03', false);

        //前日ページへ移動
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-03');
        $response->assertStatus(200);

        //ユーザー1の情報確認
        $response->assertSee('2026/03/03');
        $response->assertSee($this->users[0]->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        //ユーザー2の情報確認
        $response->assertSee($this->users[1]->name);
        $response->assertSee('10:00');
        $response->assertSee('17:00');
        $response->assertSee('0:30');
        $response->assertSee('6:30');
    }

    public function test_翌日ボタンを押すと勤怠一覧画面に翌日の勤怠情報が表示される(){

        //ユーザー1の翌日勤怠作成
        $user1Attendance = $this->createAttendance($this->users[0],[
            'date' => '2026-03-05',
            'in_at' => '2026-03-05 09:00:00',
            'out_at' => '2026-03-05 18:00:00',
        ]);

        //ユーザー２の翌日勤怠作成
        $user2Attendance = $this->createAttendance($this->users[1],[
            'date' => '2026-03-05',
            'in_at' => '2026-03-05 10:00:00',
            'out_at' => '2026-03-05 17:00:00',
        ]);

        //ユーザー１の翌日休憩作成
        $this->createBreakTime($user1Attendance,[
            'in_at' => '2026-03-05 11:00:00',
            'out_at' => '2026-03-05 12:00:00',
        ]);

        //ユーザー２の翌日休憩作成
        $this->createBreakTime($user2Attendance,[
            'in_at' => '2026-03-05 13:00:00',
            'out_at' => '2026-03-05 13:30:00',
        ]);

        //管理者ログイン、勤怠ページ移動、翌日リンク確認
        $this->actingAs($this->admin)
            ->get('/admin/attendance/list')
            ->assertSee('/admin/attendance/list?date=2026-03-05', false);

        //翌日ページへ移動
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-05');
        $response->assertStatus(200);

        //ユーザー１の情報確認
        $response->assertSee('2026/03/05');
        $response->assertSee($this->users[0]->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        //ユーザー２の情報確認
        $response->assertSee($this->users[1]->name);
        $response->assertSee('10:00');
        $response->assertSee('17:00');
        $response->assertSee('0:30');
        $response->assertSee('6:30');

    }
}
