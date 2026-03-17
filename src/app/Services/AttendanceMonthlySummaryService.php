<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceMonthlySummaryService
{
    public function build(int $userId, Carbon $currentMonth): Collection
    {
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $userId)
            ->whereYear('date', $currentMonth->year)
            ->whereMonth('date', $currentMonth->month)
            ->get()
            ->keyBy('date');

        return collect(CarbonPeriod::create(
            $currentMonth->copy()->startOfMonth(),
            $currentMonth->copy()->endOfMonth()
        ))->map(function ($day) use ($attendances) {
            $attendance = $attendances->get($day->toDateString());
            $breakMinutes = $this->calculateBreakMinutes($attendance);
            $workMinutes = $this->calculateWorkMinutes($attendance, $breakMinutes);

            return [
                'id' => $attendance ? $attendance->id : null,
                'label' => $day->format('m/d') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek] . ')',
                'in_at' => $attendance && $attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') : '',
                'out_at' => $attendance && $attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') : '',
                'break_time' => $attendance ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '',
                'work_time' => is_null($workMinutes) ? '' : sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60),
            ];
        });
    }

    private function calculateBreakMinutes(?Attendance $attendance): int
    {
        if (!$attendance) {
            return 0;
        }

        return $attendance->breakTimes->sum(function ($breakTime) {
            if (!$breakTime->in_at || !$breakTime->out_at) {
                return 0;
            }

            return Carbon::parse($breakTime->out_at)->diffInMinutes(
                Carbon::parse($breakTime->in_at)
            );
        });
    }

    private function calculateWorkMinutes(?Attendance $attendance, int $breakMinutes): ?int
    {
        if (!$attendance || !$attendance->in_at || !$attendance->out_at) {
            return null;
        }

        return Carbon::parse($attendance->out_at)->diffInMinutes(
            Carbon::parse($attendance->in_at)
        ) - $breakMinutes;
    }
}
