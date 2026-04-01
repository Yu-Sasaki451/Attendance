<?php

namespace App\Services;

use Carbon\Carbon;

class CorrectionRequestService{

    public function correctionRequest($correctionRequests_pending,$correctionRequests_approved){

        //空配列を用意
        $pendingRequests = [];
        $approvedRequests = [];

        //承認待ちの申請情報を1件ずつ配列に格納
        foreach($correctionRequests_pending as $correctionRequest_pending){


            $pendingRequests[] = [
                'status_label' => '承認待ち',
                'user_name' => $correctionRequest_pending->attendance->user->name,
                'target_date' => Carbon::parse($correctionRequest_pending->attendance->date)->format('Y/m/d'),
                'applied_date' => Carbon::parse($correctionRequest_pending->created_at)->format('Y/m/d'),
                'reason' => $correctionRequest_pending->reason,
                'detail_url'=> auth()->user()->role == 'admin' ?
                    route('admin.correction.show',['correction_request_id' => $correctionRequest_pending->id]) :
                    route('attendance.detail',['attendance_id'=> $correctionRequest_pending->attendance->id]),
            ];
        }

        //承認済みの申請情報を1件ずつ配列へ格納
        foreach($correctionRequests_approved as $correctionRequest_approved){

            $approvedRequests[] = [
                'status_label' => '承認済み',
                'user_name' => $correctionRequest_approved->attendance->user->name,
                'target_date' => Carbon::parse($correctionRequest_approved->attendance->date)->format('Y/m/d'),
                'applied_date' => Carbon::parse($correctionRequest_approved->created_at)->format('Y/m/d'),
                'reason' => $correctionRequest_approved->reason,
                'detail_url'=> auth()->user()->role == 'admin' ?
                    route('admin.correction.show',['correction_request_id' => $correctionRequest_approved->id]) :
                    route('attendance.detail',['attendance_id'=> $correctionRequest_approved->attendance->id]),
            ];
        }

        return [
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
        ];
    }
}