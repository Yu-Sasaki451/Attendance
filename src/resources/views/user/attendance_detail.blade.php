@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendance_detail.css') }}">
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
<form class="attendance-list" action="{{ route('attendance.detail.update', ['id' => $attendance->id]) }}" method="POST">
    @csrf
    <h1 class="page-title">勤怠詳細</h1>

    <table class="attendance-table">
        <tr class="table-row">
            <th class="name-row">名前</th>
            <td>{{ $userName }}</td>
        </tr>
        <tr class="table-row">
            <th class="date-col">日付</th>
            <td>{{ $dateLabel }}</td>
        </tr>
        <tr class="table-row">
            <th class="in-col">出勤・退勤</th>
            <td>
                <input class="detail-input detail-input-time" type="text" name="in_at" value="{{ $inAtLabel }}">
                〜
                <input class="detail-input detail-input-time" type="text" name="out_at" value="{{ $outAtLabel }}">
            </td>
        </tr>
        @foreach($breakRows as $breakRow)
        <tr class="table-row">
            <th class="break-col">{{ $breakRow['label'] }}</th>
            <td>
                <input class="detail-input detail-input-time" type="text" name="break_in_at[]" value="{{ $breakRow['in_at'] }}">
                〜
                <input class="detail-input detail-input-time" type="text" name="break_out_at[]" value="{{ $breakRow['out_at'] }}">
            </td>
        </tr>
        @endforeach
        <tr class="table-row">
            <th>備考</th>
            <td>
                <textarea class="detail-textarea" name="note">{{ $attendance->note }}</textarea>
            </td>
        </tr>
    </table>

    <button class="detail-button" type="submit">修正</button>
</form>
@endsection
