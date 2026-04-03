<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Models\BreakTime;
use App\Services\DateService;
use App\Services\AttendanceCalculationService;
use App\Services\DetailService;
use App\Services\BreakCalculationService;
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
        $detail_url = route('admin.attendance.detail',['attendance_id' => $attendance->id]);

        $rows [] =[
        'name' => $attendance->user->name,
        'in_at' => $attendance->in_at ? Carbon::parse($attendance->in_at)->format('H:i') :null,
        'out_at' => $attendance->out_at ? Carbon::parse($attendance->out_at)->format('H:i') :null,
        'break_time' => $breakMinutes === 0 ? null : $breakTimeLabel,
        'work_time' => $workTimeLabel,
        'detail_url' => $detail_url,
    ];
    }

    return view('admin.attendance',compact('pageTitle','currentDateLabel','previousDateUrl','nextDateUrl','rows'));
}

//勤怠詳細表示
public function detail($attendance_id,DetailService $DetailService){

    //URLのIDを元に勤怠＆ユーザー情報、紐づく休憩情報を1件取得
    $attendance = Attendance::with('user','breakTimes')
        ->where('id',$attendance_id)
        ->first();

    //勤怠IDを元に申請情報と紐づく休憩情報(最新)を1件取得
    $correctionRequest = CorrectionRequest::with('breakTimes')
    ->where('attendance_id',$attendance->id)
    ->where('status','pending')
    ->first();

    //2つの変数をサービスに渡して、処理結果を$detail_dataに格納する
    $detail_data = $DetailService->detailData($attendance,$correctionRequest);

    //Serviceのreturnで渡された値たち
    $correctionRequest = $detail_data['correctionRequest'];
    $userName = $detail_data['userName'];
    $dateYearLabel = $detail_data['dateYearLabel'];
    $dateMonthDayLabel = $detail_data['dateMonthDayLabel'];
    $breakRows = $detail_data['breakRows'];
    $inAtLabel = $detail_data['inAtLabel'];
    $outAtLabel = $detail_data['outAtLabel'];
    $noteLabel = $detail_data['noteLabel'];
    $isPending = $detail_data['isPending'];

    return view('admin.attendance_detail',compact('attendance','userName','dateYearLabel','dateMonthDayLabel','inAtLabel','outAtLabel','breakRows','noteLabel','isPending'));
}

//スタッフ一覧表示
public function staff_list(){

    //roleがユーザーの情報を全て取得
    $users = User::where('role','user')->get();

    //foreachで回す前に空配列を用意
    $staffs = [];

    //ユーザー情報とURLを１件ずつ$staffsに格納
    foreach($users as $user){

    $detail_url = route('admin.staff.attendance',['user_id' => $user->id]);

    $staffs[] = [
        'name' => $user->name,
        'email' => $user->email,
        'detail_url' => $detail_url,
    ];
    }


    return view('admin.staff_index',compact('staffs'));
}

