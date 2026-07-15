import { Controller } from '@hotwired/stimulus';

const SEARCH_DEBOUNCE_DELAY = 300;

/**
 * Connected on the facet list container (#searchbarTextField): handles both "view more" pagination
 * and in-facet option search for every checkbox facet nested inside. Stimulus re-scans this
 * element's descendants for data-action bindings after each AJAX swap below, so newly injected
 * "view more" links / search inputs are wired up automatically.
 */
export default class extends Controller {
    connect() {
        this.initialChoicesHtmlByDataFor = new Map();
        this.searchDebounceTimers = new Map();
    }

    viewMore(event) {
        event.preventDefault();

        const viewMoreBtn = event.currentTarget;
        this.replaceChoices(viewMoreBtn.dataset.for, viewMoreBtn.dataset.href).then(() => {
            viewMoreBtn.style.display = 'none';
        });
    }

    search(event) {
        const searchInput = event.currentTarget;
        const dataFor = searchInput.dataset.for;
        const wrapper = searchInput.closest('.facet-search-wrapper');
        const clearButton = wrapper ? wrapper.querySelector('.facet-search-clear') : null;
        const viewMoreLink = searchInput.closest('.field').querySelector('.view-more');

        clearTimeout(this.searchDebounceTimers.get(dataFor));

        const term = searchInput.value.trim();
        if (viewMoreLink) {
            viewMoreLink.style.display = term === '' ? '' : 'none';
        }
        if (clearButton) {
            clearButton.style.display = term === '' ? 'none' : '';
        }

        if (term === '') {
            this.restoreChoices(dataFor);
            return;
        }

        if (!this.initialChoicesHtmlByDataFor.has(dataFor)) {
            const currentChoicesEl = document.getElementById(dataFor);
            if (currentChoicesEl) {
                this.initialChoicesHtmlByDataFor.set(dataFor, currentChoicesEl.outerHTML);
            }
        }

        this.searchDebounceTimers.set(
            dataFor,
            setTimeout(() => {
                const url = new URL(searchInput.dataset.href, window.location.origin);
                url.searchParams.set('optionSearch', term);
                // a newer keystroke may have started another request while this one was in flight
                this.replaceChoices(dataFor, url, () => searchInput.value.trim() === term);
            }, SEARCH_DEBOUNCE_DELAY),
        );
    }

    clearSearch(event) {
        const wrapper = event.currentTarget.closest('.facet-search-wrapper');
        const searchInput = wrapper.querySelector('.facet-search');
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        searchInput.focus();
    }

    // the filters-autosubmit controller listens for "change" bubbling up from #searchbar; the
    // facet-search input isn't a real filter field, so its own "change" (on blur) must not submit.
    stopPropagation(event) {
        event.stopPropagation();
    }

    restoreChoices(dataFor) {
        const html = this.initialChoicesHtmlByDataFor.get(dataFor);
        const currentChoicesEl = document.getElementById(dataFor);
        if (!html || !currentChoicesEl) {
            return;
        }

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        currentChoicesEl.replaceWith(tempDiv.firstElementChild);
    }

    replaceChoices(dataFor, url, isStillRelevant) {
        const choicesEl = document.getElementById(dataFor);
        if (!choicesEl || !url) {
            return Promise.resolve();
        }

        const field = choicesEl.closest('.field');
        field.classList.add('is-loading');

        return fetch(url)
            .then((response) => response.json())
            .then((data) => {
                if (isStillRelevant && !isStillRelevant()) {
                    return;
                }

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;
                const newChoices = tempDiv.querySelector(`#${dataFor}`);
                const currentChoicesEl = document.getElementById(dataFor);

                if (newChoices && currentChoicesEl) {
                    currentChoicesEl.replaceWith(newChoices);
                }
            })
            .catch((error) => {
                console.error('Fetch error:', error);
            })
            .finally(() => {
                field.classList.remove('is-loading');
            });
    }
}
