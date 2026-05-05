const gallySearchFormHandler = function () {
  const gallySearchFormContainers = document.querySelectorAll('.searchFormContainer');

  gallySearchFormContainers.forEach(container => {
    const gallyPreviewUrl = container.dataset.previewUrl;
    const gallySearchForm = container.querySelector('form');
    const gallySearchInput = gallySearchForm.querySelector('input');
    const gallySearchResult = container.querySelector('.collapsedSearchResults');

    let abortController = null;
    let debounceTimer = null;
    const queryCache = new Map();
    let lastNonEmptyContent = null;

    const displayResults = (content) => {
      gallySearchResult.querySelector('.loading-results').classList.add('d-none');
      gallySearchResult.querySelector('.results').classList.remove('d-none');

      // If response is empty but we have a previous non-empty result, keep showing it
      const displayContent = content.htmlResults ? content : lastNonEmptyContent;

      if (!displayContent || !displayContent.htmlResults) {
        gallySearchResult.classList.remove('show');
        return;
      }

      gallySearchResult.classList.add('show');
      gallySearchResult.querySelector('.results').innerHTML = displayContent.htmlResults;

      if (gallySearchResult.querySelector('.results .products')) {
        gallySearchResult.parentElement.classList.add('start-0');
        gallySearchResult.parentElement.style.width = '100%';
      } else {
        gallySearchResult.parentElement.classList.remove('start-0');
        gallySearchResult.parentElement.style.width = 'auto';
      }
    };

    const performSearch = ({ showWhileLoading = true } = {}) => {
      const formData = new FormData(gallySearchForm);
      const plainFormData = Object.fromEntries(formData.entries());
      const formDataString = new URLSearchParams(plainFormData).toString();

      // Serve from cache if available
      if (queryCache.has(formDataString)) {
        const cached = queryCache.get(formDataString);
        gallySearchResult.classList.add('show');
        displayResults(cached);
        if (cached.htmlResults) {
          lastNonEmptyContent = cached;
        }
        return;
      }

      // While loading, show panel only if requested (not on focus)
      if (showWhileLoading) {
        if (lastNonEmptyContent) {
          // Keep showing previous results (no spinner)
          gallySearchResult.querySelector('.loading-results').classList.add('d-none');
          gallySearchResult.querySelector('.results').classList.remove('d-none');
        } else {
          // First search: show spinner
          gallySearchResult.querySelector('.loading-results').classList.remove('d-none');
          gallySearchResult.querySelector('.results').classList.add('d-none');
        }
        gallySearchResult.classList.add('show');
      }

      if (abortController) {
        abortController.abort();
      }

      abortController = new AbortController();

      (async () => {
        try {
          const rawResponse = await fetch(gallyPreviewUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formDataString,
            signal: abortController.signal
          });

          const content = await rawResponse.json();

          // Track last non-empty result
          if (content.htmlResults) {
            lastNonEmptyContent = content;
          }

          // Cache the result: if empty, store last non-empty result instead
          const cachedContent = content.htmlResults ? content : lastNonEmptyContent;

          if (cachedContent) {
            queryCache.set(formDataString, cachedContent);
            displayResults(cachedContent);
          } else {
            // No result ever received yet, just hide the panel
            gallySearchResult.querySelector('.loading-results').classList.add('d-none');
            gallySearchResult.classList.remove('show');
          }

        } catch (error) {
          if (error.name !== 'AbortError') {
            console.error(error);
          }
        }
      })();
    };

    gallySearchInput.addEventListener('input', (event) => {
      console.log('Input events__6:');
      const queryText = event.target.value;

      // Always cancel the previous debounce timer
      if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
      }

      if (queryText.length >= 3) {
        // Keep previous results visible while waiting for debounce
        gallySearchResult.classList.add('show');

        debounceTimer = setTimeout(() => {
          performSearch();
        }, 200);
      } else {
        // Also cancel any ongoing request
        if (abortController) {
          abortController.abort();
          abortController = null;
        }
        // New search session: reset last results so stale content won't reappear
        lastNonEmptyContent = null;
        gallySearchResult.querySelector('.results').textContent = '';
        gallySearchResult.classList.remove('show');
      }
    });

    gallySearchInput.addEventListener('focus', (event) => {
      const queryText = event.target.value;
      if (queryText.length >= 3) {
        if (gallySearchResult.querySelector('.results').innerHTML.trim() !== '') {
          gallySearchResult.classList.add('show');
        } else {
          // Search silently: panel will only appear when results arrive via displayResults
          performSearch({ showWhileLoading: false });
        }
      }
    });
  });

  // Close when clicking outside the search results or the search input
  document.addEventListener('mousedown', function (event) {
    document.querySelectorAll('.collapsedSearchResults.show').forEach(result => {
      const container = result.closest('.searchFormContainer');
      const input = container ? container.querySelector('form input') : null;
      if (!result.contains(event.target) && !(input && input.contains(event.target))) {
        result.classList.remove('show');
      }
    });
  });
};

window.addEventListener("DOMContentLoaded", gallySearchFormHandler);
