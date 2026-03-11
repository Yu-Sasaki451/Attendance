@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/partials/attendance_monthly_list.css') }}">
@endsection

@section('header-menu')
@include('partials.header.admin')
@endsection

@section('content')
@include('partials.attendance.monthly_list', [
    'pageTitle' => $pageTitle,
    'currentMonthLabel' => $currentMonthLabel,
    'previousMonthUrl' => route('admin.staff.attendance', ['id' => $staffId, 'month' => $previousMonth]),
    'nextMonthUrl' => route('admin.staff.attendance', ['id' => $staffId, 'month' => $nextMonth]),
    'detailRouteName' => 'admin.attendance.detail',
    'days' => $days,
])
@endsection
