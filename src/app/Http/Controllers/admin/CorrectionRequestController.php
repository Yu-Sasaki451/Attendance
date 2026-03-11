<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CorrectionRequestController extends Controller
{
    public function correctionIndex(Request $request)
    {
        $formatRequest = function ($request) {
            return [
                'status_label' => $request->status === 'pending' ? '承認待ち' : '承認済み',
                'user_name' => $request->attendance->user->name,
                'target_date' => Carbon::parse($request->requested_in_at)->format('Y/m/d'),
                'reason' => $request->reason,
                'applied_date' => $request->created_at->format('Y/m/d'),
                'detail_url' => route('admin.attendance.detail', ['id' => $request->attendance_id]),
            ];
        };

        $activeTab = $request->query('tab', 'pending');

        $pendingRequests = CorrectionRequest::with('attendance.user')
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map($formatRequest);

        $approvedRequests = CorrectionRequest::with('attendance.user')
            ->where('status', 'approved')
            ->latest()
            ->get()
            ->map($formatRequest);

        return view('admin.correction_request_index', compact('pendingRequests', 'approvedRequests', 'activeTab'));
    }
}
