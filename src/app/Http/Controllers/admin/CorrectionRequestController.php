<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\Attendance;
use App\Services\DetailService;
use App\Services\CorrectionRequestService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class CorrectionRequestController extends Controller
{

    public function correctionIndex(CorrectionRequestService $correctionRequestService){

        //attendanceのリレーション関係にあるuserを一緒に取得
        $correctionRequests_pending = CorrectionRequest::with('attendance.user')
                ->where('status','pending')
                ->get();
        $correctionRequests_approved = CorrectionRequest::with('attendance.user')
                ->where('status','approved')
                ->get();

        $correctionRequests = $correctionRequestService
                ->correctionRequest($correctionRequests_pending,$correctionRequests_approved);

        $pendingRequests = $correctionRequests['pendingRequests'];
        $approvedRequests = $correctionRequests['approvedRequests'];

        //デフォルト表示をpendingに指定
        $activeTab = 'pending';

        return view('admin.correction_request_index',compact('pendingRequests','approvedRequests','activeTab'));
    }

    //申請詳細を表示
    public function showRequest($correction_request_id,DetailService $detailService){

        $correctionRequest = CorrectionRequest::with('attendance.user','breakTimes')
                ->where('id',$correction_request_id)
                ->first();

        $detail_data = $detailService->detailData($correctionRequest->attendance,$correctionRequest);

        $userName = $detail_data['userName'];
        $dateYearLabel = $detail_data['dateYearLabel'];
        $dateMonthDayLabel = $detail_data['dateMonthDayLabel'];
        $inAtLabel = $detail_data['inAtLabel'];
        $outAtLabel = $detail_data['outAtLabel'];
        $noteLabel = $detail_data['noteLabel'];
        $isPending = $detail_data['isPending'];
        $breakRows = $detail_data['breakRows'];

        return view('admin.correction_request_detail',compact('correctionRequest','userName','dateYearLabel','dateMonthDayLabel','inAtLabel','outAtLabel','noteLabel','breakRows','isPending'));
    }


    public function approveRequest($correction_request_id){

        // 申請テーブルから勤怠と休憩情報を併せて取得
        $request_data = CorrectionRequest::with('attendance','breakTimes')
                ->where('id',$correction_request_id)
                ->first();

        //$request_dataで一緒に取得したattendanceを使いやすいように変数定義
        $attendance = $request_data->attendance;

        //$request_dataで一緒に取得したbreakTimesを使いやすいように変数定義
        $breakRows = $request_data->breakTimes;

        //トランザクション->一連の処理が一部でも失敗すると元に戻る
        DB::transaction(function() use($request_data,$attendance,$breakRows){

            //元の勤怠を申請内容で上書き保存する処理
            Attendance::where('id',$attendance->id)->update([
                'in_at' => $request_data->requested_in_at,
                'out_at' => $request_data->requested_out_at,
                'note' => $request_data->reason,
            ]);

            //元の勤怠に紐づく休憩を削除する処理
            $attendance->breakTimes()->delete();

            //foreachで休憩の配列を回す→保存する処理
            foreach($breakRows as $index => $breakRow ){
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'in_at' => $breakRow->requested_in_at,
                    'out_at' => $breakRow->requested_out_at,
                ]);
            }

            //申請テーブルのステータスを承認済みにして保存する処理
            CorrectionRequest::where('id',$request_data->id)->update([
                'status' => 'approved'
            ]);
        }
        );

        return redirect()->route('admin.correction.show',['correction_request_id' => $correction_request_id]);

    }
}
