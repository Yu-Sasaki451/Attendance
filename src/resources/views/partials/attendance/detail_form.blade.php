<form class="attendance-list {{ $isPending ? 'is-pending' : '' }}"
    action="{{ $isAdmin
        ? ($isPending
            ? route('admin.correction.approve', ['id' => $correctionRequest->id])
            : route('admin.attendance.update',['id' => $attendance->id]))
        :route('attendance.detail.update', ['id' => $attendance->id]) }}"
    method="POST">
    @csrf
    <h1 class="page-title">勤怠詳細</h1>

    <table class="attendance-table">
        <tr class="table-row">
            <th class="name-row">名前</th>
            <td>{{ $userName }}</td>
        </tr>
        <tr class="table-row">
            <th class="date-col">日付</th>
            <td>{{ $dateLabel }}</td>
        </tr>
        <tr class="table-row">
            <th class="in-col">出勤・退勤</th>
            <td>
                <input class="detail-input detail-input-time" type="time" name="in_at" value="{{ $inAtLabel }}" @readonly($isPending)>
                <span>〜</span>
                <input class="detail-input detail-input-time" type="time" name="out_at" value="{{ $outAtLabel }}" @readonly($isPending)>
            </td>
        </tr>
        @foreach($breakRows as $breakRow)
        <tr class="table-row">
            <th class="break-col">{{ $breakRow['label'] }}</th>
            <td>
                <input class="detail-input detail-input-time" type="time" name="break_in_at[]" value="{{ $breakRow['in_at'] }}" @readonly($isPending)>
                <span> ~ </span>
                <input class="detail-input detail-input-time" type="time" name="break_out_at[]" value="{{ $breakRow['out_at'] }}" @readonly($isPending)>
            </td>
        </tr>
        @endforeach
        <tr class="table-row">
            <th>備考</th>
            <td>
                <textarea class="detail-textarea" name="note" @readonly($isPending)>{{ $noteLabel }}</textarea>
            </td>
        </tr>
    </table>

    @if($isPending && $isAdmin)
        <button class="detail-button" type="submit">承認</button>
    @elseif($isAdmin)
        <button class="detail-button" type="submit">修正</button>
    @elseif($isPending)
        <p class="detail-pending-text">* 承認待ちのため修正はできません。</p>
    @elseif($isApproved)
        <p class="detail-pending-text">* 承認済みです。</p>
    @else
        <button class="detail-button" type="submit">修正</button>
    @endif


</form>
