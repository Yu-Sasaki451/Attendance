//対象のjsを読み込む
require('../../public/js/common/break-row');

//テストしたいHTML環境を作る、
describe('break-row', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <table>
            <tbody>
                <tr class="js-break-row">
                    <td class="break-row">休憩1</td>
                    <td>
                        <input class="js-break-in" type="time" value="">
                    </td>
                    <td>
                        <input class="js-break-out" type="time" value="">
                    </td>
                    <td>
                        <div class="validate-error">エラー</div>
                    </td>
                </tr>
                <tr class="js-note-row">
                    <td>備考</td>
                </tr>
            </tbody>
            </table>
            `;
    });

    //テスト内容
    test('最後の休憩行の入りと戻りの時刻が入力されると新しい行が追加される', () => {

        //ここを使うよという宣言
        const breakIn = document.querySelector('.js-break-in');
        const breakOut = document.querySelector('.js-break-out');

        //入力する値を準備
        breakIn.value = '12:00';
        breakOut.value = '13:00';

        //brealOutに値が入ったよ！と伝える
        breakOut.dispatchEvent(new Event('input', { bubbles: true }));

        //js-break-rowを全部取り出す
        const rows = document.querySelectorAll('.js-break-row');

        //２件に増えてるか確認
        expect(rows).toHaveLength(2);

    });
})