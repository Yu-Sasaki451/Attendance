@extends('layouts.app')



@section('content')
管理者ログイン完了

<form action="/logout" method="post">
    @csrf
    <input type="hidden" name="logout_from" value="admin">
    <button type="submit">ログアウト</button>
</form>
@endsection