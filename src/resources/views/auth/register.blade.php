@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
<link rel="stylesheet" href="{{ asset('css/validate.css') }}">
@endsection

@section('content')
<form class="register-form" action="/register" method="post">
    @csrf
    <div class="register-form__inner">
        <h1 class="register-form__header">会員登録</h1>

        <span class="register-form__span">名前</span>
        <input class="register-form__input" type="text" name="name" value="{{ old('name') }}">
        <div class="form-error">
            @error('password')
            {{ $message }}
            @enderror
        </div>

        <span class="register-form__span">メールアドレス</span>
        <input class="register-form__input" type="text" name="email" value="{{ old('email') }}">
        <div class="form-error">
            @error('password')
            {{ $message }}
            @enderror
        </div>

        <span class="register-form__span">パスワード</span>
        <input class="register-form__input" type="password" name="password">
        <div class="form-error">
            @error('password')
            {{ $message }}
            @enderror
        </div>

        <span class="register-form__span">パスワード確認</span>
        <input class="register-form__input" type="password" name="password_confirmation">
        <div class="form-error">
            @error('password')
            {{ $message }}
            @enderror
        </div>

        <button class="register-form__button">登録する</button>
        <a class="link-register" href="/login">ログインはこちら</a>
    </div>
</form>

@endsection