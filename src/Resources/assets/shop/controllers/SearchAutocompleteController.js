import { Controller } from '@hotwired/stimulus';

/**
 * Debounced, cached, abortable autocomplete search preview. One instance is connected per
 * ".searchFormContainer" (desktop and mobile header variants each get their own).
 */
export default class extends Controller {
    static targets = ['input', 'results', 'loading', 'resultsPanel'];
    static values = { previewUrl: String };

    connect() {
        this.abortController = null;
        this.debounceTimer = null;
        this.queryCache = new Map();
        this.lastNonEmptyContent = null;
    }

    disconnect() {
        if (this.abortController) {
            this.abortController.abort();
        }
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
    }

    onInput(event) {
        const queryText = event.target.value;

        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = null;
        }

        if (queryText.length >= 3) {
            // Keep previous results visible while waiting for debounce
            this.resultsPanelTarget.classList.add('show');
            this.debounceTimer = setTimeout(() => this.performSearch(), 200);

            return;
        }

        // Also cancel any ongoing request
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
        // New search session: reset last results so stale content won't reappear
        this.lastNonEmptyContent = null;
        this.resultsTarget.textContent = '';
        this.resultsPanelTarget.classList.remove('show');
    }

    onFocus(event) {
        const queryText = event.target.value;
        if (queryText.length < 3) {
            return;
        }

        if (this.resultsTarget.innerHTML.trim() !== '') {
            this.resultsPanelTarget.classList.add('show');
        } else {
            // Search silently: panel will only appear when results arrive via displayResults
            this.performSearch({ showWhileLoading: false });
        }
    }

    outsideClick(event) {
        if (
            this.resultsPanelTarget.classList.contains('show')
            && !this.resultsPanelTarget.contains(event.target)
            && !this.inputTarget.contains(event.target)
        ) {
            this.resultsPanelTarget.classList.remove('show');
        }
    }

    displayResults(content) {
        this.loadingTarget.classList.add('d-none');
        this.resultsTarget.classList.remove('d-none');

        // If response is empty but we have a previous non-empty result, keep showing it
        const displayContent = content.htmlResults ? content : this.lastNonEmptyContent;

        if (!displayContent || !displayContent.htmlResults) {
            this.resultsPanelTarget.classList.remove('show');

            return;
        }

        this.resultsPanelTarget.classList.add('show');
        this.resultsTarget.innerHTML = displayContent.htmlResults;

        if (this.resultsTarget.querySelector('.products')) {
            this.resultsPanelTarget.parentElement.classList.add('start-0');
            this.resultsPanelTarget.parentElement.style.width = '100%';
        } else {
            this.resultsPanelTarget.parentElement.classList.remove('start-0');
            this.resultsPanelTarget.parentElement.style.width = 'auto';
        }
    }

    performSearch({ showWhileLoading = true } = {}) {
        const form = this.element.querySelector('form');
        const formData = new FormData(form);
        const plainFormData = Object.fromEntries(formData.entries());
        const formDataString = new URLSearchParams(plainFormData).toString();

        // Serve from cache if available
        if (this.queryCache.has(formDataString)) {
            const cached = this.queryCache.get(formDataString);
            this.resultsPanelTarget.classList.add('show');
            this.displayResults(cached);
            if (cached.htmlResults) {
                this.lastNonEmptyContent = cached;
            }

            return;
        }

        // While loading, show panel only if requested (not on focus)
        if (showWhileLoading) {
            if (this.lastNonEmptyContent) {
                // Keep showing previous results (no spinner)
                this.loadingTarget.classList.add('d-none');
                this.resultsTarget.classList.remove('d-none');
            } else {
                // First search: show spinner
                this.loadingTarget.classList.remove('d-none');
                this.resultsTarget.classList.add('d-none');
            }
            this.resultsPanelTarget.classList.add('show');
        }

        if (this.abortController) {
            this.abortController.abort();
        }
        this.abortController = new AbortController();

        fetch(this.previewUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formDataString,
            signal: this.abortController.signal,
        })
            .then((response) => response.json())
            .then((content) => {
                // Track last non-empty result
                if (content.htmlResults) {
                    this.lastNonEmptyContent = content;
                }

                // Cache the result: if empty, store last non-empty result instead
                const cachedContent = content.htmlResults ? content : this.lastNonEmptyContent;

                if (cachedContent) {
                    this.queryCache.set(formDataString, cachedContent);
                    this.displayResults(cachedContent);
                } else {
                    // No result ever received yet, just hide the panel
                    this.loadingTarget.classList.add('d-none');
                    this.resultsPanelTarget.classList.remove('show');
                }
            })
            .catch((error) => {
                if (error.name !== 'AbortError') {
                    console.error(error);
                }
            });
    }
}
