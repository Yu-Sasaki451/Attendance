const { initializeTabSwitch } = require('../../public/js/common/tab-switch');

describe('tab-switch', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div data-tab-root>
                <div role="tablist">
                    <button
                        type="button"
                        role="tab"
                        id="tab-pending"
                        aria-controls="panel-pending"
                        aria-selected="true"
                        tabindex="0"
                    >
                        承認待ち
                    </button>
                    <button
                        type="button"
                        role="tab"
                        id="tab-approved"
                        aria-controls="panel-approved"
                        aria-selected="false"
                        tabindex="-1"
                    >
                        承認済み
                    </button>
                </div>

                <div id="panel-pending" role="tabpanel" aria-labelledby="tab-pending">
                    pending panel
                </div>
                <div id="panel-approved" role="tabpanel" aria-labelledby="tab-approved" hidden>
                    approved panel
                </div>
            </div>
        `;
    });

    test('クリックしたタブに対応するパネルが表示される', () => {
        initializeTabSwitch(document);

        const pendingTab = document.getElementById('tab-pending');
        const approvedTab = document.getElementById('tab-approved');
        const pendingPanel = document.getElementById('panel-pending');
        const approvedPanel = document.getElementById('panel-approved');

        approvedTab.click();

        expect(approvedTab.getAttribute('aria-selected')).toBe('true');
        expect(approvedTab.getAttribute('tabindex')).toBe('0');
        expect(pendingTab.getAttribute('aria-selected')).toBe('false');
        expect(pendingTab.getAttribute('tabindex')).toBe('-1');
        expect(approvedPanel.hidden).toBe(false);
        expect(pendingPanel.hidden).toBe(true);
    });
});
