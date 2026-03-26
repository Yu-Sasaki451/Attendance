@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
@endsection

@section('header-menu')
@include('partials.header.admin')
@endsection

@section('content')
<div class="attendance-list">
    <h1 class="page-title">{{ $pageTitle }}</h1>

    <div class="date-nav">
        <a class="date-link"
        href="/admin/attendance/list?date={{ $previousDate }}">← 前日</a>
        <p class="date-current">{{ $currentDateLabel }}</p>
        <a class="date-link"
        href="/admin/attendance/list?date={{ $nextDate }}">翌日 →</a>
    </div>

    <table class="attendance-table">
        <tr class="table-title">
            <th class="name-col">名前</th>
            <th class="in-col">出勤</th>
            <th class="out-col">退勤</th>
            <th class="break-col">休憩</th>
            <th class="sum-col">合計</th>
            <th class="detail-col">詳細</th>
        </tr>
        @foreach($rows as $row)
        <tr class="table-row">
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['in_at'] }}</td>
            <td>{{ $row['out_at'] }}</td>
            <td>{{ $row['break_time'] }}</td>
            <td>{{ $row['work_time'] }}</td>
            <td>
                @if($row['id'])
                <a class="link-detail" href="/admin/attendance/{{ $row['id'] }}">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
