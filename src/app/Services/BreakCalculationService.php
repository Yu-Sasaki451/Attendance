<?php

namespace App\Services;

class BreakCalculationService{

    public function break_array($request_data){

    //formから送られてくる値は配列、そのままだと取り出せないからarray_mapで使えるように変数定義
    $break_in_times = $request_data['break_in_at'];
    $break_out_times = $request_data['break_out_at'];

    /*
    in_at ['12:00','15:00']
    out_at ['13:00','15:30']
    のようになってるので、array_mapで
    in_at 12:00　　out_at 13:00
    で１セットになるようにする
    */
    $breakRows = array_map(function ($break_in_time, $break_out_time){
        return [
            'in_at' => $break_in_time,
            'out_at' => $break_out_time,
        ];
    }, $break_in_times,$break_out_times);

    /*
    $break_rowsの配列を1件ずつ確認して
    in_at out_atの両方があるものだけ$break_rowsに入れる
    */
    $breakRows = array_filter($breakRows, function ($breakRow) {
    return filled($breakRow['in_at']) && filled($breakRow['out_at']);
    });

    return $breakRows;
    }
}