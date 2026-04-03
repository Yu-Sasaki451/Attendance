<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AdminAttendanceUpdateTest extends TestCase
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
            'date' => '2026-03-02',
            'in_at' => '2026-03-02 09:00',
            'out_at' => '2026-03-02 18:00',
        ]);
        $this->breakTime1 = $this->createBreakTime($this->attendance1,[
            'in_at' => '2026-03-02 11:00',
            'out_at' => '2026-03-02 12:00',
        ]);

        //2人目のユーザーに勤怠と休憩情報を作成
        $this->attendance2 = $this->createAttendance($this->users[1],[
            'date' => '2026-03-03',
            'in_at' => '2026-03-03 10:00',
            'out_at' => '2026-03-03 17:00',
        ]);
        $this->breakTime2 = $this->createBreakTime($this->attendance2,[
            'in_at' => '2026-03-03 13:00',
            'out_at' => '2026-03-03 13:30',
        ]);
    }
    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }
    
    public function test_申請が全て承認待ちに表示される(){

        //ユーザー１が修正申請するよ
        $response = $this->actingAs($this->users[0])->post('/attendance/detail/' . $this->attendance1->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー1備考',
        ]);

        //ユーザー２が修正申請するよ
        $response = $this->actingAs($this->users[1])->post('/attendance/detail/' . $this->attendance2->id,[
            'in_at' => '09:00',
            'out_at' => '17:00',
            'break_in_at' => ['13:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー2備考',
        ]);

        //管理者ログイン、申請一覧ページへ移動
        $response = $this->actingAs($this->admin)->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);

        //ユーザー１と２の申請内容確認
        $response->assertSee('承認待ち');
        $response->assertSee($this->users[0]->name);
        $response->assertSee('2026/03/02'); //修正対象日
        $response->assertSee('ユーザー1備考');
        $response->assertSee('2026/03/04'); //申請日

        $response->assertSee('承認待ち');
        $response->assertSee($this->users[1]->name);
        $response->assertSee('2026/03/03'); //修正対象日
        $response->assertSee('ユーザー2備考');
        $response->assertSee('2026/03/04'); //申請日

    }


    public function test_承認された申請が全て承認済みに表示される(){
        

        //ユーザー１の修正申請を実行
        $response = $this->actingAs($this->users[0])->post('/attendance/detail/' . $this->attendance1->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー1備考',
        ]);

        //ユーザー2の修正申請を実行
        $response = $this->actingAs($this->users[1])->post('/attendance/detail/' . $this->attendance2->id,[
            'in_at' => '09:00',
            'out_at' => '17:00',
            'break_in_at' => ['13:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー2備考',
        ]);

        //ユーザー１の申請情報を取得
        $correctionRequest1 = \App\Models\CorrectionRequest::where('attendance_id', $this->attendance1->id)
            ->where('status', 'pending')
            ->first();

        //ユーザー2の申請情報を取得
        $correctionRequest2 = \App\Models\CorrectionRequest::where('attendance_id', $this->attendance2->id)
            ->where('status', 'pending')
            ->first();

        //管理者ログイン、ユーザー１と２の申請を承認する
        $this->actingAs($this->admin)->post('/stamp_correction_request/approve/' . $correctionRequest1->id);
        $this->actingAs($this->admin)->post('/stamp_correction_request/approve/' . $correctionRequest2->id);

        //管理者ログイン、申請一覧ページに移動
        $response = $this->actingAs($this->admin)->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

         //ユーザー１と２の申請内容確認
        $response->assertSee('承認済');
        $response->assertSee($this->users[0]->name);
        $response->assertSee('2026/03/02'); //修正対象日
        $response->assertSee('ユーザー1備考');
        $response->assertSee('2026/03/04'); //申請日

        $response->assertSee('承認済');
        $response->assertSee($this->users[1]->name);
        $response->assertSee('2026/03/03'); //修正対象日
        $response->assertSee('ユーザー2備考');
        $response->assertSee('2026/03/04'); //申請日
    }

    public function test_修正申請の内容が正しく表示される(){
        

        //修正申請をする
        $response = $this->actingAs($this->users[0])->post('/attendance/detail/' . $this->attendance1->id,[
            'in_at' => '08:00',
            'out_at' => '18:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー備考修正',
        ]);

        //未承認の申請１件取得する
        $correctionRequest = \App\Models\CorrectionRequest::where('attendance_id', $this->attendance1->id)
            ->where('status', 'pending')
            ->first();

        //管理者ログイン、修正承認画面へ移動
        $response = $this->actingAs($this->admin)->get('/stamp_correction_request/approve/' . $correctionRequest->id);
        $response->assertStatus(200);

        $response->assertSee($this->users[0]->name);
        $response->assertSee('2026年');
        $response->assertSee('3月2日');
        $response->assertSee('08:00'); //出勤時間
        $response->assertSee('18:00'); //退勤時間
        $response->assertSee('12:00'); //休憩開始時間
        $response->assertSee('13:30'); //休憩終了時間
        $response->assertSee('ユーザー備考修正'); //申請理由
        $response->assertSee('承認'); //承認ボタン

    }

    public function test_修正申請の承認処理が正しく行われる(){
        

        //修正申請する
        $response = $this->actingAs($this->users[0])->post('/attendance/detail/' . $this->attendance1->id,[
            'in_at' => '08:00',
            'out_at' => '17:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'ユーザー備考修正',
        ]);

        //修正申請を１件取得
        $correctionRequest = \App\Models\CorrectionRequest::where('attendance_id',$this->attendance1->id)
            ->where('status','pending')
            ->first();

        //管理者ログイン、修正承認、リダイレクト確認
        $response = $this->actingAs($this->admin)
            ->post('/stamp_correction_request/approve/' . $correctionRequest->id);
        $response->assertRedirect('/stamp_correction_request/approve/' . $correctionRequest->id);

        //DBの状態を最新に更新
        $this->attendance1->refresh();

        //申請テーブルのステータスが承認済みになってるか確認
        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved',
        ]);

        //勤怠テーブルが修正内容で更新されてるか確認
        $this->assertDatabaseHas('attendances',[
            'id' => $this->attendance1->id,
            'in_at' => '2026-03-02 08:00:00',
            'out_at' => '2026-03-02 17:00:00',
            'note' => 'ユーザー備考修正',
        ]);

        //休憩テーブルが修正内容で更新されてるか確認
        $this->assertDatabaseHas('break_times',[
            'attendance_id' => $this->attendance1->id,
            'in_at' => '2026-03-02 12:00:00',
            'out_at' => '2026-03-02 13:30:00'
        ]);
    }
}
