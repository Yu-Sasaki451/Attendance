<?php

namespace App\Services;

class BreakCalculationService{

    public function break_array($request_data){

    //formからの値まとめてやってくるので、array_mapで使えるように変数定義
    $break_in_times = $request->input('break_in_at');
    $break_out_times = $request->input('break_out_at');

    /*
    in_at ['12:00','15:00']
    out_at ['13:00','15:30']
    のようになってるので、array_mapで
    in_at 12:00　　out_at 13:00
    で１セットになるようにする
    */
    $break_rows = array_map(function ($break_in_time, $break_out_time){
        return [
            'in_at' => $break_in_time,
            'out_at' => $break_out_time,
        ];
    }, $break_in_times,$break_out_times);

    /*
    $break_rowsの配列を1件ずつ確認して
    in_at out_atの両方があるものだけ$break_rowsに入れる
    */
    $break_rows = array_filter($break_rows, function ($break_row) {
    return filled($break_row['in_at']) && filled($break_row['out_at']);
    });

    return [
        'breakRows' => $breakRows,
    ];
    }
}