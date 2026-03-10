<form class="attendance-list {{ $isPending ? 'is-pending' : '' }}" action="{{ route('attendance.detail.update', ['id' => $attendance->id]) }}" method="POST">
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
                @if($isPending)
                <span class="detail-static-time">{{ $inAtLabel }}</span>
                @else
                <input class="detail-input detail-input-time" type="text" name="in_at" value="{{ $inAtLabel }}" @disabled($isPending)>
                @endif
                〜
                @if($isPending)
                <span class="detail-static-time">{{ $outAtLabel }}</span>
                @else
                <input class="detail-input detail-input-time" type="text" name="out_at" value="{{ $outAtLabel }}" @disabled($isPending)>
                @endif
            </td>
        </tr>
        @foreach($breakRows as $breakRow)
        <tr class="table-row">
            <th class="break-col">{{ $breakRow['label'] }}</th>
            <td>
                @if($isPending)
                <span class="detail-static-time">{{ $breakRow['in_at'] }}</span>
                @else
                <input class="detail-input detail-input-time" type="text" name="break_in_at[]" value="{{ $breakRow['in_at'] }}" @disabled($isPending)>
                @endif
                〜
                @if($isPending)
                <span class="detail-static-time">{{ $breakRow['out_at'] }}</span>
                @else
                <input class="detail-input detail-input-time" type="text" name="break_out_at[]" value="{{ $breakRow['out_at'] }}" @disabled($isPending)>
                @endif
            </td>
        </tr>
        @endforeach
        <tr class="table-row">
            <th>備考</th>
            <td>
                @if($isPending)
                <p class="detail-static-note">{{ $attendance->note }}</p>
                @else
                <textarea class="detail-textarea" name="note" @disabled($isPending)>{{ $attendance->note }}</textarea>
                @endif
            </td>
        </tr>
    </table>

    @if($isPending)
    <p class="detail-pending-text">* 承認待ちのため修正はできません。</p>
    @else
    <button class="detail-button" type="submit">修正</button>
    @endif
</form>
