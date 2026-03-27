<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Services\AttendanceDetailViewService;
use App\Services\AttendanceMonthlySummaryService;
use Carbon\Carbon;

class AttendanceController extends Controller
{

//打刻画面
public function index(){

//ブレードで表示する日付
$today = now()->format('Y年n月j日');

//ブレードで表示する曜日
$week = ['日','月','火','水','木','金','土',];

//番号になってる曜日の配列を日本語にしてる
$weekDay = $week[now()->dayOfWeek];

//ブレードで表示する時間
$currentTime = now()->format('h:i');

//休憩のテーブルを一緒に取得、条件は日付とユーザーID
$todayAttendance = Attendance::with('breakTimes')
    ->where('date',now()->format('Y-m-d'))
    ->where('user_id',auth()->id())
    ->first();

//勤怠レコードがなければ勤務外
if (!$todayAttendance) {
    $status = '勤務外';
    $message = '';
    $buttons = [
        [
        'route' => '/attendance/clock-in',
        'type' => 'dark',
        'label' => '出勤',
        ]
    ];
} else {
    //休憩レコードがあるか確認
    $latestBreak = $todayAttendance->breakTimes->last();

    //退勤カラムがあれば退勤済
    if ($todayAttendance->out_at) {
        $status = '退勤済';
        $message = 'お疲れ様でした。';
        $buttons = [];
    }
    //休憩レコードがある＆inがある＆outはないなら休憩中
    elseif ($latestBreak !== null && $latestBreak->in_at !== null && $latestBreak->out_at === null) {
        $status = '休憩中';
        $message = '';
        $buttons = [
            [
            'route' => '/attendance/break-end',
            'type' => 'light',
            'label' => '休憩戻',
            ]
        ];
    }
    //どれにも当てはまらないと出勤中
    else {
        $status = '出勤中';
        $message = '';
        $buttons = [
            [
            'route' => '/attendance/clock-out',
            'type' => 'dark',
            'label' => '退勤',
            ],
            [
            'route' => '/attendance/break-start',
            'type' => 'light',
            'label' => '休憩入',
            ]
            ];
    }}

return view('user.attendance',compact('status','weekDay','today','currentTime','message','buttons'));}

//出勤打刻、登録処理
public function clockIn(){
    
    $attendance_data = $this->todayAttendance();

    //今日の勤怠レコードがなかったら出勤保存
    if(!$attendance_data){
        $attendance_data = new Attendance;
        $attendance_data->user_id = auth()->id();
        $attendance_data->date = now()->toDateString();
        $attendance_data->in_at = now();
        $attendance_data->save();
    }

    return redirect()->route('user.attendance');
}

//退勤打刻、更新処理
public function clockOut(){
    
    $attendance_data = $this->todayAttendance();

    //今日の勤怠レコードがあれば退勤保存
    if($attendance_data){
        $attendance_data->out_at = now();
        $attendance_data->save();
    }

    return redirect()->route('user.attendance');
}

//休憩入り打刻、登録処理
public function breakStart(){

    $attendance_data = $this->todayAttendance();

    //今日の勤怠レコードがあれば休憩入り保存
    if($attendance_data){
        $breakTime_data = new BreakTime;
        $breakTime_data->attendance_id = $attendance_data->id;
        $breakTime_data->in_at = now();
        $breakTime_data->save();
    }

    return redirect()->route('user.attendance');
}

//休憩終わり打刻、更新処理
public function breakEnd(){
    $attendance_data = $this->todayAttendance();

    //今日の勤怠レコードがあれば休憩次レコードを降順で取得
    if($attendance_data){
        $breakTime_data = BreakTime::where('attendance_id',$attendance_data->id)
            ->orderBy('id','desc')
            ->first();

        //休憩レコードがある＆休憩レコードのout_atが入ってないならout_atを保存
        if($breakTime_data && $breakTime_data->out_at === null){
            $breakTime_data->out_at = now();
            $breakTime_data->save();
        }
    }

    return redirect()->route('user.attendance');
}

//打刻で使う共通処理、ユーザーIDと今日の勤怠情報を1件取得
private function todayAttendance(){
        return Attendance::where('user_id',auth()->id())
        ->where('date',now()->toDateString())
        ->first();
}

public function list(Request $request){
    //ページのタイトル
    $pageTitle = '勤怠一覧';

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


    //ひと月で指定してデータ取得
    $attendance = Attendance::with('breakTimes')
        ->where('user_id',auth()->id())
        ->whereBetween('date',[$date,$lastDate])
        ->get();

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

    return view('user.attendance_index',compact('pageTitle','currentMonthLabel','previousMonth','nextMonth','days'));

}

public function detail($id){

    /*
    attendances_tableから勤怠attendance_idを基に
    user breakTimesの情報も併せて1件取得する
    */
    $attendance_data = Attendance::with(['user','breakTimes'])
        ->where('id',$id)
        ->first();

    /*
    　　correctionRequests_tableからattendance_idを基に
    breakTimesの情報も併せて1件取得する
    条件としてステータスがpendingのもの
    */
    $correctionRequest = CorrectionRequest::with('breakTimes')
        ->where('attendance_id',$attendance_data->id)
        ->where('status','pending')
        ->first();

    $userName = $attendance_data->user->name;

    $dateYearLabel = Carbon::parse($attendance_data->date)->format('Y年');

    $dateMonthDayLabel = Carbon::parse($attendance_data->date)->format('n月j日');

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
    $inAtLabel = Carbon::parse($attendance_data->in_at)->format('H:i');
    $outAtLabel = Carbon::parse($attendance_data->out_at)->format('H:i');

    foreach($attendance_data->breakTimes as $index => $breakTime){
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

    $noteLabel = $attendance_data->note;
    }

    $isPending = $correctionRequest !== null;

    return view ('user.attendance_detail',compact('attendance_data','userName','dateYearLabel','dateMonthDayLabel','inAtLabel','outAtLabel','breakRows','noteLabel','isPending'));

}
}

