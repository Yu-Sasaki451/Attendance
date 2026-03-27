<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreakTime;
use Carbon\Carbon;
use App\Http\Requests\AttendanceDetailRequest;

class CorrectionRequestController extends Controller
{

//修正申請テーブルに保存
public function store(AttendanceDetailRequest $request,$id){
    //勤怠情報がユーザーのと一致するか確認するため
    $attendance_data = Attendance::where('id',$id)
        ->where('user_id',auth()->id())
        ->firstOrFail();

    /*　
    出退勤、備考、ステータスを保存する処理
    formからは時間しか値がないので、日付＆時間に整形が必要
    ステータスは未承認でpending
    */
    $request_data = new CorrectionRequest;
    $request_data->attendance_id = $attendance_data->id;
    $request_data->requested_in_at = $attendance_data->date.' '. $request->input('in_at');
    $request_data->requested_out_at = $attendance_data->date.' '. $request->input('out_at');
    $request_data->status = 'pending';
    $request_data->reason = $request->input('note');
    $request_data->save();

    //formから配列で値が来る、array_mapで使えるように変数定義
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


    /*
    休憩時間を保存する処理
    $indexで番号を休憩時間に番号をつける
    番号は0から始まるので、+1して１からだよと修正する
    日付＆時間の形に整形する
    */
    foreach($break_rows as $index => $break_row){
        $break_request_data = new CorrectionRequestBreakTime;
        $break_request_data->correction_request_id = $request_data->id;
        $break_request_data->break_index = $index + 1;
        $break_request_data->requested_in_at = $attendance_data->date.' '.$break_row['in_at'];
        $break_request_data->requested_out_at = $attendance_data->date.' '.$break_row['out_at'];
        $break_request_data->save();
    }

    return redirect()->back();

}

//申請一覧を表示
public function correctionIndex(){

    //空配列を用意
    $pendingRequests = [];
    $approvedRequests = [];

    //デフォルトで表示するタブをpendingにする
    $activeTab = 'pending';

    /*
    attendanceのリレーションからwhere句にuser_idを使用
    ステータスがpendingのデータだけを取得
    */
    $pending_requests = CorrectionRequest::with('attendance.user')
        ->whereRelation('attendance','user_id',auth()->id())
        ->where('status','pending')
        ->get();

    //1件ずつ配列で入れてビューに渡す
    foreach($pending_requests as $pending_request){
        $pendingRequests[] = [
            'status_label' => '承認待ち',
            'user_name' => $pending_request->attendance->user->name,
            'target_date' => Carbon::parse($pending_request->attendance->date)->format('Y/m/d'),
            'reason' => $pending_request->reason,
            'applied_date' => Carbon::parse($pending_request->created_at)->format('Y/m/d'),
            'detail_url' => route('attendance.detail',['id'=> $pending_request->attendance->id]),
    ];
    }

    /*
    attendanceのリレーションからwhere句にuser_idを使用
    ステータスがapprovedのデータだけを取得
    */
    $approved_requests = CorrectionRequest::with('attendance.user')
        ->whereRelation('attendance','user_id',auth()->id())
        ->where('status','approved')
        ->get();


    foreach($approved_requests as $approved_request){
        $approvedRequests[] = [
            'status_label' => '承認済み',
            'user_name' => $approved_request->attendance->user->name,
            'target_date' => Carbon::parse($approved_request->attendance->date)->format('Y/m/d'),
            'reason' => $approved_request->reason,
            'applied_date' => Carbon::parse($approved_request->created_at)->format('Y/m/d'),
            'detail_url' => route('attendance.detail',['id'=> $approved_request->attendance->id]),
    ];
    }

    return view('user.correction_request_index',compact('pendingRequests','approvedRequests','activeTab'));
}

}
