@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/partials/attendance_detail.css') }}">
<link rel="stylesheet" href="{{ asset('css/validation.css') }}">
@endsection

@section('header-menu')
@include('partials.header.admin')
@endsection

@section('content')
<form class="attendance-list {{ $isPending ? 'is-pending' : '' }}"
    action="{{ route('admin.attendance.update',['attendance_id' => $attendance->id]) }}"
    method="POST">
    @csrf

@include('partials.attendance.attendance_detail')


</form>
@endsection

@section('js')
<script src="{{ asset('js/common/break-row.js') }}"></script>
@endsection

