@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/partials/attendance_monthly_list.css') }}">
@endsection

@section('header-menu')
@include('partials.header.user')
@endsection

@section('content')
@include('partials.attendance.monthly_list', [
    'pageTitle' => $pageTitle,
    'currentMonthLabel' => $currentMonthLabel,
    'previousMonthUrl' => route('attendance.index', ['month' => $previousMonth]),
    'nextMonthUrl' => route('attendance.index', ['month' => $nextMonth]),
    'detailRouteName' => 'attendance.detail',
    'days' => $days,
])
@endsection
