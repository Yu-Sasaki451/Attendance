@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/admin_login.css') }}">
<link rel="stylesheet" href="{{ asset('css/validate.css') }}">
@endsection

@section('content')
<form class="admin-login" action="/login" method="post">
    @csrf
    <input type="hidden" name="login_type" value="admin">
    
    <div class="admin-login__inner">
        <h1 class="admin-login__header">ログイン</h1>
        <span class="admin-login__span">メールアドレス</span>
        <input class="admin-login__input" type="text" name="email" value="{{ old('email') }}">
        <div class="form-error">
            @error('email')
            {{ $message }}
            @enderror
        </div>
        <span class="admin-login__span">パスワード</span>
        <input class="admin-login__input" type="password" name="password">
        <div class="form-error">
            @error('password')
            {{ $message }}
            @enderror
        </div>

        <button class="admin-login__button">管理者ログインする</button>
    </div>
</form>

@endsection