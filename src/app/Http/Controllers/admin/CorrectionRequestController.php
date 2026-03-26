<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\User;
use Carbon\Carbon;


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
                'target_date' => $correctionRequest_pending->attendance->date,
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
                'target_date' => $correctionRequest_approved->attendance->date,
                'reason' => $correctionRequest_approved->reason,
                'applied_date' => Carbon::parse($correctionRequest_approved->created_at)->format('Y/m/d'),
                'id' => $correctionRequest_approved->id,
            ];
        }

        //デフォルト表示をpendingに指定
        $activeTab = 'pending';

        return view('admin.correction_request_index',compact('pendingRequests','approvedRequests','activeTab'));
    }

    public function showRequest($attendance_correction_request_id){

        
    }
}
