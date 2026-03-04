@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendance_index.css') }}">
@endsection

@section('header-menu')
<nav class="header-nav">
    <a class="nav-link" href="/attendance">勤怠</a>
    <a class="nav-link" href="/attendance/list">勤怠一覧</a>
    <a class="nav-link" href="/stamp_correction_request/list">申請</a>
    <form action="/logout" method="post">
        @csrf
        <input type="hidden" name="logout_from" value="user">
        <button class="nav-button" type="submit">ログアウト</button>
    </form>
</nav>
@endsection

@section('content')
<div class="attendance-list">
    <h1 class="page-title">勤怠詳細</h1>

    @php
        $breakTimes = $attendance->breakTimes->values();
        $firstBreak = $breakTimes->get(0);
        $secondBreak = $breakTimes->get(1);
    @endphp

    <table class="attendance-table">
        <tr class="table-row">
            <th class="date-col">日付</th>
            <td>{{ \Carbon\Carbon::parse($attendance->date)->format('Y/m/d') }}</td>
        </tr>
        <tr class="table-row">
            <th class="in-col">出勤・退勤</th>
            <td>
                {{ $attendance->in_at ? \Carbon\Carbon::parse($attendance->in_at)->format('H:i') : '' }}
                〜
                {{ $attendance->out_at ? \Carbon\Carbon::parse($attendance->out_at)->format('H:i') : '' }}
            </td>
        </tr>
        <tr class="table-row">
            <th class="break-col">休憩時間</th>
            <td>
                @if($firstBreak && $firstBreak->in_at && $firstBreak->out_at)
                {{ \Carbon\Carbon::parse($firstBreak->in_at)->format('H:i') }} 〜 {{ \Carbon\Carbon::parse($firstBreak->out_at)->format('H:i') }}
                @endif
            </td>
        </tr>
        <tr class="table-row">
            <th class="break-col">休憩時間2</th>
            <td>
                @if($secondBreak && $secondBreak->in_at && $secondBreak->out_at)
                {{ \Carbon\Carbon::parse($secondBreak->in_at)->format('H:i') }} 〜 {{ \Carbon\Carbon::parse($secondBreak->out_at)->format('H:i') }}
                @endif
            </td>
        </tr>
        <tr class="table-row">
            <th>備考</th>
            <td>{{ $attendance->note }}</td>
        </tr>
    </table>
</div>
@endsection
