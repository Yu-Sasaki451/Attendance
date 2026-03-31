<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestBreakTime;
use App\Services\BreakCalculationService;
use App\Services\CorrectionRequestService;
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
    $request_data = CorrectionRequest::create([
        'attendance_id' => $attendance_data->id,
        'requested_in_at' => $attendance_data->date.' '. $request->input('in_at'),
        'requested_out_at' => $attendance_data->date.' '. $request->input('out_at'),
        'status' => 'pending',
        'reason' => $request->input('note'),
    ]);

    $breakRows = $breakCalculationService->break_array($request);

    /*
    休憩時間を保存する処理
    $indexで番号を休憩時間に番号をつける
    番号は0から始まるので、+1して１からだよと修正する
    日付＆時間の形に整形する
    */
    foreach($breakRows as $index => $breakRow){
        $break_request_data = CorrectionRequestBreakTime::create([
            'correction_request_id' =>$request_data->id,
            'break_index' => $index + 1,
            'requested_in_at' => $attendance_data->date.' '.$breakRow['in_at'],
            'requested_out_at' => $attendance_data->date.' '.$breakRow['out_at'],
        ]);
    }

    return redirect()->route('attendance.detail',['id' => $id]);

}

//申請一覧を表示
public function correctionIndex(CorrectionRequestService $correctionRequestService){


    $correctionRequests_pending = CorrectionRequest::with('attendance.user')
        ->whereRelation('attendance','user_id',auth()->id())
        ->where('status','pending')
        ->get();

    $correctionRequests_approved = CorrectionRequest::with('attendance.user')
        ->whereRelation('attendance','user_id',auth()->id())
        ->where('status','approved')
        ->get();

    $correctionRequests = $correctionRequestService
                ->correctionRequest($correctionRequests_pending,$correctionRequests_approved);

    $pendingRequests = $correctionRequests['pendingRequests'];
    $approvedRequests = $correctionRequests['approvedRequests'];

    //デフォルトで表示するタブをpendingにする
    $activeTab = 'pending';

    return view('user.correction_request_index',compact('pendingRequests','approvedRequests','activeTab'));
}

}
