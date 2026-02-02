(function ($) {
  "use strict";

  function renderLoading() {
    $('#ult-results').html('<p class="ult-muted">Cargando...</p>');
  }

  function renderError(msg) {
    $('#ult-results').html(
      '<p class="ult-muted">' + (msg || 'Error cargando datos') + '</p>'
    );
  }

  function loadUsers(page) {
    var $form = $('#ult-search-form');
    var data = {
      action: 'ult_get_users',
      nonce: (window.ULT_AJAX && ULT_AJAX.nonce) ? ULT_AJAX.nonce : '',
      page: page || 1,
      name: $form.find('[name="name"]').val() || '',
      surnames: $form.find('[name="surnames"]').val() || '',
      email: $form.find('[name="email"]').val() || ''
    };

    renderLoading();

    $.post(ULT_AJAX.ajax_url, data)
      .done(function (res) {
        if (res && res.success && res.data && typeof res.data.html === 'string') {
          $('#ult-results').html(res.data.html);
        } else {
          renderError();
        }
      })
      .fail(function () {
        renderError();
      });
  }

  // Initial load
  $(function () {
    if ($('#ult-app').length) {
      loadUsers(1);
    }
  });

  // Search submit
  $(document).on('submit', '#ult-search-form', function (e) {
    e.preventDefault();
    loadUsers(1);
  });

  // Pagination buttons
  $(document).on('click', '.ult-page-btn', function () {
    var p = parseInt($(this).data('page'), 10);
    if (!isNaN(p)) {
      loadUsers(p);
    }
  });
})(jQuery);
