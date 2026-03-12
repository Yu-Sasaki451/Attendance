<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\User\CorrectionRequestController;
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

Route::middleware('guest')->group(function () {
    Route::view('/admin/login', 'auth.admin_login')->name('admin.login');
});

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/attendance',[UserAttendanceController::class,'index'])->name('user.attendance');

    Route::post('/attendance/clock-in', [UserAttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [UserAttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-start', [UserAttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [UserAttendanceController::class, 'breakEnd'])->name('attendance.break-end');

    Route::get('/attendance/list',[UserAttendanceController::class,'list'])->name('attendance.index');

    Route::get('/attendance/detail/{id}', [UserAttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [CorrectionRequestController::class, 'store'])->name('attendance.detail.update');

});

Route::middleware('auth')->group(function () {
    Route::get('/stamp_correction_request/list', function (Request $request) {
        if (auth()->user()->role === 'admin') {
            return app(AdminCorrectionRequestController::class)->correctionIndex($request);
        }

        return app(CorrectionRequestController::class)->correctionIndex($request);
    })->name('correction.index');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.index');
    Route::get('admin/attendance/{id}', [AdminAttendanceController::class, 'detail'])->name('admin.attendance.detail');
    Route::get('admin/staff/list', [AdminAttendanceController::class,'staff_list'])->name('staff.list');
    Route::get('admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staff_attendance'])->name('admin.staff.attendance');
    Route::post('/stamp_correction_request/approve/{id}',[AdminCorrectionRequestController::class,'approve'])->name('admin.correction.approve');
});
