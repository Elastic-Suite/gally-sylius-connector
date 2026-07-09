document.addEventListener('DOMContentLoaded', () => {
    var searchbar = document.getElementById('searchbar');
    if (!searchbar) {
        return;
    }
    var form = searchbar.closest('form');
    if (!form) {
        return;
    }

    searchbar.addEventListener('change', function () {
        // don't submit facets that haven't actually been set (e.g. an untouched price range,
        // or a boolean select left on its "All" placeholder) - disabled fields are left out
        // of the submission entirely, and the page is about to unload anyway
        searchbar.querySelectorAll('input, select').forEach(function (field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                return;
            }
            if ('' === field.value) {
                field.disabled = true;
            }
        });

        form.requestSubmit();
    });
});
