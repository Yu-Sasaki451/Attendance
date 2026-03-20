function initializeTabSwitch(documentObject) {
    const roots = documentObject.querySelectorAll('[data-tab-root]');

    roots.forEach(function (root) {
        const tabs = Array.from(root.querySelectorAll('[role="tab"]'));
        if (tabs.length === 0) {
            return;
        }

        const activateTab = function (activeTab) {
            tabs.forEach(function (tab) {
                const selected = tab === activeTab;
                tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                tab.setAttribute('tabindex', selected ? '0' : '-1');

                const panelId = tab.getAttribute('aria-controls');
                if (!panelId) {
                    return;
                }

                const panel = documentObject.getElementById(panelId);
                if (!panel) {
                    return;
                }

                panel.hidden = !selected;
            });
        };

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                activateTab(tab);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initializeTabSwitch(document);
});

if (typeof module !== 'undefined') {
    module.exports = {
        initializeTabSwitch: initializeTabSwitch,
    };
}
