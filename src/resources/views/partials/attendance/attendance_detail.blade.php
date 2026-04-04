<h1 class="page-title">勤怠詳細</h1>

    <table class="attendance-table">
        <tr class="table-row table-row--name">
            <th class="name-row">名前</th>
            <td class="name-row">
                <div class="detail-row">
                    <span class="detail-name-text">{{ $userName }}</span>
                </div>
            </td>
        </tr>
        <tr class="table-row">
            <th class="date-row">日付</th>
            <td class="date-row">
                <div class="detail-row">
                    <span class="detail-date-text">{{ $dateYearLabel }}</span>
                    <span></span>
                    <span class="detail-date-text">{{ $dateMonthDayLabel }}</span>
                </div>
            </td>
        </tr>
        <tr class="table-row">
            <th class="in-row">出勤・退勤</th>
            <td class="in-row">
                <div class="detail-field">
                    <div class="detail-row">
                        <input class="detail-input" type="time" name="in_at" value="{{ $inAtLabel }}" {{ $isPending ? 'readonly' : '' }}>
                        <span class="detail-time-separator">〜</span>
                        <input class="detail-input" type="time" name="out_at" value="{{ $outAtLabel }}" {{ $isPending ? 'readonly' : '' }}>
                    </div>
                    <div class="validate-error">
                        @error('attendance_time')
                        {{ $message }}
                        @enderror
                    </div>
                </div>
            </td>
        </tr>
        @foreach($breakRows as $breakRow)
        <tr class="table-row js-break-row">
            <th class="break-row">{{ $breakRow['label'] }}</th>
            <td class="break-row">
                <div class="detail-field">
                    <div class="detail-row">
                        <input class="detail-input js-break-in" type="time" name="break_in_at[]" value="{{ $breakRow['in_at'] }}" {{ $isPending ? 'readonly' : '' }}>
                        <span class="detail-time-separator">〜</span>
                        <input class="detail-input js-break-out" type="time" name="break_out_at[]" value="{{ $breakRow['out_at'] }}" {{ $isPending ? 'readonly' : '' }}>
                    </div>
                    <div class="validate-error">
                        @error("break_time.$loop->index")
                        {{ $message }}
                        @enderror
                    </div>
                </div>
            </td>
        </tr>
        @endforeach
        <tr class="table-row table-row--note js-note-row">
            <th class="note-row">備考</th>
            <td class="note-row">
                <div class="detail-field">
                    <textarea class="detail-textarea" name="note" {{ $isPending ? 'readonly' : '' }}>{{ $noteLabel }}</textarea>
                    <div class="validate-error">
                        @error('note')
                        {{ $message }}
                        @enderror
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if($isPending)
        <p class="detail-pending-text">* 承認待ちのため修正はできません。</p>
    @else
        <button class="detail-button" type="submit">修正</button>
    @endif