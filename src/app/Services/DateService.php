<?php

namespace App\Services;

use Carbon\Carbon;

class DateService{

    public function getMonth($month){

    //$monthを加減できるようにするためCarbonに変換
    $targetMonth = Carbon::parse($month);

    //月を取得、整形
    $currentMonthLabel = $targetMonth->format('Y/m');
    $previousMonth = $targetMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

     //月の初日と最終日
    $date = $targetMonth->copy()->startOfMonth();
    $lastDate = $targetMonth->copy()->endOfMonth();

    return [
        'currentMonthLabel' => $currentMonthLabel,
        'previousMonth' => $previousMonth,
        'nextMonth' => $nextMonth,
        'date' => $date,
        'lastDate' => $lastDate,
    ];
    }

    public function getDate($date){

     //$dayを日付として扱うためにCarbonで変換
    $targetDate = Carbon::parse($date);

    $currentDateLabel = $targetDate->format('Y/m/d');
    $previousDate = $targetDate->copy()->subDay()->format('Y-m-d');
    $nextDate = $targetDate->copy()->addDay()->format('Y-m-d');

    return [
        'currentDateLabel' => $currentDateLabel,
        'previousDate' => $previousDate,
        'nextDate' => $nextDate,
    ];
    }
}