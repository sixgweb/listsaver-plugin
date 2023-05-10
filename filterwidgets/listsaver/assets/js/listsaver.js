(function () {

    let handlers = [
        'onApplyListSaverPreference',
        'onSaveListSaverPreference',
        'onDeleteListSaverPreference',
    ];

    let getListSaverElement = () => {
        return document.querySelector('[data-scope-name="listsaver"]');
    };

    /**
     * No firefox support for :has() css selector, so forced to add classes
     */
    let addListSaverClasses = (el) => {
        el.parentElement.classList.add('filter-group-has-list-saver');
        el.parentElement.parentElement.classList.add('filter-has-list-saver');
    };

    addEventListener('ajax:update-complete', (e) => {

        let el = getListSaverElement();

        if (!el) {
            return;
        }

        let handler = e.detail.context.handler.split('::')[1] ?? null;
        let prefId = e.detail.context.options.data['list_saver_preference'] ?? null;
        let currPrefId = el.dataset.scopeId ?? null;

        addListSaverClasses(el);

        if (!handler || handlers.includes(handler) === false) {
            return;
        }

        if (prefId && prefId == currPrefId) {
            document.dispatchEvent(new Event('mousedown'));
        }

        if (handler === 'onApplyListSaverPreference') {
            document.dispatchEvent(new Event('mousedown'));
            addListSaverClasses(el);
        }

        if (handler === 'onSaveListSaverPreference') {
            document.dispatchEvent(new Event('mousedown'));
        }

        if (handler == 'onDeleteListSaverPreference' && !currPrefId) {
            document.dispatchEvent(new Event('mousedown'));
        }

    });

    /**
     * Add classes on page loaded
     */
    addEventListener('page:loaded', (e) => {
        let el = getListSaverElement();
        if (el) {
            addListSaverClasses(el);
        }
    });
})();