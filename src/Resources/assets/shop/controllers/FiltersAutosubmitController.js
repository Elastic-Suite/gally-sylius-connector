import { Controller } from '@hotwired/stimulus';

// Submits the filters form as soon as a facet field changes, instead of waiting for a submit click.
export default class extends Controller {
    submit(event) {
        // the facet-search text input isn't a real filter field: its own controller stops this
        // "change" event from bubbling up, but keep this guard in case markup changes.
        if (event.target.classList.contains('facet-search')) {
            return;
        }

        // don't submit facets that haven't actually been set (e.g. an untouched price range,
        // or a boolean select left on its "All" placeholder) - disabled fields are left out
        // of the submission entirely, and the page is about to unload anyway
        this.element.querySelectorAll('input, select').forEach((field) => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                return;
            }
            if (field.value === '') {
                field.disabled = true;
            }
        });

        this.element.closest('form').requestSubmit();
    }
}
