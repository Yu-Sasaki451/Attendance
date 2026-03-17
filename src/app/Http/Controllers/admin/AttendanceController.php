<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Services\AttendanceMonthlySummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceDetailRequest;


class AttendanceController extends Controller
{
    private AttendanceMonthlySummaryService $attendanceMonthlySummaryService;

    public function __construct(AttendanceMonthlySummaryService $attendanceMonthlySummaryService)
    {
        $this->attendanceMonthlySummaryService = $attendanceMonthlySummaryService;
    }

    public function index(Request $request)
    {
        $currentDate = $request->filled('date')
            ? Carbon::createFromFormat('Y-m-d', $request->date)
            : today();

        $attendances = Attendance::with('breakTimes')
            ->whereDate('date', $currentDate)
            ->get()
            ->keyBy('user_id');

        $rows = User::where('role', 'user')
            ->get()
            ->map(function ($user) use ($attendances) {
                $attendance = $attendances->get($user->id);

                $breakMinutes = $attendance
                    ? $attendance->breakTimes->sum(function ($breakTime) {
                        if (!$breakTime->in_at || !$breakTime->out_at) {
                            return 0;
                        }

                        return Carbon::parse($breakTime->out_at)->diffInMinutes(
                            Carbon::parse($breakTime->in_at)
                        );
                    })
                    : 0;

                $workMinutes = $attendance && $attendance->in_at && $attendance->out_at
                    ? Carbon::parse($attendance->out_at)->diffInMinutes(
                        Carbon::parse($attendance->in_at)
                    ) - $breakMinutes
                    : null;

                return [
                    'id' => $attendance?->id,
                    'name' => $user->name,
                    'in_at' => $attendance && $attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') : '',
                    'out_at' => $attendance && $attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') : '',
                    'break_time' => $attendance ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '',
                    'work_time' => is_null($workMinutes) ? '' : sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60),
                ];
            });

