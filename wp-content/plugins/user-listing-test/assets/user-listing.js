/*
 * This script handles dynamic user loading via AJAX.
 * It supports live search with debounce and AJAX pagination.
 * The search button is hidden because filtering happens automatically.
 */

(function ($) {

    // Timer used to debounce live search input
    let debounceTimer;

    /**
     * Load users from the backend using AJAX
     * @param {number} page Page number to load (default: 1)
     */
    function loadUsers(page = 1) {

        const data = {
            action: 'ult_get_users',
            nonce: ULT_AJAX.nonce,
            name: $('input[name="name"]').val() || '',
            surnames: $('input[name="surnames"]').val() || '',
            email: $('input[name="email"]').val() || '',
            page: page
        };

        // Show loading message
        $('#ult-results').html('<p class="ult-muted">Loading...</p>');

        $.post(ULT_AJAX.ajax_url, data)
            .done(function (response) {
                if (response.success && response.data && response.data.html) {
                    $('#ult-results').html(response.data.html);
                } else {
                    $('#ult-results').html('<p class="ult-muted">Unexpected error.</p>');
                }
            })
            .fail(function () {
                $('#ult-results').html('<p class="ult-muted">AJAX request failed.</p>');
            });
    }

    $(document).ready(function () {

        // Initial load
        loadUsers();

        // Hide search button (not needed anymore)
        $('#ult-search-form button').hide();

        // Live search with debounce
        $('#ult-search-form input').on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                loadUsers(1);
            }, 300);
        });

        // Allow submit via Enter key
        $('#ult-search-form').on('submit', function (e) {
            e.preventDefault();
            loadUsers(1);
        });

        // AJAX pagination (event delegation)
        $('#ult-results').on('click', '.ult-page-btn', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            if (page) {
                loadUsers(page);
            }
        });

    });

})(jQuery);
