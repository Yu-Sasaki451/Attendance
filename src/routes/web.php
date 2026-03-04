<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AttendanceController;

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
    Route::get('/attendance',[AttendanceController::class,'index'])->name('user.attendance');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');

    Route::get('/attendance/list',[AttendanceController::class,'list'])->name('attendance.index');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::view('/admin/attendance/list','admin.index')->name('admin.index');
});
