@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user/attendance.css') }}">
@endsection

@section('header-menu')
@include('partials.header.user')
@endsection

@section('content')

<div class="attendance">
    <p class="attendance-status">{{ $status }}</p>
    <p class="info-today">{{ $today }}（{{ $weekDay }}）</p>
    <p class="info-now">{{ $currentTime }}</p>

    @if ($message)
        <p class="attendance-message">{{ $message }}</p>
    @endif
    <div class="attendance-buttons">
        @foreach ($buttons as $button)
            <form action="{{ $button['route'] }}" method="post">
                @csrf
                <button class="attendance-button attendance-button--{{ $button['type'] }}" type="submit">{{ $button['label'] }}</button>
            </form>
        @endforeach
    </div>
</div>

@endsection
