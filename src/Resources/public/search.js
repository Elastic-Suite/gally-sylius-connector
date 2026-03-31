const gallySearchFormHandler = function () {
  const gallySearchFormContainers = document.querySelectorAll('.searchFormContainer');

  gallySearchFormContainers.forEach(container => {
    const gallyPreviewUrl = container.dataset.previewUrl;
    const gallySearchForm = container.querySelector('form');
    const gallySearchInput = gallySearchForm.querySelector('input');
    const gallySearchResult = container.querySelector('.collapsedSearchResults');

    let abortController = null;

    gallySearchInput.addEventListener('input', (event) => {
      const queryText = event.target.value;

      if (queryText.length >= 3) {
        const formData = new FormData(gallySearchForm);
        const plainFormData = Object.fromEntries(formData.entries());
        const formDataString = new URLSearchParams(plainFormData).toString();

        gallySearchResult.querySelector('.loading-results').classList.remove('d-none');
        gallySearchResult.querySelector('.results').classList.add('d-none');
        gallySearchResult.querySelector('.results').textContent = '';
        gallySearchResult.classList.add('show');

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

            gallySearchResult.querySelector('.loading-results').classList.add('d-none');
            gallySearchResult.querySelector('.results').classList.remove('d-none');
            gallySearchResult.querySelector('.results').innerHTML = content.htmlResults;

            if (!content.htmlResults) {
              gallySearchResult.classList.remove('show');
            }

            if (gallySearchResult.querySelector('.results .products')) {
              gallySearchResult.parentElement.classList.add('start-0');
              gallySearchResult.parentElement.style.width = '100%';
            } else {
              gallySearchResult.parentElement.classList.remove('start-0');
              gallySearchResult.parentElement.style.width = 'auto';
            }

          } catch (error) {
            if (error.name !== 'AbortError') {
              console.error(error);
            }
          }
        })();
      } else {
        gallySearchResult.classList.remove('show');
      }
    });

    gallySearchInput.addEventListener('focus', (event) => {
      const queryText = event.target.value;
      if (queryText.length >= 3) {
        if (gallySearchResult.querySelector('.results').innerHTML.trim() !== '') {
          gallySearchResult.classList.add('show');
        } else {
          gallySearchInput.dispatchEvent(new Event('input'));
        }
      }
    });
  });

  // Close when clicking outside
  document.addEventListener('click', function (event) {
    if (!event.target.closest('.collapsedSearchResults') && !event.target.closest('.searchFormContainer')) {
      document.querySelectorAll('.collapsedSearchResults').forEach(element => element.classList.remove('show'));
    }
  });
};

window.addEventListener("DOMContentLoaded", gallySearchFormHandler);
