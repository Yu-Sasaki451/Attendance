<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class CorrectionRequestController extends Controller
{

    public function correctionIndex(){

        //attendanceのリレーション関係にあるuserを一緒に取得
        $correctionRequests_pending = CorrectionRequest::with('attendance.user')
                ->where('status','pending')
                ->get();
        $correctionRequests_approved = CorrectionRequest::with('attendance.user')
                ->where('status','approved')
                ->get();

        //空配列を用意
        $pendingRequests = [];
        $approvedRequests = [];

        //承認待ちの申請情報を1件ずつ配列に格納
        foreach($correctionRequests_pending as $correctionRequest_pending){

            $pendingRequests[] = [
                'status_label' => '承認待ち',
                'user_name' => $correctionRequest_pending->attendance->user->name,
                'target_date' => Carbon::parse($correctionRequest_pending->attendance->date)->format('Y/m/d'),
                'reason' => $correctionRequest_pending->reason,
                'applied_date' => Carbon::parse($correctionRequest_pending->created_at)->format('Y/m/d'),
                'id' => $correctionRequest_pending->id,
            ];
        }

        //承認済みの申請情報を1件ずつ配列へ格納
        foreach($correctionRequests_approved as $correctionRequest_approved){

            $approvedRequests[] = [
                'status_label' => '承認済み',
                'user_name' => $correctionRequest_approved->attendance->user->name,
                'target_date' => Carbon::parse($correctionRequest_approved->attendance->date)
                        ->format('Y/m/d'),
                'reason' => $correctionRequest_approved->reason,
                'applied_date' => Carbon::parse($correctionRequest_approved->created_at)->format('Y/m/d'),
                'id' => $correctionRequest_approved->id,
            ];
        }

        //デフォルト表示をpendingに指定
        $activeTab = 'pending';

        return view('admin.correction_request_index',compact('pendingRequests','approvedRequests','activeTab'));
    }

    //申請詳細を表示
    public function showRequest($correction_request_id){

        $correction_request = CorrectionRequest::with('attendance.user','breakTimes')
                ->where('id',$correction_request_id)
                ->first();

        $userName = $correction_request->attendance->user->name;

        $dateYearLabel = Carbon::parse($correction_request->attendance->date)->format('Y年');
        $dateMonthDayLabel = Carbon::parse($correction_request->attendance->date)->format('n月j日');

        $inAtLabel = Carbon::parse($correction_request->requested_in_at)->format('H:i');
        $outAtLabel = Carbon::parse($correction_request->requested_out_at)->format('H:i');
        $noteLabel = $correction_request->reason;

        $isPending = $correction_request->status === 'pending';

        $breakRows = [];

        foreach($correction_request->breakTimes as $index => $breakTime){
            $breakRows[] = [
                'label' => '休憩'.$index+1,
                'in_at' => Carbon::parse($breakTime->requested_in_at)->format('H:i'),
                'out_at' => Carbon::parse($breakTime->requested_out_at)->format('H:i'),
            ];
        }

        return view('admin.correction_request_detail',compact('correction_request','userName','dateYearLabel','dateMonthDayLabel','inAtLabel','outAtLabel','noteLabel','breakRows','isPending'));
    }

    // 申請テーブルの情報取得 *
    //　申請休憩の情報取得 *
    //　元の勤怠を申請情報で上書き保存
    //　元の休憩情報を削除
    //　申請の休憩で保存
    //ステータスを承認済みにする
    public function approveRequest($correction_request_id){

        $request_data = CorrectionRequest::with('attendance','breakTimes')
                ->where('id',$correction_request_id)
                ->first();

        $attendance = $request_data->attendance;
        $breakRows = $request_data->breakTimes;

        DB::transaction(function() use($request_data,$attendance,$breakRows){

            $attendance->in_at = $request_data->requested_in_at;
            $attendance->out_at = $request_data->requested_out_at;
            $attendance->note = $request_data->reason;
            $attendance->save();

            $attendance->breakTimes()->delete();

            foreach($breakRows as $index => $breakRow ){

                $breakTime = new BreakTime;
                $breakTime->attendance_id = $attendance->id;
                $breakTime->in_at = $breakRow->requested_in_at;
                $breakTime->out_at = $breakRow->requested_out_at;
                $breakTime->save();
            }

            $request_data->status = 'approved';
            $request_data->save();
        }
        );

        return redirect()->route('admin.correction.show',['id' => $correction_request_id]);

    }
}
