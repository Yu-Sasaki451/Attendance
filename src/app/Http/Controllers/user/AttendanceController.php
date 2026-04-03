<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use App\Services\DateService;
use App\Services\AttendanceCalculationService;
use App\Services\DetailService;
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
$currentTime = now()->format('H:i');

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
        Attendance::create ([
            'user_id' => auth()->user()->id,
            'date' => now()->toDateString(),
            'in_at' => now(),
        ]);
    }

    return redirect()->route('user.attendance');
}

//退勤打刻、更新処理
public function clockOut(){
    
    $attendance_data = $this->todayAttendance();

    //今日の勤怠レコードがあれば退勤保存
    if($attendance_data){
        $attendance_data->update(['out_at' => now()]);
    }

    return redirect()->route('user.attendance');
}

//休憩入り打刻、登録処理
public function breakStart(){

    $attendance_data = $this->todayAttendance();

    //今日の勤怠レコードがあれば休憩入り保存
    if($attendance_data){
        BreakTime::create([
            'attendance_id' => $attendance_data->id,
            'in_at' => now(),
        ]);
    }

    return redirect()->route('user.attendance');
}

//休憩終わり打刻、更新処理
public function breakEnd(){
    $attendance_data = $this->todayAttendance();

    //今日の勤怠レコードがあれば休憩時間レコードを降順で取得
    if($attendance_data){
        $breakTime_data = BreakTime::where('attendance_id',$attendance_data->id)
            ->orderBy('id','desc')
            ->first();

        //休憩レコードがある＆休憩レコードのout_atが入ってないならout_atを保存
        if($breakTime_data && $breakTime_data->out_at === null){
            $breakTime_data->update(['out_at' => now()]);
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

public function list(
    Request $request,
    DateService $dateService,
    AttendanceCalculationService $attendanceCalculationService){

    //ページのタイトル
    $pageTitle = '勤怠一覧';

    //URLにmonthがあればそれを使う、なければnowを整形
    $month = $request->month ?? now()->format('Y-m');

    //Serviceに$monthを渡して、処理結果を$monthDataに格納する
    $monthData = $dateService->getMonth($month);

    //Serviceのreturnで渡された値たち
    $currentMonthLabel = $monthData['currentMonthLabel'];
    $previousMonth = $monthData['previousMonth'];
    $nextMonth = $monthData['nextMonth'];
    $date = $monthData['date'];
    $lastDate = $monthData['lastDate'];


    $previousMonthUrl =  route('attendance.index',['month' => $previousMonth]);
    $nextMonthUrl = route('attendance.index',['month' => $nextMonth]);

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

        //$attendanceOfDayをサービスに渡して、結果を$attendance_dataに格納する
        $attendance_data = $attendanceCalculationService->attendance_data($attendanceOfDay);

        //Serviceのreturnで渡された値たち
        $breakMinutes = $attendance_data['breakMinutes'];
        $breakTimeLabel = $attendance_data['breakTimeLabel'];
        $workMinutes = $attendance_data['workMinutes'];
        $workTimeLabel = $attendance_data['workTimeLabel'];

        //$attendanceOfDayがあればURL作成
        $detail_url = $attendanceOfDay ? route('attendance.detail',['attendance_id' => $attendanceOfDay->id]) : null;

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
            'detail_url' => $detail_url,
        ];

        //計算結果に+1日して次の日にする
        $date->addDay();

    }

    return view('user.attendance_index',compact('pageTitle','currentMonthLabel','previousMonthUrl','nextMonthUrl','days'));

}

public function detail($attendance_id,DetailService $DetailService){

    $attendance = Attendance::with(['user','breakTimes'])
        ->where('id',$attendance_id)
        ->where('user_id', auth()->id())
        ->first();

    $correctionRequest = CorrectionRequest::with('breakTimes')
        ->where('attendance_id',$attendance->id)
        ->latest('created_at')
        ->first();

    //勤怠情報＆ユーザー情報と申請情報をサービスに渡して、処理結果を$detail_dataに格納する
    $detail_data = $DetailService->detailData($attendance,$correctionRequest);

    $correctionRequest = $detail_data['correctionRequest'];
    $userName = $detail_data['userName'];
    $dateYearLabel = $detail_data['dateYearLabel'];
    $dateMonthDayLabel = $detail_data['dateMonthDayLabel'];
    $breakRows = $detail_data['breakRows'];
    $inAtLabel = $detail_data['inAtLabel'];
    $outAtLabel = $detail_data['outAtLabel'];
    $noteLabel = $detail_data['noteLabel'];
    $isPending = $detail_data['isPending'];

    return view ('user.attendance_detail',compact('attendance','userName','dateYearLabel','dateMonthDayLabel','inAtLabel','outAtLabel','breakRows','noteLabel','isPending'));

}
}

