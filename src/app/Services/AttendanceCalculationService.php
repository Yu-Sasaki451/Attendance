<?php

namespace App\Services;

use Carbon\Carbon;

class AttendanceCalculationService{

public function attendance_data($attendanceOfDay){

        $breakMinutes = null;
        $breakTimeLabel = null;

        //休憩時間n件を合計、inとoutの差分を分で計算
        $breakMinutes = collect($attendanceOfDay?->breakTimes)->sum(function($breakTime){
        return Carbon::parse($breakTime->in_at)
        ->diffInMinutes(Carbon::parse($breakTime->out_at));
        });

        //H:mmの形で表示、Hは60で割る
        $breakTimeLabel = sprintf('%d:%02d',floor($breakMinutes / 60),$breakMinutes % 60);

        $workMinutes = null;
        $workTimeLabel = null;

        //勤怠がある＆退勤があるなら計算
        if($attendanceOfDay && $attendanceOfDay->out_at){
        $workMinutes = Carbon::parse($attendanceOfDay->in_at)
        ->diffInMinutes(Carbon::parse($attendanceOfDay->out_at));

        //休憩時間を引く
        $workMinutes -= $breakMinutes;

        //H:mmで表示させる
        $workTimeLabel = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);}


        return [
            'breakMinutes' => $breakMinutes,
            'breakTimeLabel' => $breakTimeLabel,
            'workMinutes' => $workMinutes,
            'workTimeLabel' => $workTimeLabel,
        ];
}
}
