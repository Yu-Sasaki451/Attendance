<nav class="header-nav header-nav--user">
    <a class="nav-link" href="/attendance">勤怠</a>
    <a class="nav-link" href="/attendance/list">勤怠一覧</a>
    <a class="nav-link" href="/stamp_correction_request/list">申請</a>
    <form action="/logout" method="post">
        @csrf
        <input type="hidden" name="logout_from" value="user">
        <button class="nav-button" type="submit">ログアウト</button>
    </form>
</nav>
