<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Models\BreakTime;
use App\Services\AttendanceDetailViewService;
use App\Services\AttendanceMonthlySummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\AttendanceDetailRequest;


class AttendanceController extends Controller
{

//1日のスタッフ全員の勤怠表示
public function index(Request $request){

    //URLに日付があればそれを使う、なければnowを整形
    $date = $request->date ?? now()->format('Y-m-d');

    //$dayを日付として扱うためにCarbonで変換
    $targetDate = Carbon::parse($date);

    $currentDateLabel = $targetDate->format('Y/m/d');
    $previousDate = $targetDate->copy()->subDay()->format('Y-m-d');
    $nextDate = $targetDate->copy()->addDay()->format('Y-m-d');

    $attendances = Attendance::with('user','breakTimes')
        ->where('date',$date)
        ->get();

    $rows = [];

    $pageTitle = Carbon::parse($date)->format('Y年n月j日').'の勤怠';

    foreach($attendances as $attendance){

        $breakMinutes = null;
        $breakTimeLabel = null;

        //休憩時間n件を合計、inとoutの差分を分で計算
        $breakMinutes = collect($attendance?->breakTimes)->sum(function($breakTime){
        return Carbon::parse($breakTime->in_at)
        ->diffInMinutes(Carbon::parse($breakTime->out_at));
        });

        //H:mmの形で表示、Hは60で割る
        $breakTimeLabel = sprintf('%d:%02d',floor($breakMinutes / 60),$breakMinutes % 60);

        //出勤がある＆退勤があるなら計算、違うなら合計時間はNULL
        if($attendance->in_at && $attendance->out_at){
        $workMinutes = Carbon::parse($attendance->in_at)
        ->diffInMinutes(Carbon::parse($attendance->out_at));

        //休憩時間を引く
        $workMinutes -= $breakMinutes;

        //H:mmで表示させる
        $workTimeLabel = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);
        } else{ $workTimeLabel = null; }

    $rows [] =[
        'name' => $attendance->user->name,
        'in_at' => $attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') :null,
        'out_at' => $attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') :null,
        'break_time' => $breakMinutes === 0 ? null : $breakTimeLabel,
        'work_time' => $workTimeLabel,
        'id' => $attendance->id,
    ];
    }

    return view('admin.index',compact('pageTitle','currentDateLabel','previousDate','nextDate','rows'));
}

//勤怠詳細表示
public function detail($id){

    $attendance = Attendance::with('user','breakTimes')
        ->where('id',$id)
        ->first();

    /*correctionRequests_tableからattendance_idを基に
    breakTimesの情報も併せて1件取得する
    条件としてステータスがpendingのもの
    */
    $correctionRequest = CorrectionRequest::with('breakTimes')
        ->where('attendance_id',$attendance->id)
        ->where('status','pending')
        ->first();

    $userName = $attendance->user->name;

    $dateYearLabel = Carbon::parse($attendance->date)->format('Y年');

    $dateMonthDayLabel = Carbon::parse($attendance->date)->format('n月j日');

    $breakRows = [];

    /*
    修正申請($correctionRequest)があるかどうかで条件分岐
    ある場合は詳細ページの出退勤、休憩、備考はcorrection_requests_tableの内容を表示させる
    ない場合はattendances_tableの内容を表示させる
    attendances_tableを表示させる場合は休憩時間の項目を+1個表示させる
    */
    if($correctionRequest){
        $inAtLabel = Carbon::parse($correctionRequest->requested_in_at)->format('H:i');
        $outAtLabel = Carbon::parse($correctionRequest->requested_out_at)->format('H:i');

        foreach($correctionRequest->breakTimes as $index => $breakTime){
        $breakRows[] =[
            'label' => '休憩'.($index +1),
            'in_at' => Carbon::parse($breakTime->requested_in_at)->format('H:i'),
            'out_at' => Carbon::parse($breakTime->requested_out_at)->format('H:i'),
    ];
    }
        $noteLabel = $correctionRequest->reason;
    } else{
        $inAtLabel = Carbon::parse($attendance->in_at)->format('H:i');
        $outAtLabel = Carbon::parse($attendance->out_at)->format('H:i');

        foreach($attendance->breakTimes as $index => $breakTime){
        $breakRows[] =[
            'label' => '休憩'.($index +1),
            'in_at' => Carbon::parse($breakTime->in_at)->format('H:i'),
            'out_at' => Carbon::parse($breakTime->out_at)->format('H:i'),
    ];
    }

        $breakRows[] = [
            'label'=> '休憩'.count($breakRows)+1,
            'in_at' => '',
            'out_at' => '',
        ];

        $noteLabel = $attendance->note;
    }

    $isPending = $correctionRequest !== null;

    return view('admin.detail',compact('attendance','userName','dateYearLabel','dateMonthDayLabel','inAtLabel','outAtLabel','breakRows','noteLabel','isPending'));
}

//スタッフ一覧表示
public function staff_list(){
    $staffs = User::where('role','user')->get();

    return view('admin.staff_index',compact('staffs'));
}

//スタッフの月別勤怠表示
public function staff_attendance(Request $request,$id){

    //URLにmonthがあればそれを使う、なければnowを整形
    $month = $request->month ?? now()->format('Y-m');

    //$monthを加減できるようにするためCarbonに変換
    $targetMonth = Carbon::parse($month);

    //月を取得、整形
    $currentMonthLabel = $targetMonth->format('Y/m');
    $previousMonth = $targetMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

    //月の初日と最終日
    $date = $targetMonth->copy()->startOfMonth();
    $lastDate = $targetMonth->copy()->endOfMonth();

    $week = ['日','月','火','水','木','金','土',];

    $user = User::where('id',$id)->first();

    $attendance = Attendance::with('breakTimes')
                ->where('user_id',$user->id)
                ->whereBetween('date',[$date,$lastDate])
                ->get();

    $pageTitle = $user->name .'さんの勤怠';

    $days = [];

    while ($date <= $lastDate){

        //1日分の勤怠情報を取得
        $attendanceOfDay = $attendance->firstWhere('date',$date->format('Y-m-d'));

        //休憩時間n件を合計、inとoutの差分を分で計算
        $breakMinutes = collect($attendanceOfDay?->breakTimes)->sum(function($breakTime){
        return Carbon::parse($breakTime->in_at)
        ->diffInMinutes(Carbon::parse($breakTime->out_at));
        });

        //H:mmの形で表示、Hは60で割る
        $breakTimeLabel = sprintf('%d:%02d',floor($breakMinutes / 60),$breakMinutes % 60);

        //勤怠がある＆退勤があるなら計算、違うならNULL
        if($attendanceOfDay && $attendanceOfDay->out_at){
        $workMinutes = Carbon::parse($attendanceOfDay->in_at)
        ->diffInMinutes(Carbon::parse($attendanceOfDay->out_at));

        //休憩時間を引く
        $workMinutes -= $breakMinutes;

        //H:mmで表示させる
        $workTimeLabel = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);}

        else{ $workTimeLabel = null; }


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
            'id' => $attendanceOfDay ? $attendanceOfDay->id : null,
        ];

        $date->addDay();

    }

    return view('admin.staff_attendance',compact('user','pageTitle','currentMonthLabel','previousMonth','nextMonth','days'));
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

    return redirect('/admin/attendance/list');
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
