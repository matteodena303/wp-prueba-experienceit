(function ($) {

    let debounceTimer;

    function loadUsers(page = 1) {
        const data = {
            action: 'ult_get_users',
            nonce: ULT_AJAX.nonce,
            name: $('input[name="name"]').val() || '',
            surnames: $('input[name="surnames"]').val() || '',
            email: $('input[name="email"]').val() || '',
            page: page
        };

        $('#ult-results').html('<p class="ult-muted">Cargando...</p>');

        $.post(ULT_AJAX.ajax_url, data)
            .done(function (res) {
                if (res.success && res.data && res.data.html) {
                    $('#ult-results').html(res.data.html);
                } else {
                    $('#ult-results').html('<p class="ult-muted">Error inesperado.</p>');
                }
            })
            .fail(function () {
                $('#ult-results').html('<p class="ult-muted">La petición AJAX ha fallado.</p>');
            });
    }

    $(document).ready(function () {

        // carga inicial
        loadUsers();

        // nasconde il bottone Buscar (non serve più)
        $('#ult-search-form button').hide();

        // ricerca automatica mentre si scrive (debounce 300ms)
        $('#ult-search-form input').on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                loadUsers(1);
            }, 300);
        });

        // invio form con Enter
        $('#ult-search-form').on('submit', function (e) {
            e.preventDefault();
            loadUsers(1);
        });

        // paginazione AJAX
        $('#ult-results').on('click', '.ult-page-btn', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                loadUsers(page);
            }
        });
    });

})(jQuery);
