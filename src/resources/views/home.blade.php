@extends('layouts.app')



@section('content')
ログイン完了

<form action="/logout" method="post">
    @csrf
    <button type="submit">ログアウト</button>
</form>
@endsection