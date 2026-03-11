@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/correction_request_index.css') }}">
@endsection

@section('header-menu')
@include('partials.header.admin')
@endsection

@section('content')
@include('partials.attendance.correction_request')
@endsection

@section('js')
<script src="{{ asset('js/common/tab-switch.js') }}"></script>
@endsection
