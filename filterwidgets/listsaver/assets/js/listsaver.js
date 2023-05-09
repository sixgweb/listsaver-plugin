(function () {

    let handlers = [
        'onApplyListSaverPreference',
        'onSaveListSaverPreference',
        'onDeleteListSaverPreference',
    ];

    addEventListener('ajax:update-complete', (e) => {
        let handler = e.detail.context.handler.split('::')[1] ?? null;
        let prefId = e.detail.context.options.data['list_saver_preference'] ?? null;
        let currPrefId = document.querySelector('[data-scope-name="listsaver"]').dataset.scopeId ?? null;

        if (!handler || handlers.includes(handler) === false) {
            return;
        }

        if (prefId && prefId == currPrefId) {
            document.dispatchEvent(new Event('mousedown'));
        }

        if (handler === 'onApplyListSaverPreference') {
            document.dispatchEvent(new Event('mousedown'));
        }

        if (handler === 'onSaveListSaverPreference') {
            document.dispatchEvent(new Event('mousedown'));
        }

        if (handler == 'onDeleteListSaverPreference' && !currPrefId) {
            document.dispatchEvent(new Event('mousedown'));
        }

    });

    /**
     * No firefox support for :has() css selector, so forced to add classes
     */
    addEventListener('page:loaded', (e) => {
        let listSaver = document.querySelector('[data-scope-name="listsaver"]');
        listSaver.parentElement.classList.add('filter-group-has-list-saver');
        listSaver.parentElement.parentElement.classList.add('filter-has-list-saver');
    });
})();