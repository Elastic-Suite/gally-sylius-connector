import { Controller } from '@hotwired/stimulus';

/**
 * Connected on the facet list container: Stimulus keeps wiring "click" on any ".view-more"
 * link matching data-action, including ones inserted later by the AJAX swap below.
 */
export default class extends Controller {
    load(event) {
        event.preventDefault();

        const viewMoreBtn = event.currentTarget;
        const form = viewMoreBtn.closest('form');
        const dataFor = viewMoreBtn.dataset.for;
        const dataHref = viewMoreBtn.dataset.href;
        const choicesEl = document.querySelector(`#${dataFor}`);

        form.classList.add('loading');

        fetch(dataHref)
            .then((response) => response.json())
            .then((data) => {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;
                const newFields = tempDiv.querySelector(`#${dataFor}`);

                if (newFields && choicesEl) {
                    choicesEl.replaceWith(newFields);
                }

                viewMoreBtn.style.display = 'none';
            })
            .catch((error) => {
                console.error('Fetch error:', error);
            })
            .finally(() => {
                form.classList.remove('loading');
            });
    }
}
