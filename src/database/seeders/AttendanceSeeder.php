<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('role', 'user')->orderBy('id')->get();
        $startDate = Carbon::today()->subMonths(3);
        $endDate = Carbon::today()->subDay();

        foreach ($users as $user) {
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                if ($date->isWeekend()) {
                    continue;
                }

                $inHour = rand(8, 9);
                $inMinute = rand(0, 1) * 30;
                $workHours = rand(8, 9);
                $breakMinute = rand(0, 1) * 30;
                $breakLength = rand(0, 1) ? 30 : 60;

                $inAt = $date->copy()->setTime($inHour, $inMinute);
                $outAt = $inAt->copy()->addHours($workHours);
                $breakInAt = $date->copy()->setTime(12, $breakMinute);
                $breakOutAt = $breakInAt->copy()->addMinutes($breakLength);

                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'in_at' => $inAt,
                        'out_at' => $outAt,
                        'note' => 'ダミー勤怠データ',
                    ]
                );

                BreakTime::where('attendance_id', $attendance->id)->delete();

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'in_at' => $breakInAt,
                    'out_at' => $breakOutAt,
                ]);
            }
        }
    }
}
