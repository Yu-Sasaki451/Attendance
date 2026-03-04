<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>
<body class="body">
    <header class="header">
        <div class="header-inner--left">
            <img class="header-logo" src="{{ asset('COACHTECHヘッダーロゴ.png') }}" alt="">
        </div>
        <div class="header-inner--right">
            @yield('header-menu')
        </div>
    </header>
    <main class="main">
        @yield('content')
    </main>
</body>
</html>
