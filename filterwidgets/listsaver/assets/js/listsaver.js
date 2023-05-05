(function () {
    addEventListener('ajax:update-complete', (e) => {
        //Hide popover by triggering mousedown event on document
        if (e.detail.data.closePopover ?? false) {
            document.dispatchEvent(new Event('mousedown'));
        }
    });
})();