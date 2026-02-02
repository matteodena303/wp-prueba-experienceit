<?php
/**
 * Plugin Name: User Listing Test
 * Description: Paginated and searchable user list with AJAX, simulating a POST API call.
 * Version: 1.2.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ULT_User_Listing_Test
 *
 * Registers the [ult_user_list] shortcode to display a list of users with search and pagination via AJAX.
 * It simulates the response of an external API and applies filters on the server side
 * before returning the HTML table with pagination data.
 */
class ULT_User_Listing_Test
{
    /**
     * Number of items to display per page.
     */
    const PER_PAGE = 5;

    /**
     * Nonce action used to validate AJAX requests.
     */
    const NONCE_ACTION = 'ult_nonce_action';

    /**
     * Constructor. Registers hooks and shortcodes.
     */
    public function __construct()
    {
        add_shortcode('ult_user_list', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_ult_get_users', [$this, 'ajax_get_users']);
        add_action('wp_ajax_nopriv_ult_get_users', [$this, 'ajax_get_users']);
    }

    /**
     * Enqueues scripts and styles only when the shortcode is present.
     */
    public function enqueue_assets()
    {
        if (!$this->current_page_has_shortcode('ult_user_list')) {
            return;
        }

        // Enqueue the public-facing script.
        wp_enqueue_script(
            'ult-user-listing',
            plugin_dir_url(__FILE__) . 'assets/user-listing.js',
            ['jquery'],
            '1.1.0',
            true
        );

        // Pass PHP variables to the JS script.
        wp_localize_script('ult-user-listing', 'ULT_AJAX', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce(self::NONCE_ACTION),
            'per_page' => self::PER_PAGE,
        ]);

        // Inline CSS for the plugin frontend.
        $css = "
        .ult-wrap{max-width:900px;margin:20px 0;}
        .ult-form{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;}
        .ult-form input{padding:8px;min-width:220px;}
        .ult-form button{padding:8px 14px;cursor:pointer;}
        .ult-table{width:100%;border-collapse:collapse;}
        .ult-table th,.ult-table td{border:1px solid #ddd;padding:8px;text-align:left;}
        .ult-pagination{display:flex;gap:8px;align-items:center;margin-top:12px;flex-wrap:wrap;}
        .ult-pagination button{padding:6px 10px;cursor:pointer;}
        .ult-muted{opacity:.7;}
        ";

        wp_register_style('ult-user-listing-style', false);
        wp_enqueue_style('ult-user-listing-style');
        wp_add_inline_style('ult-user-listing-style', $css);
    }

    /**
     * Checks whether the current post content contains the specified shortcode.
     *
     * @param string $shortcode
     * @return bool
     */
    private function current_page_has_shortcode($shortcode)
    {
        global $post;
        if (!$post) {
            return false;
        }
        return has_shortcode($post->post_content, $shortcode);
    }

    /**
     * Renders the HTML containing the list and search form.
     *
     * @return string
     */
    public function render_shortcode()
    {
        ob_start();
        ?>
        <div class="ult-wrap" id="ult-app">
            <form class="ult-form" id="ult-search-form">
                <input type="text" name="name" placeholder="Nombre" />
                <input type="text" name="surnames" placeholder="Apellidos" />
                <input type="text" name="email" placeholder="Correo electrónico" />
                <button type="submit">Buscar</button>
            </form>
            <div id="ult-results">
                <p class="ult-muted">Cargando...</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handles the AJAX request and returns the HTML and pagination data.
     */
    public function ajax_get_users()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $filters = [
            'name'     => isset($_POST['name'])     ? sanitize_text_field($_POST['name'])     : '',
            'surnames' => isset($_POST['surnames']) ? sanitize_text_field($_POST['surnames']) : '',
            'email'    => isset($_POST['email'])    ? sanitize_email($_POST['email'])        : '',
        ];

        // Simula llamada a API externa y filtra los resultados.
        $api_response    = $this->simulate_api_post($filters);
        $users           = $api_response['usuarios'];
        $users_filtered  = $this->apply_filters($users, $filters);

        $per_page    = self::PER_PAGE;
        $total       = count($users_filtered);
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page        = min($page, $total_pages);

        $offset     = ($page - 1) * $per_page;
        $users_page = array_slice($users_filtered, $offset, $per_page);

        $html = $this->render_table_html($users_page, $total, $page, $total_pages);
        wp_send_json_success([
            'html'        => $html,
            'page'        => $page,
            'total_pages' => $total_pages,
            'total'       => $total,
        ]);
    }

    /**
     * Simulates the response of a POST API. Returns 50 sample users.
     *
     * @param array $filters
     * @return array
     */
    private function simulate_api_post($filters)
    {
        $usuarios = [];
        for ($i = 1; $i <= 50; $i++) {
            $usuarios[] = [
                'id'       => $i,
                'email'    => "admin{$i}@yopmail.com",
                'name'     => "Nombre{$i}",
                'surname1' => "Apellido{$i}A",
                'surname2' => "Apellido{$i}B",
                'username' => "user{$i}",
            ];
        }
        return ['usuarios' => $usuarios];
    }

    /**
     * Applies filters (name, surnames, and email) to the user list.
     *
     * @param array $users
     * @param array $filters
     * @return array
     */
    private function apply_filters($users, $filters)
    {
        $name     = mb_strtolower(trim($filters['name']));
        $surnames = mb_strtolower(trim($filters['surnames']));
        $email    = mb_strtolower(trim($filters['email']));
        return array_values(array_filter($users, function ($u) use ($name, $surnames, $email) {
            $haystack_name     = mb_strtolower($u['name']);
            $haystack_surnames = mb_strtolower(trim($u['surname1'] . ' ' . $u['surname2']));
            $haystack_email    = mb_strtolower($u['email']);
            if ($name     !== '' && mb_strpos($haystack_name,     $name)     === false) return false;
            if ($surnames !== '' && mb_strpos($haystack_surnames, $surnames) === false) return false;
            if ($email    !== '' && mb_strpos($haystack_email,    $email)    === false) return false;
            return true;
        }));
    }

    /**
     * Generates the HTML for the table and pagination.
     *
     * @param array $users
     * @param int $total
     * @param int $page
     * @param int $total_pages
     * @return string
     */
    private function render_table_html($users, $total, $page, $total_pages)
    {
        ob_start();
        ?>
        <div class="ult-meta ult-muted">
            Total resultados: <strong><?php echo esc_html($total); ?></strong>
        </div>
        <table class="ult-table" aria-label="Listado de usuarios">
            <thead>
            <tr>
                <th>Nombre de usuario</th>
                <th>Nombre</th>
                <th>Apellido 1</th>
                <th>Apellido 2</th>
                <th>Email</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($users)) : ?>
                <tr><td colspan="5" class="ult-muted">Sin resultados</td></tr>
            <?php else : ?>
                <?php foreach ($users as $u) : ?>
                    <tr>
                        <td><?php echo esc_html($u['username']); ?></td>
                        <td><?php echo esc_html($u['name']); ?></td>
                        <td><?php echo esc_html($u['surname1']); ?></td>
                        <td><?php echo esc_html($u['surname2']); ?></td>
                        <td><?php echo esc_html($u['email']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <div class="ult-pagination" role="navigation" aria-label="Paginación">
            <button type="button" class="ult-page-btn" data-page="1" <?php disabled($page === 1); ?>>« Primera</button>
            <button type="button" class="ult-page-btn" data-page="<?php echo esc_attr(max(1, $page - 1)); ?>" <?php disabled($page === 1); ?>>‹ Anterior</button>
            <span class="ult-muted">Página <?php echo esc_html($page); ?> de <?php echo esc_html($total_pages); ?></span>
            <button type="button" class="ult-page-btn" data-page="<?php echo esc_attr(min($total_pages, $page + 1)); ?>" <?php disabled($page === $total_pages); ?>>Siguiente ›</button>
            <button type="button" class="ult-page-btn" data-page="<?php echo esc_attr($total_pages); ?>" <?php disabled($page === $total_pages); ?>>Última »</button>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Inicializa el plugin.
new ULT_User_Listing_Test();
?>
