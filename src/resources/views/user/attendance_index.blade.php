@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/partials/attendance_monthly_list.css') }}">
@endsection

@section('header-menu')
@include('partials.header.user')
@endsection

@section('content')
<div class="attendance-list">
    <h1 class="page-title">{{ $pageTitle }}</h1>

    <div class="month-nav">
        <a class="month-link" href="/attendance/list?month={{ $previousMonth }}">← 前月</a>
        <p class="month-current">{{ $currentMonthLabel }}</p>
        <a class="month-link" href="/attendance/list?month={{ $nextMonth }}">翌月 →</a>
    </div>

    <table class="attendance-table">
        <tr class="table-title">
            <th class="date-col">日付</th>
            <th class="in-col">出勤</th>
            <th class="out-col">退勤</th>
            <th class="break-col">休憩</th>
            <th class="sum-col">合計</th>
            <th class="detail-col">詳細</th>
        </tr>
        @foreach($days as $day)
        <tr class="table-row">
            <td>{{ $day['dateLabel'] }} ({{ $day['weekLabel'] }})</td>
            <td>{{ $day['in_at'] }}</td>
            <td>{{ $day['out_at'] }}</td>
            <td>{{ $day['break_time'] }}</td>
            <td>{{ $day['work_time'] }}</td>
            <td>
                @if($day['id'])
                <a class="link-detail" href="{{route('attendance.detail',['id' => $day->id]) }}">詳細</a>
                @else
                <span class="link-detail">詳細</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
