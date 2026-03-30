<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreakTime;
use App\Services\BreakCalculationService;
use Carbon\Carbon;
use App\Http\Requests\AttendanceDetailRequest;

class CorrectionRequestController extends Controller
{

//修正申請テーブルに保存
public function store(
    AttendanceDetailRequest $request,
    $id,
    BreakCalculationService $breakCalculationService){
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

    $breakRows = $breakCalculationService->break_array($request);

    /*
    休憩時間を保存する処理
    $indexで番号を休憩時間に番号をつける
    番号は0から始まるので、+1して１からだよと修正する
    日付＆時間の形に整形する
    */
    foreach($breakRows as $index => $breakRow){
        $break_request_data = new CorrectionRequestBreakTime;
        $break_request_data->correction_request_id = $request_data->id;
        $break_request_data->break_index = $index + 1;
        $break_request_data->requested_in_at = $attendance_data->date.' '.$breakRow['in_at'];
        $break_request_data->requested_out_at = $attendance_data->date.' '.$breakRow['out_at'];
        $break_request_data->save();
    }

    return redirect()->route('attendance.detail',['id' => $id]);

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
