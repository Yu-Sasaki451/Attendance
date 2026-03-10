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
    $attendance = Attendance::with(['user', 'breakTimes'])
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
                'in_at' => $breakTime->in_at ? Carbon::parse($breakTime->in_at)->format('H:i') : '',
                'out_at' => $breakTime->out_at ? Carbon::parse($breakTime->out_at)->format('H:i') : '',
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
    $isPending = $attendance->correctionRequests()
        ->where('status', 'pending')
        ->exists();

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
        'breakRows' => $breakRows,
        'userName' => $userName,
        'isPending' => $isPending,
    ]);
}

}
