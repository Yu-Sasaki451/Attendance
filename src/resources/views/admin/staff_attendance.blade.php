@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/partials/attendance_index.css') }}">
@endsection

@section('header-menu')
@include('partials.header.admin')
@endsection

@section('content')
@include('partials.attendance.attendance_index')

    <div class="export-button">
        <a class="export-link" href="{{ $csvExport_url }}">CSV出力</a>
    </div>

@endsection
