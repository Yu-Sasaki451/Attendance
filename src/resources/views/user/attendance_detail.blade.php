@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/partials/attendance_detail_form.css') }}">
@endsection

@section('header-menu')
@include('partials.header.user')
@endsection

@section('content')
@include('partials.attendance.detail_form')
@endsection
