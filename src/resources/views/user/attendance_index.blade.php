@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/partials/attendance_index.css') }}">
@endsection

@section('header-menu')
@include('partials.header.user')
@endsection

@section('content')
@include('partials.attendance.attendance_index')
@endsection
