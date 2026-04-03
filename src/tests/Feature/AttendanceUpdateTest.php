<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceUpdateTest extends TestCase
{
    //DBを毎回フレッシュにする
    use RefreshDatabase;

    //setUpで使う箱
    private $user;
    private $attendance;
    private $breakTime;

    protected function setUp(): void
    {
        //テスト環境を初期化
        parent::setUp();

        //2つセットで、now()は$fixedNowの時間で固定されるよ
        $fixedNow = Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        //ユーザーを1人作成
        $this->user = $this->createRoleUser();

        //ユーザーの勤怠情報を作成
        $this->attendance = $this->createAttendance($this->user, [
            'date' => '2026-03-10',
            'in_at' => '2026-03-10 09:00',
            'out_at' => '2026-03-10 18:00',
            'note' => 'テスト用備考'
        ]);

        //ユーザーの休憩情報を作成 勤怠情報に紐付けること
        $this->breakTime = $this->createBreakTime($this->attendance,[
            'in_at' => '2026-03-10 11:00',
            'out_at' => '2026-03-10 12:00',
        ]);
    }

    protected function tearDown(): void
    {
        //固定した時間を解除するよ
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_出勤時間を退勤時間より遅く修正申請してエラーメッセージ表示(){

        //ログイン後、出勤時間を19：00で送信
        $response = $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id, [
            'in_at' => '19:00',
            'out_at' => '18:00',
            'note' => 'テスト用備考',
        ]);

        $response->assertSessionHasErrors([
            'attendance_time' =>'出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    public function test_休憩開始時間を退勤時間より遅く修正申請してエラーメッセージ表示(){

        //ログイン後、休憩開始時間を19：00で送信
        $response = $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id,[
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

    public function test_休憩終了時間を退勤時間より遅く修正申請してエラーメッセージ表示(){

        //ログイン後、休憩終了時間を19：３０で送信
        $response = $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id,[
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

    public function test_備考欄空白で修正申請してエラーメッセージ表示(){

        //ログイン後、備考を空白で送信
        $response = $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id, [
            'in_at' => '09:00',
            'out_at' => '18:00',
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' =>'備考を記入してください'
        ]);
    }

    public function test_備考欄が100文字超過で修正申請してエラーメッセージ表示(){

        //ログイン後、備考を101文字で送信
        $response = $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id, [
            'in_at' => '09:00',
            'out_at' => '18:00',
            'note' => str_repeat('あ', 101),
        ]);

        $response->assertSessionHasErrors([
            'note' =>'100文字以内で記入してください'
        ]);
    }

    public function test_正しい情報で修正申請して承認待ちに表示される(){

        //ログイン後、正しい値で送信
        $response = $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id,[
            'in_at' => '08:00',
            'out_at' => '20:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'テスト用備考修正',
        ]);


        $response = $this->get('/stamp_correction_request/list?tab=pending'); //承認待ちのページを表示
        $response->assertStatus(200); //表示されたか確認
        $response->assertSee('承認待ち');
        $response->assertSee($this->user->name);
        $response->assertSee('2026/03/10');
        $response->assertSee('テスト用備考修正');
        $response->assertSee('2026/03/15');
    }

    public function test_承認された申請が承認済みに表示される(){

        $admin = $this->createRoleAdmin();

        //ログイン後、正しい値で送信
        $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id, [
            'in_at' => '08:00',
            'out_at' => '20:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'テスト用備考修正',
        ]);

        //$attendance_idを基に、未承認の修正申請情報を１件取得する
        $correctionRequest = \App\Models\CorrectionRequest::where('attendance_id', $this->attendance->id)
            ->where('status', 'pending')
            ->first();

        //管理者でログイン、承認処理をする
        $this->actingAs($admin)->post('/stamp_correction_request/approve/' . $correctionRequest->id);

        //ユーザーでログイン、承認済みページを表示する
        $response = $this->actingAs($this->user)->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200);

        $response->assertSee('承認済み');
        $response->assertSee($this->user->name);
        $response->assertSee('2026/03/10'); //申請対象日
        $response->assertSee('テスト用備考'); //申請理由
        $response->assertSee('2026/03/15'); //申請した日

    }

    public function test_申請一覧の詳細を押すと勤怠詳細に遷移する(){

        //ログイン後、正しい値で送信
        $response = $this->actingAs($this->user)
            ->from('/attendance/detail/' . $this->attendance->id)
            ->post('/attendance/detail/' . $this->attendance->id,[
            'in_at' => '08:00',
            'out_at' => '20:00',
            'break_in_at' => ['12:00'],
            'break_out_at' => ['13:30'],
            'note' => 'テスト用備考修正',
        ]);

        //ユーザーでログイン、未承認ページを開く
        $response = $this->get('/stamp_correction_request/list?tab=pending');
        $response->assertStatus(200);

        //詳細リンクがあることを確認
        $response->assertSee('/attendance/detail/' . $this->attendance->id, false);

        //
        $response = $this->get('/attendance/detail/' . $this->attendance->id);
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee('2026年');
        $response->assertSee('3月10日');
        $response->assertSee('08:00'); //承認後の出勤時間
        $response->assertSee('20:00'); //承認後の退勤時間
        $response->assertSee('12:00'); //承認後の休憩開始時間
        $response->assertSee('13:30'); //承認後の休憩終了時間
        $response->assertSee('テスト用備考修正'); //承認後の備考

    }

}
