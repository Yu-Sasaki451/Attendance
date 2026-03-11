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
        $inAt = trim((string) $request->input('in_at', ''));
        $outAt = trim((string) $request->input('out_at', ''));
        $note = (string) $request->input('note', '');

        $correctionRequest = CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'requested_in_at' => $inAt === '' ? null : Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $inAt),
            'requested_out_at' => $outAt === '' ? null : Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $outAt),
            'reason' => $note,
            'status' => 'pending',
            'note' => $note,
        ]);

        foreach ($request->input('break_in_at', []) as $index => $breakInAt) {
            $breakInAt = trim((string) $breakInAt);
            $breakOutAt = trim((string) ($request->input('break_out_at', [])[$index] ?? ''));

            if ($breakInAt === '' && $breakOutAt === '') {
                continue;
            }

            $correctionRequest->breakTimes()->create([
                'break_index' => $index + 1,
                'requested_in_at' => $breakInAt === '' ? null : Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $breakInAt),
                'requested_out_at' => $breakOutAt === '' ? null : Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $breakOutAt),
            ]);
        }

        return redirect()->route('attendance.detail', ['id' => $attendance->id]);
    }

    public function correctionIndex(Request $request){
        $activeTab = $request->query('tab', 'pending');

        $pendingRequests = CorrectionRequest::whereRelation('attendance', 'user_id', auth()->id())
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(function ($request) {
                return[
                    'status_label' => $request->status === 'pending' ? '承認待ち' : '承認済み',
                    'user_name' => auth()->user()->name,
                    'target_date' => Carbon::parse($request->requested_in_at)->format('Y/m/d'),
                    'reason' => $request->reason,
                    'applied_date' => $request->created_at->format('Y/m/d'),
                    'detail_url' => route('attendance.detail',['id' => $request->attendance_id]),
                ];
            });

        $approvedRequests = CorrectionRequest::whereRelation('attendance', 'user_id', auth()->id())
            ->where('status', 'approved')
            ->latest()
            ->get()
            ->map(function ($request) {
                return[
                    'status_label' => $request->status === 'pending' ? '承認待ち' : '承認済み',
                    'user_name' => auth()->user()->name,
                    'target_date' => Carbon::parse($request->requested_in_at)->format('Y/m/d'),
                    'reason' => $request->reason,
                    'applied_date' => $request->created_at->format('Y/m/d'),
                    'detail_url' => route('attendance.detail',['id' => $request->attendance_id]),
                ];
            });

        return view('user.correction_request_index', compact('pendingRequests', 'approvedRequests', 'activeTab'));
    }
}
