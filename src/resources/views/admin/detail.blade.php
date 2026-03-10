@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/detail.css') }}">
@endsection

@section('header-menu')
@include('partials.header.admin')
@endsection

@section('content')
@include('partials.attendance.detail_form')
@endsection
