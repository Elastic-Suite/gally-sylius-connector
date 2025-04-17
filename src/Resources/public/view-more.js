document.addEventListener('DOMContentLoaded', function () {
  document.addEventListener('click', function (event) {
    const viewMoreBtn = event.target.closest('#searchbarTextField .view-more');
    if (!viewMoreBtn) return;

    event.preventDefault();

    const form = viewMoreBtn.closest('form');
    const dataFor = viewMoreBtn.dataset.for;
    const dataHref = viewMoreBtn.dataset.href;
    const choicesEl = document.querySelector(`#${dataFor}`);

    form.classList.add('loading');

    fetch(dataHref)
      .then(response => response.json())
      .then(data => {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = data.html;
        const newFields = tempDiv.querySelector(`#${dataFor}`);

        if (newFields && choicesEl) {
          choicesEl.replaceWith(newFields);
        }

        viewMoreBtn.style.display = 'none';
      })
      .catch(error => {
        console.error('Fetch error:', error);
      })
      .finally(() => {
        form.classList.remove('loading');
      });
  });
});
