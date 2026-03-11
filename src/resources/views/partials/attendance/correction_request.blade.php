<div class="request-list" data-tab-root>
    <h1 class="page-title">申請一覧</h1>
    <div class="tablist" role="tablist">
        <button 
            class="tab-button"
            type="button"
            role="tab"
            id="tab-pending"
            aria-controls="panel-pending"
            aria-selected="{{ $activeTab === 'pending' ? 'true' : 'false' }}"
            tabindex="{{ $activeTab === 'pending' ? '0' : '-1' }}"
        >
            承認待ち
        </button>

        <button 
            class="tab-button"
            type="button"
            role="tab"
            id="tab-approved"
            aria-controls="panel-approved"
            aria-selected="{{ $activeTab === 'approved' ? 'true' : 'false' }}"
            tabindex="{{ $activeTab === 'approved' ? '0' : '-1' }}"
        >
            承認済み
        </button>
    </div>
    <div
        class="tab-panel"
        role="tabpanel"
        id="panel-pending"
        aria-labelledby="tab-pending"
        tabindex="0" {{ $activeTab === 'pending' ? '' : 'hidden' }}>
        <div>
            <table class="request-list__table">
                <tr class="table-header__row">
                    <th class="status-col">状態</th>
                    <th class="name-col">名前</th>
                    <th class="date-col">対象日時</th>
                    <th class="reason-col">申込理由</th>
                    <th class="request-col">申込日時</th>
                    <th class="detail-col">詳細</th>
                </tr>
                @foreach($pendingRequests as $pendingRequest)
                <tr class="table-row">
                    <td class="status-col">{{ $pendingRequest['status_label'] }}</td>
                    <td class="name-col">{{ $pendingRequest['user_name'] }}</td>
                    <td class="date-col">{{ $pendingRequest['target_date'] }}</td>
                    <td class="reason-col">{{ $pendingRequest['reason'] }}</td>
                    <td class="request-col">{{ $pendingRequest['applied_date'] }}</td>
                    <td class="detail-col"><a href="{{ $pendingRequest['detail_url'] }}">詳細</a></td>
                </tr>
                @endforeach
            </table>
        </div>

    </div>
    <div
        class="tab-panel"
        role="tabpanel"
        id="panel-approved"
        aria-labelledby="tab-approved"
        tabindex="0" {{ $activeTab === 'approved' ? '' : 'hidden' }}>

        <div>
            <table class="request-list__table">
                <tr class="table-header__row">
                    <th class="status-col">状態</th>
                    <th class="name-col">名前</th>
                    <th class="date-col">対象日時</th>
                    <th class="reason-col">申込理由</th>
                    <th class="request-col">申込日時</th>
                    <th class="detail-col">詳細</th>
                </tr>
                @foreach($approvedRequests as $approvedRequest)
                <tr class="table-row">
                    <td class="status-col">{{ $approvedRequest['status_label'] }}</td>
                    <td class="name-col">{{ $approvedRequest['user_name'] }}</td>
                    <td class="date-col">{{ $approvedRequest['target_date'] }}</td>
                    <td class="reason-col">{{ $approvedRequest['reason'] }}</td>
                    <td class="request-col">{{ $approvedRequest['applied_date'] }}</td>
                    <td class="detail-col"><a href="{{ $approvedRequest['detail_url'] }}">詳細</a></td>
                </tr>
                @endforeach
            </table>
        </div>

    </div>
</div>
