<?php

namespace Tests\Feature;

use Tests\TestCase;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    public function test_日時確認()
    {
        $user = $this->createRoleUser();

        $fixedNow = Carbon::create(2026, 3, 8, 9, 5, 0, 'Asia/Tokyo');
        Carbon::setTestNow($fixedNow);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee($fixedNow->format('Y年n月j日'));
        $response->assertSee('（' . ['日', '月', '火', '水', '木', '金', '土'][$fixedNow->dayOfWeek] . '）');
        $response->assertSee($fixedNow->format('H:i'));

        Carbon::setTestNow();
    }
    
}
