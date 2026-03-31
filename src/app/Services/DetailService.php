<?php

namespace App\Services;

use App\Models\CorrectionRequest;
use Carbon\Carbon;

class DetailService{

    public function detailData($attendance,$correctionRequest = null){


    $userName = $attendance->user->name;
    $dateYearLabel = Carbon::parse($attendance->date)->format('Y年');
    $dateMonthDayLabel = Carbon::parse($attendance->date)->format('n月j日');

    $breakRows = [];

    /*修正申請($correctionRequest)がある場合
    詳細ページの出退勤、休憩、備考はcorrection_requests_tableの内容を表示させる*/
    if($correctionRequest){
        $inAtLabel = Carbon::parse($correctionRequest->requested_in_at)->format('H:i');
        $outAtLabel = Carbon::parse($correctionRequest->requested_out_at)->format('H:i');

        foreach($correctionRequest->breakTimes as $index => $breakTime){
        $breakRows[] =[
            'label' => '休憩'.($index +1),
            'in_at' => Carbon::parse($breakTime->requested_in_at)->format('H:i'),
            'out_at' => Carbon::parse($breakTime->requested_out_at)->format('H:i'),
    ];
    }
        $noteLabel = $correctionRequest->reason;
    }
        /*修正申請($correctionRequest)がない場合は
        詳細ページの出退勤、休憩、備考attendances_tableの内容を表示させる
        さらに休憩時間の項目を+1個表示させる*/
    else{
        $inAtLabel = Carbon::parse($attendance->in_at)->format('H:i');
        $outAtLabel = Carbon::parse($attendance->out_at)->format('H:i');

        //休憩を1件ずつ配列にする、インデックスの番号が 0 からなので+1する
        foreach($attendance->breakTimes as $index => $breakTime){
        $breakRows[] =[
            'label' => '休憩'.($index +1),
            'in_at' => Carbon::parse($breakTime->in_at)->format('H:i'),
            'out_at' => Carbon::parse($breakTime->out_at)->format('H:i'),
    ];
    }
        //ここで休憩項目を+1する
        $breakRows[] = [
            'label'=> '休憩'.count($breakRows)+1,
            'in_at' => '',
            'out_at' => '',
        ];

        $noteLabel = $attendance->note;
    }

    //$correctionRequestがnullじゃない&ステータスがpending
    $isPending = $correctionRequest && $correctionRequest->status == 'pending';

    return [
        'correctionRequest' =>$correctionRequest,
        'userName' => $userName,
        'dateYearLabel' => $dateYearLabel,
        'dateMonthDayLabel' => $dateMonthDayLabel,
        'inAtLabel' => $inAtLabel,
        'outAtLabel' => $outAtLabel,
        'breakRows' => $breakRows,
        'noteLabel' => $noteLabel,
        'isPending' => $isPending,
    ];
    }
}