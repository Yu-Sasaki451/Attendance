<nav class="header-nav header-nav--admin">
    <a class="nav-link" href="/admin/attendance/list">勤怠一覧</a>
    <a class="nav-link" href="/admin/staff/list">スタッフ一覧</a>
    <a class="nav-link" href="/admin/stamp_correction_request/list">申請一覧</a>
    <form action="/logout" method="post">
        @csrf
        <input type="hidden" name="logout_from" value="admin">
        <button class="nav-button" type="submit">ログアウト</button>
    </form>
</nav>
