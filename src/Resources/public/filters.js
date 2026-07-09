document.addEventListener('DOMContentLoaded', function () {
  initAutosubmit();
  initViewMore();
  initFacetSearch();
});

// submits the filters form as soon as a facet field changes, instead of waiting for a submit click
function initAutosubmit() {
  const searchbar = document.getElementById('searchbar');
  const form = searchbar && searchbar.closest('form');
  if (!form) return;

  searchbar.addEventListener('change', function () {
    // don't submit facets that haven't actually been set (e.g. an untouched price range,
    // or a boolean select left on its "All" placeholder) - disabled fields are left out
    // of the submission entirely, and the page is about to unload anyway
    searchbar.querySelectorAll('input, select').forEach(function (field) {
      if (field.type !== 'checkbox' && field.type !== 'radio' && field.value === '') {
        field.disabled = true;
      }
    });
    form.requestSubmit();
  });
}

// loads the next batch of a facet's options when its "view more" link is clicked
function initViewMore() {
  document.addEventListener('click', function (event) {
    const viewMoreBtn = event.target.closest('#searchbarTextField .view-more');
    if (!viewMoreBtn) return;

    event.preventDefault();
    replaceChoices(viewMoreBtn.dataset.for, viewMoreBtn.dataset.href).then(function () {
      viewMoreBtn.style.display = 'none';
    });
  });
}

// lets the user type in a facet to search its options server-side (debounced), with a clear
// button that instantly restores the facet's original options
function initFacetSearch() {
  document.querySelectorAll('#searchbarTextField .facet-search').forEach(function (searchInput) {
    const dataFor = searchInput.dataset.for;
    const clearButton = searchInput.closest('.facet-search-wrapper').querySelector('.facet-search-clear');
    const viewMoreLink = searchInput.closest('.field').querySelector('.view-more');
    const initialChoicesEl = document.querySelector(`#${dataFor}`);
    const initialChoicesHtml = initialChoicesEl ? initialChoicesEl.outerHTML : null;
    let debounceTimer = null;

    // the autosubmit "change" listener on #searchbar would otherwise submit the form whenever
    // this input loses focus after being typed into; it isn't a real filter field.
    searchInput.addEventListener('change', function (event) {
      event.stopPropagation();
    });

    searchInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);

      const term = searchInput.value.trim();
      if (viewMoreLink) {
        viewMoreLink.style.display = term === '' ? '' : 'none';
      }
      if (clearButton) {
        clearButton.style.display = term === '' ? 'none' : '';
      }

      if (term === '') {
        restoreChoices(dataFor, initialChoicesHtml);
        return;
      }

      debounceTimer = setTimeout(function () {
        const url = new URL(searchInput.dataset.href, window.location.origin);
        url.searchParams.set('optionSearch', term);
        replaceChoices(dataFor, url, function () {
          // a newer keystroke may have started another request while this one was in flight
          return searchInput.value.trim() === term;
        });
      }, 300);
    });

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        searchInput.focus();
      });
    }
  });
}

// swaps a facet's options back to a previously captured (pristine) state, with no network call
function restoreChoices(dataFor, html) {
  const currentChoicesEl = document.querySelector(`#${dataFor}`);
  if (!html || !currentChoicesEl) return;

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = html;
  currentChoicesEl.replaceWith(tempDiv.firstElementChild);
}

// fetches a facet's options from the given URL and swaps them into the page
function replaceChoices(dataFor, url, isStillRelevant) {
  const choicesEl = document.querySelector(`#${dataFor}`);
  if (!choicesEl || !url) return Promise.resolve();

  const form = choicesEl.closest('form');
  form.classList.add('loading');

  return fetch(url)
    .then(response => response.json())
    .then(function (data) {
      if (isStillRelevant && !isStillRelevant()) return;

      const tempDiv = document.createElement('div');
      tempDiv.innerHTML = data.html;
      const newChoices = tempDiv.querySelector(`#${dataFor}`);
      const currentChoicesEl = document.querySelector(`#${dataFor}`);

      if (newChoices && currentChoicesEl) {
        currentChoicesEl.replaceWith(newChoices);
      }
    })
    .catch(function (error) {
      console.error('Fetch error:', error);
    })
    .finally(function () {
      form.classList.remove('loading');
    });
}