        return view('admin.index', [
            'currentDateLabel' => $currentDate->format('Y/m/d'),
            'titleDateLabel' => $currentDate->format('Y年n月j日'),
            'previousDate' => $currentDate->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $currentDate->copy()->addDay()->format('Y-m-d'),
            'rows' => $rows,
        ]);
    }

    public function detail($id)
    {
        $attendance = Attendance::with([
            'user',
            'breakTimes',
            'correctionRequests.breakTimes',
        ])->findOrFail($id);

        $correctionRequest = $attendance->correctionRequests
            ->sort(function ($left, $right) {
                $leftPriority = $left->status === 'pending' ? 0 : 1;
                $rightPriority = $right->status === 'pending' ? 0 : 1;

                if ($leftPriority !== $rightPriority) {
                    return $leftPriority <=> $rightPriority;
                }

                return $right->created_at->timestamp <=> $left->created_at->timestamp;
            })
            ->first();

        return $this->renderDetail($attendance, $correctionRequest);
    }

    public function staff_list(){
        $staffs = User::select('id','name','email')
            ->where('role','user')
            ->get();

        return view('admin.staff_index',compact('staffs'));
    }

    private function staffAttendanceDays($id,Carbon $currentMonth){
        return $this->attendanceMonthlySummaryService->build($id, $currentMonth);
    }

    public function staff_attendance(Request $request, $id)
    {
        $staff = User::where('role', 'user')->findOrFail($id);

        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $days = $this->staffAttendanceDays($id,$currentMonth);

        return view('admin.staff_attendance', [
            'staffId' => $staff->id,
            'pageTitle' => $staff->name . 'さんの勤怠一覧',
            'days' => $days,
            'currentMonthLabel' => $currentMonth->format('Y/m'),
            'previousMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }

    private function renderDetail(Attendance $attendance, ?CorrectionRequest $correctionRequest)
    {
        $isAdmin = auth()->user()->role === 'admin';

        $dateYearLabel = $attendance->date
            ? Carbon::parse($attendance->date)->format('Y年')
            : '';

        $dateMonthDayLabel = $attendance->date
            ? Carbon::parse($attendance->date)->format('n月j日')
            : '';

        $inAtLabel = $correctionRequest && $correctionRequest->requested_in_at
            ? Carbon::parse($correctionRequest->requested_in_at)->format('H:i')
            : ($attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') : '');

        $outAtLabel = $correctionRequest && $correctionRequest->requested_out_at
            ? Carbon::parse($correctionRequest->requested_out_at)->format('H:i')
            : ($attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') : '');

        $noteLabel = $correctionRequest
            ? $correctionRequest->note
            : $attendance->note;

        $breakTimes = $correctionRequest
            ? $correctionRequest->breakTimes
            : $attendance->breakTimes;

        $breakRows = $breakTimes
            ->values()
            ->map(function ($breakTime) use ($correctionRequest) {
                return [
                    'in_at' => $correctionRequest
                        ? ($breakTime->requested_in_at ? Carbon::parse($breakTime->requested_in_at)->format('H:i') : '')
                        : ($breakTime->in_at ? Carbon::parse($breakTime->in_at)->format('H:i') : ''),
                    'out_at' => $correctionRequest
                        ? ($breakTime->requested_out_at ? Carbon::parse($breakTime->requested_out_at)->format('H:i') : '')
                        : ($breakTime->out_at ? Carbon::parse($breakTime->out_at)->format('H:i') : ''),
                ];
            })
            ->filter(function ($breakRow) {
                return $breakRow['in_at'] !== '' || $breakRow['out_at'] !== '';
            })
            ->values()
            ->map(function ($breakRow, $index) {
                $breakRow['label'] = $index === 0 ? '休憩' : '休憩' . ($index + 1);
                return $breakRow;
            })
            ->all();

        $isPending = $correctionRequest?->status === 'pending';
        $isApproved = $correctionRequest?->status === 'approved';

        if (!$isPending && !$isApproved) {
            $nextIndex = count($breakRows);
            $breakRows[] = [
                'label' => $nextIndex === 0 ? '休憩' : '休憩' . ($nextIndex + 1),
                'in_at' => '',
                'out_at' => '',
            ];
        }

        return view('admin.detail', [
            'attendance' => $attendance,
            'correctionRequest' => $correctionRequest,
            'dateYearLabel' => $dateYearLabel,
            'dateMonthDayLabel' => $dateMonthDayLabel,
            'inAtLabel' => $inAtLabel,
            'outAtLabel' => $outAtLabel,
            'noteLabel' => $noteLabel,
            'breakRows' => $breakRows,
            'userName' => $attendance->user->name ?? '',
            'isPending' => $isPending,
            'isApproved' => $isApproved,
            'isAdmin' => $isAdmin,
        ]);
    }

    private function formatDateTime($date, $time)
    {
        $time = trim((string) $time);

        return $time === ''
            ? null
            : Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
    }

    public function update(AttendanceDetailRequest $request, $id){

    $attendance = Attendance::findOrFail($id);
    $date = $attendance->date;
    $breakInTimes = $request->input('break_in_at', []);
    $breakOutTimes = $request->input('break_out_at', []);

    DB::transaction(function () use ($attendance, $request, $date, $breakInTimes, $breakOutTimes) {
        $attendance->update([
            'in_at' => $this->formatDateTime($date, $request->input('in_at')),
            'out_at' => $this->formatDateTime($date, $request->input('out_at')),
            'note' => $request->input('note'),
        ]);

        $attendance->breakTimes()->delete();

        foreach ($breakInTimes as $index => $breakInAt) {
            $breakOutAt = $breakOutTimes[$index] ?? '';

            if ($breakInAt === '' && $breakOutAt === '') {
                continue;
            }

            $attendance->breakTimes()->create([
                'in_at' => $this->formatDateTime($date, $breakInAt),
                'out_at' => $this->formatDateTime($date, $breakOutAt),
            ]);
        }
    });

    return redirect()->route('admin.index');
    }

    public function exportStaffAttendanceCsv(Request $request, $id){
    $staff = User::where('role', 'user')->findOrFail($id);

    $currentMonth = $request->filled('month')
        ? Carbon::createFromFormat('Y-m', $request->month)
        : now();

    $days = $this->staffAttendanceDays($id, $currentMonth);

    $fileName = 'attendance_' . $staff->id . '_' . $currentMonth->format('Y_m') . '.csv';

    return response()->streamDownload(function () use ($days) {
        $handle = fopen('php://output', 'w');

        fputcsv($handle, [
            mb_convert_encoding('日付', 'SJIS-win', 'UTF-8'),
            mb_convert_encoding('出勤', 'SJIS-win', 'UTF-8'),
            mb_convert_encoding('退勤', 'SJIS-win', 'UTF-8'),
            mb_convert_encoding('休憩', 'SJIS-win', 'UTF-8'),
            mb_convert_encoding('合計', 'SJIS-win', 'UTF-8'),
        ]);

        foreach ($days as $day) {
            fputcsv($handle, [
                mb_convert_encoding($day['label'], 'SJIS-win', 'UTF-8'),
                mb_convert_encoding($day['in_at'], 'SJIS-win', 'UTF-8'),
                mb_convert_encoding($day['out_at'], 'SJIS-win', 'UTF-8'),
                mb_convert_encoding($day['break_time'], 'SJIS-win', 'UTF-8'),
                mb_convert_encoding($day['work_time'], 'SJIS-win', 'UTF-8'),
            ]);
        }

        fclose($handle);
    }, $fileName, [
        'Content-Type' => 'text/csv; charset=Shift_JIS',
    ]);
    }


}
