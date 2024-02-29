$(document).ready(function() {
  $(document).on(
    'click',
    '#searchbarTextField .view-more',
    function (event) {
      event. preventDefault();

      let linkEl = $(event.target),
          form = linkEl.closest('form'),
          choicesEl = $('#' + linkEl.data('for') + ' > .fields');

      form.addClass('loading');

      $.get(
        linkEl.data('href'),
        function( data ) {
          choicesEl.replaceWith($(data.html).find('.fields'));
          linkEl.hide();
        }
      ).always(function() {
        form.removeClass('loading');
      });
    }
  );
});
