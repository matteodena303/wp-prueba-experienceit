<?php

/**
 * Plugin Name: User Listing Test
 * Description: Listado paginado y buscable de usuarios con AJAX simulando API POST.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

class ULT_User_Listing_Test
{
    const PER_PAGE = 5;
    const NONCE_ACTION = 'ult_nonce_action';

    public function __construct()
    {
        add_shortcode('ult_user_list', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('wp_ajax_ult_get_users', [$this, 'ajax_get_users']);
        add_action('wp_ajax_nopriv_ult_get_users', [$this, 'ajax_get_users']);

        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function enqueue_assets()
    {
        if (!$this->current_page_has_shortcode('ult_user_list')) return;

        wp_enqueue_script(
            'ult-user-listing',
            plugin_dir_url(__FILE__) . 'assets/user-listing.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('ult-user-listing', 'ULT_AJAX', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'per_page' => self::PER_PAGE,
        ]);

        $css = "
        .ult-wrap{max-width:900px;margin:20px 0}
        .ult-form{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
        .ult-form input{padding:8px;min-width:220px}
        .ult-form button{padding:8px 14px;cursor:pointer}
        .ult-table{width:100%;border-collapse:collapse}
        .ult-table th,.ult-table td{border:1px solid #ddd;padding:8px;text-align:left}
        .ult-pagination{display:flex;gap:8px;align-items:center;margin-top:12px;flex-wrap:wrap}
        .ult-pagination button{padding:6px 10px;cursor:pointer}
        .ult-muted{opacity:.7}
        ";
        wp_register_style('ult-inline-style', false);
        wp_enqueue_style('ult-inline-style');
        wp_add_inline_style('ult-inline-style', $css);
    }

    private function current_page_has_shortcode($shortcode)
    {
        global $post;
        if (!$post) return false;
        return has_shortcode($post->post_content, $shortcode);
    }

    public function render_shortcode()
    {
        ob_start(); ?>
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

    public function ajax_get_users()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;

        $filters = [
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'surnames' => isset($_POST['surnames']) ? sanitize_text_field($_POST['surnames']) : '',
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
        ];

        $api_response = $this->simulate_api_post($filters);

        $users = $api_response['usuarios'];
        $users_filtered = $this->apply_filters($users, $filters);

        $per_page = self::PER_PAGE;
        $total = count($users_filtered);
        $total_pages = max(1, (int) ceil($total / $per_page));
        $page = min($page, $total_pages);

        $offset = ($page - 1) * $per_page;
        $users_page = array_slice($users_filtered, $offset, $per_page);

        $html = $this->render_table_html($users_page, $total, $page, $total_pages);

        wp_send_json_success([
            'html' => $html,
            'page' => $page,
            'total_pages' => $total_pages,
            'total' => $total,
        ]);
    }

    private function simulate_api_post($filters)
    {
        // Simulamos una llamada POST a un API (no disponible en el enunciado).
        // Para que sea verificable dentro del proyecto, exponemos un endpoint REST interno
        // y hacemos un wp_remote_post contra él (mismo flujo que sería contra un API externo).

        $endpoint = rest_url('ult/v1/users');
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            'body' => wp_json_encode($filters),
            'timeout' => 10,
        ]);

        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body, true);
            if ($code >= 200 && $code < 300 && is_array($json) && isset($json['usuarios'])) {
                return $json;
            }
        }

        // Fallback: generamos datos localmente si algo falla.
        $usuarios = [];
        for ($i = 1; $i <= 50; $i++) {
            $usuarios[] = [
                'id' => $i,
                'email' => "admin{$i}@yopmail.com",
                'name' => "Nombre{$i}",
                'surname1' => "Apellido{$i}A",
                'surname2' => "Apellido{$i}B",
                // Campo extra para mostrar "nombre de usuario".
                'username' => "user{$i}",
            ];
        }

        return ['usuarios' => $usuarios];
    }

    private function apply_filters($users, $filters)
    {
        $name = mb_strtolower(trim($filters['name']));
        $surnames = mb_strtolower(trim($filters['surnames']));
        $email = mb_strtolower(trim($filters['email']));

        return array_values(array_filter($users, function ($u) use ($name, $surnames, $email) {
            $haystack_name = mb_strtolower($u['name']);
            $haystack_surnames = mb_strtolower(trim($u['surname1'] . ' ' . $u['surname2']));
            $haystack_email = mb_strtolower($u['email']);

            if ($name !== '' && mb_strpos($haystack_name, $name) === false) return false;
            if ($surnames !== '' && mb_strpos($haystack_surnames, $surnames) === false) return false;
            if ($email !== '' && mb_strpos($haystack_email, $email) === false) return false;

            return true;
        }));
    }

    private function render_table_html($users, $total, $page, $total_pages)
    {
        ob_start(); ?>
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
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="ult-muted">Sin resultados</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
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

    // ✅ METODI REST DENTRO LA CLASSE
    public function register_rest_routes()
    {
        register_rest_route('ult/v1', '/users', [
            'methods'  => 'POST',
            'callback' => [$this, 'rest_get_users'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_get_users(\WP_REST_Request $request)
    {
        $filters = $request->get_json_params() ?: [];
        $api_response = $this->simulate_api_post($filters);
        return new \WP_REST_Response($api_response, 200);
    }
}

new ULT_User_Listing_Test();
