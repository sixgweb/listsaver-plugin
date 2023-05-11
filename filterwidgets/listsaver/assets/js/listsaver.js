(function () {

    let getListSaverElement = () => {
        return document.querySelector('[data-scope-name="listsaver"]');
    };

    /**
     * No firefox support for :has() css selector, so forced to add classes
     */
    let addListSaverClasses = () => {
        let el = getListSaverElement();
        if (el) {
            el.parentElement.classList.add('filter-group-has-list-saver');
            el.parentElement.parentElement.classList.add('filter-has-list-saver');
        }
    };

    addEventListener('ajax:update', (e) => {
        let el = getListSaverElement();
        if (el) {
            if (el.parentElement == e.target) {
                document.dispatchEvent(new Event('mousedown'));
            }
        }
    });

    addEventListener('ajax:update-complete', (e) => {
        let el = getListSaverElement();
        if (el) {
            addListSaverClasses(el);

            //When deleting, fire the click event to reload the popover content.
            //Tried to make this happen in october.filter.js but parameters weren't working
            if (e.detail.context.handler.split('::')[1] == 'onDeleteListSaverPreference') {
                el.click();
            }
        }
    });

    /**
     * Add classes on page loaded
     */
    addEventListener('page:loaded', (e) => {
        addListSaverClasses();
    });
})();