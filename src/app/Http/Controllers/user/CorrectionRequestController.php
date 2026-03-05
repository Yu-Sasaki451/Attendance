<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;

class CorrectionRequestController extends Controller
{
    public function store(Request $request, $id)
    {
        $attendance = Attendance::where('user_id', auth()->id())->findOrFail($id);
        $alreadyPending = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return redirect()->route('attendance.detail', ['id' => $attendance->id]);
        }

        $date = $attendance->date;
        $inAt = $request->input('in_at');
        $outAt = $request->input('out_at');
        $note = (string) $request->input('note', '');

        CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_in_at' => $inAt !== '' ? $date . ' ' . $inAt . ':00' : null,
            'requested_out_at' => $outAt !== '' ? $date . ' ' . $outAt . ':00' : null,
            'reason' => $note,
            'status' => 'pending',
            'note' => $note,
        ]);

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }

    public function correctionIndex(Request $request){
        $activeTab = $request->query('tab', 'pending');
        $userName = auth()->user()->name;

        $pendingRequests = CorrectionRequest::whereRelation('attendance', 'user_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(function ($request) {
                $request->status_label = $request->status === 'pending' ? '承認待ち' : '承認済み';
                $request->target_date = Carbon::parse($request->requested_in_at)->format('Y/m/d');
                $request->applied_date = $request->created_at->format('Y/m/d');
                return $request;
            });

        $approvedRequests = CorrectionRequest::whereRelation('attendance', 'user_id', auth()->id())
            ->where('status', 'approved')
            ->latest()
            ->get()
            ->map(function ($request) {
                $request->status_label = $request->status === 'pending' ? '承認待ち' : '承認済み';
                $request->target_date = Carbon::parse($request->requested_in_at)->format('Y/m/d');
                $request->applied_date = $request->created_at->format('Y/m/d');
                return $request;
            });

        return view('user.correction_request_index', compact('pendingRequests', 'approvedRequests', 'activeTab', 'userName'));
    }
}