//スタッフの月別勤怠表示
public function staff_attendance(
    Request $request,
    $user_id,
    DateService $dateService,
    AttendanceCalculationService $attendanceCalculationService){

    //URLにmonthがあればそれを使う、なければnowを整形
    $month = $request->month ?? now()->format('Y-m');

    //URLのIDを基にユーザー情報を取得
    $user = User::where('id',$user_id)->first();

    //$monthをサービスに渡して、処理結果を$monthDataに格納する
    $monthData = $dateService->getMonth($month);

    //サービスから渡されてきたものたち
    $date = $monthData['date'];
    $lastDate = $monthData['lastDate'];
    $currentMonthLabel = $monthData['currentMonthLabel'];
    $previousMonth = $monthData['previousMonth'];
    $nextMonth = $monthData['nextMonth'];

    //月を切り替えるURL作成
    $previousMonthUrl = route('admin.staff.attendance',['user_id'=> $user->id,'month'=> $previousMonth]);
    $nextMonthUrl = route('admin.staff.attendance',['user_id'=> $user->id,'month'=> $nextMonth]);

    $pageTitle = $user->name .'さんの勤怠';

    //$userのIDを使って該当するユーザーの勤怠情報を全て取得
    $attendance = Attendance::with('breakTimes')
                ->where('user_id',$user->id)
                ->whereBetween('date',[$date,$lastDate])
                ->get();


    $week = ['日','月','火','水','木','金','土',];
    $days = [];

    while ($date <= $lastDate){

        //1日分の勤怠情報(最新)を取得
        $attendanceOfDay = $attendance->sortByDesc('updated_at')->firstWhere('date',$date->format('Y-m-d'));

        //1日分の勤怠情報をサービスに渡して、処理結果を$attendance_dataに格納する
        $attendance_data = $attendanceCalculationService->attendance_data($attendanceOfDay);

        //サービスから渡された処理結果たち
        $breakMinutes = $attendance_data['breakMinutes'];
        $breakTimeLabel = $attendance_data['breakTimeLabel'];
        $workMinutes = $attendance_data['workMinutes'];
        $workTimeLabel = $attendance_data['workTimeLabel'];

        //1日分の勤怠情報がある場合はURLを作成する
        $detail_url = $attendanceOfDay ? route('admin.attendance.detail',['attendance_id' => $attendanceOfDay->id]) : null;

        /*
        勤怠の該当カラムに値があるかどうかを確認してから、整形する
        休憩時間は0ならNUll
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

        $date->addDay();

    }

    $csvExport_url = route('admin.staff.attendance.csv',['user_id' => $user_id,'month' => $month]);


    return view('admin.staff_attendance',compact('user','pageTitle','currentMonthLabel','previousMonthUrl','nextMonthUrl','days','csvExport_url'));
}

//勤怠の手動修正
public function update(
    AttendanceDetailRequest $request,
    $attendance_id,
    BreakCalculationService $breakCalculationService){

    //$requestの中身を全部取得
    $request_data = $request->all();

    //URLのIDを基に勤怠＆ユーザー＆休憩情報の最新版を1件取得
    $attendance = Attendance::with('user','breakTimes')
            ->where('id',$attendance_id)
            ->latest('updated_at')
            ->first();

    //
    $breakRows = $breakCalculationService->break_array($request_data);


    //トランザクション->どれか一つでも処理が失敗したら元に戻る
    DB::transaction(function () use($attendance,$request_data,$breakRows){

    Attendance::where('id',$attendance->id)->update([
        'in_at' => $attendance->date .' '. $request_data['in_at'], //2026-03-01 09:00　の形に整形
        'out_at' => $attendance->date . ' '. $request_data['out_at'], //2026-03-01 09:00　の形に整形
        'note' => $request_data['note'],
    ]);

    $attendance->breakTimes()->delete();

    foreach($breakRows as $index => $breakRow){
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'in_at' => $attendance->date .' '. $breakRow['in_at'],
            'out_at' => $attendance->date .' '. $breakRow['out_at'],
        ]);
    }
    });

    return redirect()->route('admin.index');
}


public function exportCsv(
    Request $request,
    $user_id,
    DateService $dateService,
    AttendanceCalculationService $attendanceCalculationService){

    //ブレードに表示するのと同じようにデータを用意
    $month = $request->month ?? now()->format('Y-m');
    $monthData = $dateService->getMonth($month);
    $date = $monthData['date'];
    $lastDate = $monthData['lastDate'];

    $attendance = Attendance::with('user','breakTimes')
                ->where('user_id',$user_id)
                ->whereBetween('date',[$date,$lastDate])
                ->get();

    $days = [];

    while ($date <= $lastDate){

        //1日分の勤怠情報(最新)を取得
        $attendanceOfDay = $attendance->sortByDesc('updated_at')->firstWhere('date',$date->format('Y-m-d'));

        //1日分の勤怠情報をサービスに渡して、処理結果を$attendance_dataに格納する
        $attendance_data = $attendanceCalculationService->attendance_data($attendanceOfDay);

        //サービスから渡された処理結果たち
        $breakMinutes = $attendance_data['breakMinutes'];
        $breakTime = $attendance_data['breakTimeLabel'];
        $workMinutes = $attendance_data['workMinutes'];
        $workTime = $attendance_data['workTimeLabel'];

        /*
        勤怠の該当カラムに値があるかどうかを確認してから、整形する
        休憩時間は0ならNUll
        */
        $days[] =[
            'date' => $date->format('Y/m/d'),
            'in_at' => $attendanceOfDay?->in_at ? Carbon::parse($attendanceOfDay->in_at)->format('H:i') : null,
            'out_at' => $attendanceOfDay?->out_at ? Carbon::parse($attendanceOfDay->out_at)->format('H:i') : null,
            'break_time' => $breakMinutes === 0 ? null : $breakTime,
            'work_time' => $workTime,
        ];

        $date->addDay();

    }

    //csvに書き込むための一時保存用変数
    $csvContent = fopen('php://temp', 'r+');

    //csvの見出しに使う
    $csvHeader = ['日付','出勤時間','退勤時間','休憩時間','勤務時間'];

    //第一引数の中に第二引数を書く
    fputcsv($csvContent,$csvHeader);

    //第一引数$csvContentの中に第二引数の$dayを書くのをforeachで回す
    foreach($days as $day){
        fputcsv($csvContent,[
            $day['date'],
            $day['in_at'],
            $day['out_at'],
            $day['break_time'],
            $day['work_time'],
        ]);
    }
    //読み取り位置を先頭に戻す
    rewind($csvContent);

    //現在の読み取り位置から最後までを文字列(UTF-8)として取得する
    $csvData = stream_get_contents($csvContent);

    //UTF-8になってる$csvDataをSJIS-winに変換
    $sjisData = mb_convert_encoding($csvData,'SJIS-win','UTF-8');

    //一時保存用変数を閉じる
    fclose($csvContent);

    //変換された$sjisDataを返す
    return response($sjisData, 200, [
    //これはcsv形式ですよーと言ってる
    'Content-Type' => 'text/csv',

    //アタッチメントでファイル名を設定
    'Content-Disposition' => 'attachment; filename="' . $month . '勤怠.csv"',
]);

}

}