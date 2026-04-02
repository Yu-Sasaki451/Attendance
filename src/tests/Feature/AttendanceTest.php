<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    //DBを毎回フレッシュにする
    use RefreshDatabase;

    //setupで使う箱
    private $user;

    protected function setUp(): void
    {
        //テスト環境を初期化
        parent::setUp();

        //ユーザーを1人作成
        $this->user = $this->createRoleUser();
    }

    public function test_日時確認()
    {
        //2026-03-15　09：00を固定日時として使うよと宣言
        $fixedNow = Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo');
        //now（）で$fixedNowを使ってね
        Carbon::setTestNow($fixedNow);

        $today = now();

        //ユーザーログイン、勤怠登録画面表示
        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);


        $response->assertSee($today->format('Y年n月j日'));
        $response->assertSee('（' . ['日', '月', '火', '水', '木', '金', '土'][$today->dayOfWeek] . '）');
        $response->assertSee($today->format('H:i'));

        Carbon::setTestNow();
    }
    
}
