<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    //DBを毎回フレッシュにする
    use RefreshDatabase;

    public function test_日時確認()
    {
        //2026-03-15　09：00を固定日時として使うよと宣言
        $fixedNow = Carbon::create(2026, 3, 2, 9, 0, 0, 'Asia/Tokyo');
        //now（）で$fixedNowを使ってね
        Carbon::setTestNow($fixedNow);

        $user = $this->createRoleUser();

        //ユーザーログイン、勤怠登録画面表示
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);


        $response->assertSee(now()->format('Y年n月j日').'（月）');
        $response->assertSee(now()->format('H:i'));

        Carbon::setTestNow();
    }
}
