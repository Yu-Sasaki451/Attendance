<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\User\CorrectionRequestController as UserCorrectionRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\CorrectionRequestController as AdminCorrectionRequestController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//ユーザー
Route::middleware(['auth', 'verified', 'role:user'])->group(function () {
    Route::get('/attendance',[UserAttendanceController::class,'index'])->name('user.attendance');

    Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-start', [UserAttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [UserAttendanceController::class, 'breakEnd'])->name('attendance.break-end');

    Route::get('/attendance/list',[UserAttendanceController::class,'list'])->name('attendance.index');

    Route::get('/attendance/detail/{id}', [UserAttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [UserCorrectionRequestController::class, 'store'])->name('attendance.detail.update');
});

/*
ログインしてるか、メール認証してるかをチェック
その後、URLにアクセスしたら無名関数を実行
ログインしてるのがadminならadminのメソッドを実行し
違うならユーザーのメソッドを実行
*/
Route::middleware(['auth','verified'])->get('/stamp_correction_request/list',function(){
    if(auth()->user()->role === 'admin'){
        return app(AdminCorrectionRequestController::class)->correctionIndex();
    }
    return app(UserCorrectionRequestController::class)->correctionIndex();
})->name('correction.index');

//管理者のログイン画面
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login');
});

//管理者としてログイン
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.index');
    Route::get('admin/attendance/{id}', [AdminAttendanceController::class, 'detail'])->name('admin.attendance.detail');
    Route::post('admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('admin/staff/list', [AdminAttendanceController::class,'staff_list'])->name('staff.list');
    Route::get('admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staff_attendance'])->name('admin.staff.attendance');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}',
            [AdminCorrectionRequestController::class, 'showRequest'])->name('admin.correction.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}',
            [AdminCorrectionRequestController::class,'approveRequest'])->name('admin.correction.approve');
    Route::get('admin/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportStaffAttendanceCsv'])
    ->name('admin.staff.attendance.csv');
});
