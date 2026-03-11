<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
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

    $isAdmin = auth()->user()->role === 'admin';

    $attendance = Attendance::with(['user', 'breakTimes','correctionRequests'])
        ->findOrFail($id);

    $pendingRequest = $attendance->correctionRequests()
        ->where('status','pending')
        ->latest()
        ->first();

    $dateLabel = $attendance->date
        ? Carbon::parse($attendance->date)->format('Y/m/d')
        : '';

    $inAtLabel = $pendingRequest && $pendingRequest->requested_in_at
            ? Carbon::parse($pendingRequest->requested_in_at)->format('H:i')
            : ($attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') : '');

        $outAtLabel = $pendingRequest && $pendingRequest->requested_out_at
            ? Carbon::parse($pendingRequest->requested_out_at)->format('H:i')
            : ($attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') : '');

        $noteLabel = $pendingRequest
            ? $pendingRequest->note
            : $attendance->note;

    $breakTimes = $pendingRequest
        ? $pendingRequest->breakTimes
        : $attendance->breakTimes;

    $breakRows = $breakTimes
        ->values()
        ->map(function ($breakTime) use ($pendingRequest) {
            return [
                'in_at' => $pendingRequest
                    ? ($breakTime->requested_in_at ? Carbon::parse($breakTime->requested_in_at)->format('H:i') : '')
                    : ($breakTime->in_at ? Carbon::parse($breakTime->in_at)->format('H:i') : ''),
                'out_at' => $pendingRequest
                    ? ($breakTime->requested_out_at ? Carbon::parse($breakTime->requested_out_at)->format('H:i') : '')
                    : ($breakTime->out_at ? Carbon::parse($breakTime->out_at)->format('H:i') : ''),
            ];
        })
        ->filter(function ($breakRow) {
            return $breakRow['in_at'] !== '' || $breakRow['out_at'] !== '';
        })
        ->values()
        ->map(function ($breakRow, $index) {
            $breakRow['label'] = $index === 0 ? '休憩時間' : '休憩時間' . ($index + 1);
            return $breakRow;
        })
        ->all();

    $userName = $attendance->user->name ?? '';
    $isPending = !is_null($pendingRequest);

    if (!$isPending) {
        $nextIndex = count($breakRows);
        $breakRows[] = [
            'label' => $nextIndex === 0 ? '休憩時間' : '休憩時間' . ($nextIndex + 1),
            'in_at' => '',
            'out_at' => '',
        ];
    }

    return view('admin.detail', [
        'attendance' => $attendance,
        'dateLabel' => $dateLabel,
        'inAtLabel' => $inAtLabel,
        'outAtLabel' => $outAtLabel,
        'noteLabel' => $noteLabel,
        'breakRows' => $breakRows,
        'userName' => $userName,
        'isPending' => $isPending,
        'isAdmin' => $isAdmin,
    ]);
    }

    public function staff_list(){
        $staffs = User::select('id','name','email')
            ->where('role','user')
            ->get();

        return view('admin.staff_index',compact('staffs'));
    }

    public function staff_attendance(Request $request, $id)
    {
        $staff = User::where('role', 'user')->findOrFail($id);

        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $id)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()
            ->keyBy('date');

        $days = collect(\Carbon\CarbonPeriod::create(
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth()
        ))->map(function ($day) use ($attendances) {
            $attendance = $attendances->get($day->toDateString());

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
                'id' => $attendance ? $attendance->id : null,
                'label' => $day->format('m/d') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek] . ')',
                'in_at' => $attendance && $attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') : '',
                'out_at' => $attendance && $attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') : '',
                'break_time' => $attendance ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '',
                'work_time' => is_null($workMinutes) ? '' : sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60),
            ];
        });

        return view('admin.staff_attendance', [
            'staffId' => $staff->id,
            'pageTitle' => $staff->name . 'さんの勤怠一覧',
            'days' => $days,
            'currentMonthLabel' => $currentMonth->format('Y/m'),
            'previousMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
