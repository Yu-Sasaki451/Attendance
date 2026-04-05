//テストしたい関数名、対象となるjsを読み込む
const { initializeTabSwitch } = require('../../public/js/common/tab-switch');

//テストしたいHTML環境を再現
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
        //テストしたい関数名
        initializeTabSwitch(document);

        //定数を定義、このidを使うよと宣言
        const pendingTab = document.getElementById('tab-pending');
        const approvedTab = document.getElementById('tab-approved');
        const pendingPanel = document.getElementById('panel-pending');
        const approvedPanel = document.getElementById('panel-approved');

        //承認済みのタブをクリックする処理
        approvedTab.click();

        //期待される反応を記述  expect A to Bで　Aの結果がBと等しいか確認してる
        expect(approvedTab.getAttribute('aria-selected')).toBe('true');
        expect(approvedTab.getAttribute('tabindex')).toBe('0');
        expect(pendingTab.getAttribute('aria-selected')).toBe('false');
        expect(pendingTab.getAttribute('tabindex')).toBe('-1');
        expect(approvedPanel.hidden).toBe(false);
        expect(pendingPanel.hidden).toBe(true);
    });
});
