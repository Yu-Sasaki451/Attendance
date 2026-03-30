<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Models\BreakTime;
use App\Services\DateService;
use App\Services\AttendanceCalculationService;
use App\Services\AttendanceDetailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceDetailRequest;


class AttendanceController extends Controller
{

//1日のスタッフ全員の勤怠表示
public function index(
    Request $request,
    DateService $dateService,
    AttendanceCalculationService $attendanceCalculationService){

    //URLに日付があればそれを使う、なければnowを整形
    $date = $request->date ?? now()->format('Y-m-d');

    $dateData = $dateService->getDate($date);

    $currentDateLabel = $dateData['currentDateLabel'];
    $previousDate = $dateData['previousDate'];
    $nextDate = $dateData['nextDate'];

    $previousDateUrl = route('admin.index',['date' => $previousDate]);
    $nextDateUrl = route('admin.index',['date' => $nextDate]);

    $attendances = Attendance::with('user','breakTimes')
        ->where('date',$date)
        ->get();

    $rows = [];

    $pageTitle = Carbon::parse($date)->format('Y年n月j日').'の勤怠';

    foreach($attendances as $attendance){

        //serviceに$attendanceを渡して、結果を$attendance_dataに格納する
        $attendance_data = $attendanceCalculationService->attendance_data($attendance);

        //渡された処理結果を取得する
        $breakMinutes = $attendance_data['breakMinutes'];
        $breakTimeLabel = $attendance_data['breakTimeLabel'];
        $workMinutes = $attendance_data['workMinutes'];
        $workTimeLabel = $attendance_data['workTimeLabel'];
        $detailUrl = route('admin.attendance.detail',['id' => $attendance->id]);

    $rows [] =[
        'name' => $attendance->user->name,
        'in_at' => $attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') :null,
        'out_at' => $attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') :null,
        'break_time' => $breakMinutes === 0 ? null : $breakTimeLabel,
        'work_time' => $workTimeLabel,
        'detailUrl' => $detailUrl,
    ];
    }

    return view('admin.index',compact('pageTitle','currentDateLabel','previousDateUrl','nextDateUrl','rows'));
}

//勤怠詳細表示
public function detail($id,AttendanceDetailService $attendanceDetailService){

    $attendance = Attendance::with('user','breakTimes')
        ->where('id',$id)
        ->first();

    $detail_data = $attendanceDetailService->detailData($attendance);

    
    $correctionRequest = $detail_data['correctionRequest'];
    $userName = $detail_data['userName'];
    $dateYearLabel = $detail_data['dateYearLabel'];
    $dateMonthDayLabel = $detail_data['dateMonthDayLabel'];
    $breakRows = $detail_data['breakRows'];
    $inAtLabel = $detail_data['inAtLabel'];
    $outAtLabel = $detail_data['outAtLabel'];
    $noteLabel = $detail_data['noteLabel'];
    $isPending = $detail_data['isPending'];

    return view('admin.detail',compact('attendance','userName','dateYearLabel','dateMonthDayLabel','inAtLabel','outAtLabel','breakRows','noteLabel','isPending'));
}

//スタッフ一覧表示
public function staff_list(){
    $users = User::where('role','user')->get();

    $staffs = [];

    foreach($users as $user){

    $detailUrl = route('admin.staff.attendance',['id' => $user->id]);

    $staffs[] = [
        'name' => $user->name,
        'email' => $user->email,
        'detailUrl' => $detailUrl,
    ];
    }

    return view('admin.staff_index',compact('staffs'));
}

//スタッフの月別勤怠表示
public function staff_attendance(
    Request $request,
    $id,
    DateService $dateService,
    AttendanceCalculationService $attendanceCalculationService){

    //URLにmonthがあればそれを使う、なければnowを整形
    $month = $request->month ?? now()->format('Y-m');

    $user = User::where('id',$id)->first();

    $monthData = $dateService->getMonth($month);
    
    $date = $monthData['date'];
    $lastDate = $monthData['lastDate'];
    $currentMonthLabel = $monthData['currentMonthLabel'];
    $previousMonth = $monthData['previousMonth'];
    $nextMonth = $monthData['nextMonth'];

    $previousMonthUrl = route('admin.staff.attendance',['id'=> $user->id,'month'=> $previousMonth]);
    $nextMonthUrl = route('admin.staff.attendance',['id'=> $user->id,'month'=> $nextMonth]);

    $week = ['日','月','火','水','木','金','土',];

    $pageTitle = $user->name .'さんの勤怠';

    $attendance = Attendance::with('breakTimes')
                ->where('user_id',$user->id)
                ->whereBetween('date',[$date,$lastDate])
                ->get();


    $days = [];

    while ($date <= $lastDate){

        //1日分の勤怠情報を取得
        $attendanceOfDay = $attendance->firstWhere('date',$date->format('Y-m-d'));

        $attendance_data = $attendanceCalculationService->attendance_data($attendanceOfDay);

        $breakMinutes = $attendance_data['breakMinutes'];
        $breakTimeLabel = $attendance_data['breakTimeLabel'];
        $workMinutes = $attendance_data['workMinutes'];
        $workTimeLabel = $attendance_data['workTimeLabel'];

        $detailUrl = $attendanceOfDay ? route('admin.attendance.detail',['id' => $attendanceOfDay->id]) : null;

        /*
        $daysを配列で用意して必要項目を入れてブレードに渡す
        in_at out_atは1日分の勤怠があるかどうかを確認してから、時間だけに整形する
        休憩時間は0ならNUll、0でなければ時間だけに整形する
        詳細へのURLはweb.phpのルート名を使用　　/attendance/detail/2みたいになる
        */
        $days[] =[
            'dateLabel' => $date->format('m/d'),
            'weekLabel' => $week[$date->dayOfWeek],
            'in_at' => $attendanceOfDay?->in_at ? Carbon::parse($attendanceOfDay->in_at)->format('H:i') : null,
            'out_at' => $attendanceOfDay?->out_at ? Carbon::parse($attendanceOfDay->out_at)->format('H:i') : null,
            'break_time' => $breakMinutes === 0 ? null : $breakTimeLabel,
            'work_time' => $workTimeLabel,
            'detailUrl' => $detailUrl,
        ];

        $date->addDay();

    }

    return view('admin.staff_attendance',compact('user','pageTitle','currentMonthLabel','previousMonthUrl','nextMonthUrl','days'));
}

//管理者の手動修正
public function update(AttendanceDetailRequest $request,$id){
    $request_data = $request->all();
    
    $attendance = Attendance::with('user','breakTimes')
            ->where('id',$id)
            ->first();

    //formから配列で値が来る、array_mapで使えるように変数定義
    $break_in_times = $request['break_in_at'];
    $break_out_times = $request['break_out_at'];

    /*
    in_at ['12:00','15:00']
    out_at ['13:00','15:30']
    のようになってるので、array_mapで
    in_at 12:00　　out_at 13:00
    で１セットになるようにする
    */
    $breakRows = array_map(function ($break_in_time,$break_out_time){
        return [
            'in_at' => $break_in_time,
            'out_at' => $break_out_time
        ];
    }, $break_in_times,$break_out_times);

    /*
    $break_rowsの配列を1件ずつ確認して
    in_at out_atの両方があるものだけ$break_rowsに入れる
    */
    $breakRows = array_filter($breakRows, function ($breakRow) {
    return filled($breakRow['in_at']) && filled($breakRow['out_at']);
    });

    /*
    トランザクションで全部成功した時だけDBの操作が完了するようにする
    どれかが失敗したら全部落ちる
    */
    DB::transaction(function () use($attendance,$request_data,$breakRows){
    $attendance->in_at = $attendance->date .' '. $request_data['in_at'];
    $attendance->out_at = $attendance->date . ' '. $request_data['out_at'];
    $attendance->note = $request_data['note'];
    $attendance->save();

    $attendance->breakTimes()->delete();

    foreach($breakRows as $index => $breakRow){
    $breakTime = new BreakTime;
    $breakTime->attendance_id = $attendance->id;
    $breakTime->in_at = $attendance->date .' '. $breakRow['in_at'];
    $breakTime->out_at = $attendance->date .' '. $breakRow['out_at'];
    $breakTime->save();
    }
    });

    return redirect()->route('admin.index');
}


public function exportStaffAttendanceCsv(Request $request){

    $month = $request->month ?? now()->format('Y-m');
    $targetMonth = Carbon::createFromFormat('Y-m', $month);

    $date = $targetMonth->copy()->startOfMonth();
    $lastDate = $targetMonth->copy()->endOfMonth();

    $week = ['日','月','火','水','木','金','土',];

    while ($date <= $lastDate){
        $days[] =[
            'dateLabel' => $date->format('m/d'),
            'weekLabel' => $week[$date->dayOfWeek],
            'in_at' => '',
            'out_at' => '',
            'break_time' => '',
            'work_time' => '',
            'id' => null,
        ];

        $date->addDay();
    }

    $csv ="日付,曜日,出勤時間,退勤時間,休憩時間,合計勤務時間\n";

    foreach ($days as $day){
        $csv .= "{$day['dateLabel']},{$day['weekLabel']},{$day['in_at']},{$day['out_at']},{$day['break_time']},{$day['work_time']}\n";
    }

    $csvExportUrl = url('/attendance/list/csv') . '?month=' . $month;


    return response($csv);
}


}
