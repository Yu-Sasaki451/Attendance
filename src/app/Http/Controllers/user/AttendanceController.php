<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $now = now();

        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('date', today())
            ->first();

        $status = '勤務外';
        $buttons = [];
        $message = null;

        $currentBreak = null;

        if ($attendance) {
            $currentBreak = BreakTime::where('attendance_id', $attendance->id)
                ->whereNotNull('in_at')
                ->whereNull('out_at')
                ->latest()
                ->first();
}


        if ($attendance && $attendance->out_at) {
            $status = '退勤済';
            $message = 'お疲れ様でした。';
        } elseif ($attendance && $currentBreak) {
            $status = '休憩中';
            $buttons = [
                ['label' => '休憩戻', 'route' => route('attendance.break-end'),'type' => 'light'],
            ];
        } elseif ($attendance && $attendance->in_at) {
            $status = '出勤中';
            $buttons = [
                ['label' => '退勤', 'route' => route('attendance.clock-out'),'type' => 'dark'],
                ['label' => '休憩入', 'route' => route('attendance.break-start'),'type' => 'light'],
                ];
        } else{
            $buttons = [
                ['label' => '出勤','route' => route('attendance.clock-in'),'type' => 'dark'],
            ];
        }

        return view('user.attendance', [
            'today' => $now->format('Y年n月j日'),
            'weekday' => ['日', '月', '火', '水', '木', '金', '土'][$now->dayOfWeek],
            'currentTime' => $now->format('H:i'),
            'status' => $status,
            'attendance' => $attendance,
            'buttons' => $buttons,
            'message' => $message,
        ]);
    }

    private function getTodayAttendance(){
    return Attendance::where('user_id', auth()->id())
        ->whereDate('date', today())
        ->first();
    }

    private function getOrCreateTodayAttendance(){
    return Attendance::firstOrCreate(
        [
            'user_id' => auth()->id(),
            'date' => today(),
        ]
    );
    }

    private function getCurrentBreak($attendanceId){
    return BreakTime::where('attendance_id', $attendanceId)
        ->whereNotNull('in_at')
        ->whereNull('out_at')
        ->latest()
        ->first();
    }

    public function clockIn(){
    $attendance = $this->getOrCreateTodayAttendance();

    $attendance->update([
        'in_at' => now(),
    ]);

    return redirect()->route('user.attendance');
    }

    public function clockOut(){
    $attendance = $this->getTodayAttendance();

    if ($attendance) {
        $attendance->update([
            'out_at' => now(),
        ]);
    }

    return redirect()->route('user.attendance');
    }

    public function breakStart(){
    $attendance = $this->getTodayAttendance();

    if ($attendance) {
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'in_at' => now(),
        ]);
    }

    return redirect()->route('user.attendance');
    }

    public function breakEnd(){
    $attendance = $this->getTodayAttendance();

    if ($attendance) {
        $breakTime = $this->getCurrentBreak($attendance->id);

        if ($breakTime) {
            $breakTime->update([
                'out_at' => now(),
            ]);
        }
    }

    return redirect()->route('user.attendance');
    }

    public function list(Request $request)
    {
        $currentMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)
            : now();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
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

        return view('user.attendance_index', [
            'days' => $days,
            'currentMonthLabel' => $currentMonth->format('Y/m'),
            'previousMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function detail($id)
    {
        $attendance = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $dateLabel = $attendance->date
            ? Carbon::parse($attendance->date)->format('Y/m/d')
            : '';

        $inAtLabel = $attendance->in_at
            ? Carbon::parse($attendance->in_at)->format('H:i')
            : '';

        $outAtLabel = $attendance->out_at
            ? Carbon::parse($attendance->out_at)->format('H:i')
            : '';

        $breakRows = $attendance->breakTimes
            ->values()
            ->map(function ($breakTime) {
                return [
                    'in_at' => $breakTime->in_at
                        ? Carbon::parse($breakTime->in_at)->format('H:i')
                        : '',
                    'out_at' => $breakTime->out_at
                        ? Carbon::parse($breakTime->out_at)->format('H:i')
                        : '',
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

        $nextIndex = count($breakRows);
        $breakRows[] = [
            'label' => $nextIndex === 0 ? '休憩時間' : '休憩時間' . ($nextIndex + 1),
            'in_at' => '',
            'out_at' => '',
        ];

        $userName = $attendance->user->name ?? '';

        return view('user.attendance_detail', [
            'attendance' => $attendance,
            'dateLabel' => $dateLabel,
            'inAtLabel' => $inAtLabel,
            'outAtLabel' => $outAtLabel,
            'breakRows' => $breakRows,
            'userName' => $userName,
        ]);
    }

    
    public function update(Request $request, $id)
    {
        $attendance = Attendance::where('user_id', auth()->id())->findOrFail($id);
        $date = $attendance->date;

        $data = [
            'in_at' => $date . ' ' . $request->input('in_at') . ':00',
            'out_at' => $date . ' ' . $request->input('out_at') . ':00',
            'note' => $request->input('note'),
        ];

        $attendance->update($data);

        $breakData = [
            'break_in_at' => $request->input('break_in_at', []),
            'break_out_at' => $request->input('break_out_at', []),
        ];

        $existingBreakTimes = $attendance->breakTimes()
            ->orderBy('id')
            ->get();

        $breakCount = count($breakData['break_in_at']);

        for ($i = 0; $i < $breakCount; $i++) {
            $breakInAt = $breakData['break_in_at'][$i] ?? '';
            $breakOutAt = $breakData['break_out_at'][$i] ?? '';

            if ($breakInAt === '' && $breakOutAt === '') {
                if (isset($existingBreakTimes[$i])) {
                    $existingBreakTimes[$i]->update([
                        'in_at' => null,
                        'out_at' => null,
                    ]);
                }
                continue;
            }

            if ($breakInAt === '' || $breakOutAt === '') {
                continue;
            }

            if (isset($existingBreakTimes[$i])) {
                $existingBreakTimes[$i]->update([
                    'in_at' => $date . ' ' . $breakInAt . ':00',
                    'out_at' => $date . ' ' . $breakOutAt . ':00',
                ]);
            } else {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'in_at' => $date . ' ' . $breakInAt . ':00',
                    'out_at' => $date . ' ' . $breakOutAt . ':00',
                ]);
            }
        }

        return redirect('/attendance/list');
    }
}
