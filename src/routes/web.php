<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\User\CorrectionRequestController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;


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

    Route::get('/stamp_correction_request/list',[CorrectionRequestController::class,'correctionIndex'])->name('correction.index');

});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.index');
    Route::get('admin/attendance/{id}', [AdminAttendanceController::class, 'detail'])->name('admin.attendance.detail');
});
