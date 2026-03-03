@extends('layouts.app')



@section('content')
ユーザーログイン完了

<form action="/logout" method="post">
    @csrf
    <input type="hidden" name="logout_from" value="user">
    <button type="submit">ログアウト</button>
</form>
@endsection