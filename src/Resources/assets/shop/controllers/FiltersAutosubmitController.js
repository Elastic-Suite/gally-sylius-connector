import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

// Submits the filters form as soon as a facet field changes, instead of waiting for a submit click.
// Also swaps the LiveComponent default full-page error iframe (facet "view more"/search failing,
// e.g. a source field removed from Gally) for a dismissible alert matching Sylius' native flash
// messages, since #searchbar is a common ancestor of every facet_options live component.
export default class extends Controller {
    static values = {
        loadMoreErrorMessage: String,
    };

    connect() {
        this.element.querySelectorAll('[data-controller~="live"]').forEach((element) => {
            getComponent(element).then((component) => {
                component.on('response:error', (response, controls) => {
                    controls.displayError = false;
                    this.showLoadMoreError();
                });
            });
        });
    }

    showLoadMoreError() {
        const container = document.querySelector('.sylius-messages');
        if (!container) {
            return;
        }

        if (this.errorAlert) {
            this.errorAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = 'alert alert-danger my-2';
        alert.setAttribute('role', 'alert');

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex justify-content-between';

        const text = document.createElement('div');
        text.textContent = this.loadMoreErrorMessageValue;

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.setAttribute('data-bs-dismiss', 'alert');
        closeButton.setAttribute('aria-label', 'Close');

        wrapper.append(text, closeButton);
        alert.append(wrapper);
        container.prepend(alert);
        this.errorAlert = alert;
    }

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
