@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_index.css') }}">
@endsection

@section('header-menu')
@include('partials.header.admin')
@endsection

@section('content')
<div class="staff-list">
    <h1 class="page-title">スタッフ一覧</h1>

    <table class="staff-table">
        <tr class="table-title">
            <th class="name-col">名前</th>
            <th class="email-col">メールアドレス</th>
            <th class="detail-col">月次勤怠</th>
        </tr>
        @foreach($staffs as $staff)
        <tr class="table-row">
            <td>{{ $staff['name'] }}</td>
            <td>{{$staff['email'] }}</td>
            <td><a class="link-detail" href="{{$staff['detailUrl']}}">詳細</a></td>
        </tr>
        @endforeach
    </table>
</div>
@endsection