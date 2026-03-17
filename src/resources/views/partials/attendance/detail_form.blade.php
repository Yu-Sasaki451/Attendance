<form class="attendance-list {{ $isPending ? 'is-pending' : '' }} {{ $isApproved ? 'is-approved' : '' }}"
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
            <th class="name-col">名前</th>
            <td class="name-col">
                <div class="detail-name-row">
                    <span class="detail-name-text">{{ $userName }}</span>
                </div>
            </td>
        </tr>
        <tr class="table-row">
            <th class="date-col">日付</th>
            <td class="date-col">
                <div class="detail-inline-row">
                    <span class="detail-date-text">{{ $dateYearLabel }}</span>
                    <span></span>
                    <span class="detail-date-text">{{ $dateMonthDayLabel }}</span>
                </div>
            </td>
        </tr>
        <tr class="table-row">
            <th class="in-col">出勤・退勤</th>
            <td class="in-col">
                <div class="detail-field">
                    <div class="detail-inline-row">
                        <input class="detail-input detail-input-time" type="time" name="in_at" value="{{ $inAtLabel }}" @readonly($isPending || $isApproved)>
                        <span class="detail-time-separator">〜</span>
                        <input class="detail-input detail-input-time" type="time" name="out_at" value="{{ $outAtLabel }}" @readonly($isPending || $isApproved)>
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
        <tr class="table-row">
            <th class="break-col">{{ $breakRow['label'] }}</th>
            <td class="break-col">
                <div class="detail-field">
                    <div class="detail-inline-row">
                        <input class="detail-input detail-input-time" type="time" name="break_in_at[]" value="{{ $breakRow['in_at'] }}" @readonly($isPending || $isApproved)>
                        <span class="detail-time-separator">〜</span>
                        <input class="detail-input detail-input-time" type="time" name="break_out_at[]" value="{{ $breakRow['out_at'] }}" @readonly($isPending || $isApproved)>
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
        <tr class="table-row">
            <th class="note-col">備考</th>
            <td class="note-col">
                <div class="detail-field">
                    <textarea class="detail-textarea" name="note" @readonly($isPending || $isApproved)>{{ $noteLabel }}</textarea>
                    <div class="validate-error">
                        @error('note')
                        {{ $message }}
                        @enderror
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if($isPending && $isAdmin)
        <button class="detail-button" type="submit">承認</button>
    @elseif($isApproved)
        <p class="detail-status-badge is-approved">承認済み</p>
    @elseif($isAdmin)
        <button class="detail-button" type="submit">修正</button>
    @elseif($isPending)
        <p class="detail-pending-text">* 承認待ちのため修正はできません。</p>
    @else
        <button class="detail-button" type="submit">修正</button>
    @endif


</form>
