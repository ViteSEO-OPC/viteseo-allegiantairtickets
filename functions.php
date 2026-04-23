<?php
/**
 * Child theme bootstrap
 */

/**
 * Filter get_avatar_url to use custom uploaded avatar if present.
 */
add_filter('get_avatar_url', 'ileg_filter_get_avatar_url', 10, 3);
function ileg_filter_get_avatar_url($url, $id_or_email, $args)
{
    $user_id = 0;
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    } elseif ($id_or_email instanceof WP_User) {
        $user_id = $id_or_email->ID;
    } elseif (is_a($id_or_email, 'WP_Post')) {
        $user_id = (int) $id_or_email->post_author;
    } elseif (is_array($id_or_email) && isset($id_or_email['user_id'])) { // Gravatar use case
        $user_id = (int) $id_or_email['user_id'];
    }

    if ($user_id) {
        $custom_avatar_id = get_user_meta($user_id, 'ileg_custom_avatar', true);
        if ($custom_avatar_id) {
            $img = wp_get_attachment_image_url($custom_avatar_id, 'thumbnail'); // or full/medium
            if ($img) {
                return $img;
            }
        }
    }

    return $url;
}

if (!function_exists('ileg_asset_ver')) {
    function ileg_asset_ver($relative_path)
    {
        $full_path = get_stylesheet_directory() . $relative_path;
        if (file_exists($full_path)) {
            return (string) filemtime($full_path);
        }
        return wp_get_theme()->get('Version');
    }
}

add_action('wp_enqueue_scripts', function () {
    // Parent first
    wp_enqueue_style(
        'parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        wp_get_theme(get_template())->get('Version')
    );

    // Child global
    wp_enqueue_style(
        'ileg-child-style',
        get_stylesheet_uri(),
        ['parent-style'],
        wp_get_theme()->get('Version')
    );

    // Optional component CSS
    wp_enqueue_style(
        'ileg-header',
        get_stylesheet_directory_uri() . '/assets/css/header.css',
        ['ileg-child-style'],
        ileg_asset_ver('/assets/css/header.css')
    );
    // wp_enqueue_style(
    //   'ileg-header-community',
    //   get_stylesheet_directory_uri() . '/assets/css/header.css',
    //   ['ileg-child-style'],
    //   wp_get_theme()->get('Version')
    // );
    wp_enqueue_style(
        'ileg-footer',
        get_stylesheet_directory_uri() . '/assets/css/footer.css',
        ['ileg-child-style'],
        ileg_asset_ver('/assets/css/footer.css')
    );

    // Scripts
    wp_enqueue_script(
        'ileg-app',
        get_stylesheet_directory_uri() . '/assets/js/app.js',
        [],
        ileg_asset_ver('/assets/js/app.js'),
        true
    );

    wp_enqueue_script(
        'ileg-header-active-links',
        get_stylesheet_directory_uri() . '/assets/js/header-active-links.js',
        [],
        ileg_asset_ver('/assets/js/header-active-links.js'),
        true
    );
});

add_filter('render_block', function ($block_content, $block) {
    if (is_admin()) {
        return $block_content;
    }

    if (($block['blockName'] ?? '') !== 'core/template-part') {
        return $block_content;
    }

    $slug = $block['attrs']['slug'] ?? '';
    if (!in_array($slug, ['header', 'header-community', 'footer'], true)) {
        return $block_content;
    }

    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $request_path = wp_parse_url($request_uri, PHP_URL_PATH);
    $request_path = is_string($request_path) && $request_path !== '' ? $request_path : '/';
    $is_ko = (bool) preg_match('#^/ko(?:/|$)#', $request_path);

    $part_slug = $slug;
    if ($slug === 'footer' && $is_ko) {
        $ko_footer_file = get_stylesheet_directory() . '/parts/footer-ko.html';
        if (file_exists($ko_footer_file)) {
            $part_slug = 'footer-ko';
        }
    }

    $part_file = get_stylesheet_directory() . "/parts/{$part_slug}.html";
    if (!file_exists($part_file)) {
        return $block_content;
    }

    $part_markup = file_get_contents($part_file);
    if ($part_markup === false || trim($part_markup) === '') {
        return $block_content;
    }

    $rendered_part = do_blocks($part_markup);

    $tag_name = strtolower((string) ($block['attrs']['tagName'] ?? 'div'));
    if (!in_array($tag_name, ['header', 'footer', 'div', 'section', 'aside', 'main'], true)) {
        $tag_name = 'div';
    }

    return sprintf(
        '<%1$s class="wp-block-template-part">%2$s</%1$s>',
        esc_attr($tag_name),
        $rendered_part
    );
}, 20, 2);

add_action('wp_enqueue_scripts', function () {
    // Detect common Bootstrap handles (best-effort)
    $css_handles = ['bootstrap', 'bootstrap-css', 'bs5', 'bootstrap-5'];
    $js_handles = ['bootstrap', 'bootstrap-js', 'bootstrap-bundle', 'bs5'];

    $has_bootstrap_css = array_reduce($css_handles, fn($c, $h) => $c || wp_style_is($h, 'enqueued') || wp_style_is($h, 'registered'), false);
    $has_bootstrap_js = array_reduce($js_handles, fn($c, $h) => $c || wp_script_is($h, 'enqueued') || wp_script_is($h, 'registered'), false);

    if (!$has_bootstrap_css) {
        wp_enqueue_style(
            'bootstrap', // our handle
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            [],
            '5.3.3'
        );
    }

    if (!$has_bootstrap_js) {
        wp_enqueue_script(
            'bootstrap-bundle', // Popper included
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            [],
            '5.3.3',
            true
        );
    }
}, 20);

// /wp-content/themes/LEAFLET-CHILD-THEME/functions.php
add_action('wp_enqueue_scripts', function () {
    // Leaflet core
    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

    // Font Awesome 6 (solid)
    wp_enqueue_style(
        'fa6',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        [],
        '6.5.2'
    );
});

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('wp-block-styles');
});

// Auto-register any block with block.json under /blocks/*/
add_action('init', function () {
    $base = get_stylesheet_directory() . '/blocks';
    foreach (glob($base . '/*/block.json') as $json)
        register_block_type(dirname($json));
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'fa-6',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        [],
        '6.5.2'
    );
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'ilegiants-outfit',
        'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap',
        [],
        null
    );
});
add_action('enqueue_block_editor_assets', function () {
    wp_enqueue_style('ilegiants-outfit-editor', 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap', [], null);
});

add_filter('template_include', function ($template) {
    if (is_page('community-register')) {
        $custom = get_stylesheet_directory() . '/page-register.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }

    return $template;
});

if (!function_exists('ileg_get_frontend_auth_url')) {
    /**
     * Return the URL of a custom frontend auth page.
     *
     * @param string $type login|register|forgot|reset
     */
    function ileg_get_frontend_auth_url($type)
    {
        $type = sanitize_key((string) $type);
        $map = [
            'login' => ['login'],
            'register' => ['register', 'community-register'],
            'forgot' => ['forgot-password'],
            'reset' => ['reset-password'],
        ];

        if (!isset($map[$type])) {
            return '';
        }

        foreach ($map[$type] as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                return get_permalink($page);
            }
        }

        return home_url('/' . $map[$type][0] . '/');
    }
}

add_filter('login_url', function ($login_url, $redirect, $force_reauth) {
    $custom_login = ileg_get_frontend_auth_url('login');
    if (!$custom_login) {
        return $login_url;
    }

    $args = [];
    if (!empty($redirect)) {
        $args['redirect_to'] = wp_validate_redirect($redirect, home_url('/'));
    }
    if ($force_reauth) {
        $args['reauth'] = '1';
    }

    return add_query_arg($args, $custom_login);
}, 10, 3);

add_filter('lostpassword_url', function ($lostpassword_url, $redirect) {
    $custom_forgot = ileg_get_frontend_auth_url('forgot');
    if (!$custom_forgot) {
        return $lostpassword_url;
    }

    if (!empty($redirect)) {
        $custom_forgot = add_query_arg(
            'redirect_to',
            wp_validate_redirect($redirect, home_url('/')),
            $custom_forgot
        );
    }

    return $custom_forgot;
}, 10, 2);

add_filter('register_url', function ($register_url) {
    $custom_register = ileg_get_frontend_auth_url('register');
    return $custom_register ?: $register_url;
});

add_action('login_init', function () {
    $request_method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($request_method === 'POST') {
        // Keep core login/lostpassword/reset handlers intact.
        return;
    }

    $action = isset($_REQUEST['action'])
        ? sanitize_key(wp_unslash($_REQUEST['action']))
        : 'login';

    // Do not hijack logout and other non-screen actions.
    if (in_array($action, ['logout', 'postpass', 'confirmaction'], true)) {
        return;
    }

    $target_type = 'login';
    $allowed_args = ['redirect_to', 'reauth', 'login', 'password', 'checkemail', 'loggedout'];

    if (in_array($action, ['lostpassword', 'retrievepassword'], true)) {
        $target_type = 'forgot';
        $allowed_args = ['redirect_to', 'checkemail', 'error'];
    } elseif ($action === 'register') {
        $target_type = 'register';
        $allowed_args = ['redirect_to', 'registration', 'error'];
    } elseif (in_array($action, ['rp', 'resetpass'], true)) {
        $target_type = 'reset';
        $allowed_args = ['key', 'login', 'error'];
    }

    $target = ileg_get_frontend_auth_url($target_type);
    if (!$target) {
        return;
    }

    $args = [];
    foreach ($allowed_args as $key) {
        if (!isset($_REQUEST[$key])) {
            continue;
        }
        $raw = wp_unslash($_REQUEST[$key]);
        if ($key === 'redirect_to') {
            $args[$key] = wp_validate_redirect($raw, home_url('/'));
        } else {
            $args[$key] = sanitize_text_field((string) $raw);
        }
    }

    wp_safe_redirect(add_query_arg($args, $target));
    exit;
});

add_action('wp_enqueue_scripts', function () {

    // Make sure this runs after your main scripts are registered
    wp_enqueue_script(
        'ileg-auth-header',
        get_stylesheet_directory_uri() . '/assets/js/auth-header.js',
        [],
        ileg_asset_ver('/assets/js/auth-header.js'),
        true
    );

    // Resolve URLs
    $login_page = get_page_by_path('login');
    $login_url = $login_page ? get_permalink($login_page) : wp_login_url();

    // Adjust this if your register slug is different
    $profile_url = home_url('/community/profile/'); // placeholder

    wp_localize_script('ileg-auth-header', 'IlegAuth', [
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl' => $login_url,
        'logoutUrl' => wp_logout_url(home_url('/community/')),
        'profileUrl' => $profile_url,
    ]);
}, 25);

/**
 * Handle frontend profile update
 */
add_action('admin_post_ileg_update_profile', 'ileg_handle_profile_update');
add_action('admin_post_nopriv_ileg_update_profile', 'ileg_handle_profile_update_guest');

function ileg_handle_profile_update_guest()
{
    wp_redirect(wp_login_url(home_url('/community/profile/')));
    exit;
}

function ileg_handle_profile_update()
{
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(home_url('/community/profile/')));
        exit;
    }

    if (
        !isset($_POST['ileg_profile_nonce']) ||
        !wp_verify_nonce($_POST['ileg_profile_nonce'], 'ileg_profile_update')
    ) {
        wp_die('Security check failed.');
    }

    $user_id = get_current_user_id();
    $redirect_base = wp_get_referer() ?: home_url('/community/profile/');

    $userdata = ['ID' => $user_id];

    if (isset($_POST['display_name'])) {
        $userdata['display_name'] = sanitize_text_field($_POST['display_name']);
    }
    if (isset($_POST['user_email'])) {
        $userdata['user_email'] = sanitize_email($_POST['user_email']);
    }

    // Password
    $pass1 = isset($_POST['pass1']) ? $_POST['pass1'] : '';
    $pass2 = isset($_POST['pass2']) ? $_POST['pass2'] : '';

    if ($pass1 || $pass2) {
        if ($pass1 !== $pass2) {
            wp_redirect(add_query_arg('profile-error', 'password_mismatch', $redirect_base));
            exit;
        }
        $userdata['user_pass'] = $pass1;
    }

    $result = wp_update_user($userdata);
    if (is_wp_error($result)) {
        wp_redirect(add_query_arg('profile-error', $result->get_error_code(), $redirect_base));
        exit;
    }

    // Extra meta
    if (isset($_POST['first_name'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
    }
    if (isset($_POST['last_name'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
    }

    // Handle Avatar Upload
    if (!empty($_FILES['profile_avatar']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attach_id = media_handle_upload('profile_avatar', 0); // 0 = not attached to a post

        if (is_wp_error($attach_id)) {
            // Optional: handle error, e.g. redirect with error code
            // wp_redirect( add_query_arg( 'profile-error', 'avatar_upload_failed', $redirect_base ) );
            // exit;
        } else {
            update_user_meta($user_id, 'ileg_custom_avatar', $attach_id);
        }
    }

    if (isset($_GET['submit-success']) && $_GET['submit-success'] === '1'): ?>
        <div class="alert alert-success mb-4">
            Your post has been submitted for review.
        </div>
    <?php endif;

    wp_redirect(add_query_arg('profile-updated', '1', $redirect_base));
    exit;
}

/**
 * Frontend "Submit Post" handler
 */
add_action('admin_post_community_submit_post', 'ileg_handle_community_submit_post');
add_action('admin_post_nopriv_community_submit_post', 'ileg_handle_community_submit_post_guest');

function ileg_handle_community_submit_post_guest()
{
    $redirect = wp_get_referer() ?: home_url('/community/submit-post/');

    $login_page = get_page_by_path('login');
    if ($login_page) {
        $login_url = add_query_arg(
            'redirect_to',
            urlencode($redirect),
            get_permalink($login_page)
        );
    } else {
        // fallback to default wp-login.php if custom page is missing
        $login_url = wp_login_url($redirect);
    }

    wp_redirect($login_url);
    exit;
}


function ileg_handle_community_submit_post()
{
    // Must be logged in
    if (!is_user_logged_in()) {
        wp_redirect('/login');
        exit;
    }

    // Nonce check
    if (
        !isset($_POST['community_post_nonce']) ||
        !wp_verify_nonce($_POST['community_post_nonce'], 'community_submit_post')
    ) {
        wp_die('Security check failed. Please reload the page and try again.');
    }

    $redirect_base = wp_get_referer() ?: home_url('/community/submit-post/');

    // Variant: blog vs image
    $variant = isset($_POST['post_variant']) ? sanitize_text_field($_POST['post_variant']) : 'blog';
    $variant = in_array($variant, ['blog', 'image'], true) ? $variant : 'blog';

    // Core fields
    $title = sanitize_text_field($_POST['post_title'] ?? '');
    $content = wp_kses_post($_POST['post_content'] ?? '');
    $tags_raw = sanitize_text_field($_POST['post_tags'] ?? '');
    $excerpt = sanitize_textarea_field($_POST['post_excerpt'] ?? '');

    $tags = array_filter(array_map('trim', explode(',', $tags_raw)));

    /**
     * Placeholder title logic for IMAGE posts:
     * If user leaves title blank, store "(no_title)".
     * We'll hide this later in the UI when rendering.
     */
    if ($variant === 'image' && $title === '') {
        $title = '(no_title)';
    }

    /**
     * VALIDATION
     * - Blog: title + content required
     * - Image: only featured image required (title optional thanks to placeholder)
     */
    if ($variant === 'blog') {

        if ($title === '' || $content === '') {
            wp_redirect(add_query_arg('submit-error', 'missing_fields', $redirect_base));
            exit;
        }

    } else { // image

        if (empty($_FILES['featured_image']['name'])) {
            wp_redirect(add_query_arg('submit-error', 'missing_image', $redirect_base));
            exit;
        }
    }

    // CATEGORY
    if ($variant === 'image') {
        // force "imagepost" category for images
        $image_cat = get_category_by_slug('imagepost');
        $category = $image_cat ? (int) $image_cat->term_id : 0;
    } else {
        $category = intval($_POST['post_category'] ?? 0);
    }

    // DRAFT vs SUBMIT
    $action_type = sanitize_text_field($_POST['post_action'] ?? 'submit');
    $post_status = ($action_type === 'draft') ? 'draft' : 'pending';

    // INSERT POST
    $post_id = wp_insert_post(
        [
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => $post_status,
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
            'post_category' => $category ? [$category] : [],
            'tags_input' => $tags,
        ],
        true
    );

    if (is_wp_error($post_id)) {
        // Optional debugging:
        // error_log( 'Submit Post error: ' . $post_id->get_error_message() );
        wp_redirect(add_query_arg('submit-error', 'insert_failed', $redirect_base));
        exit;
    }

    // FEATURED IMAGE
    if (!empty($_FILES['featured_image']['name'])) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $uploaded_file = $_FILES['featured_image'];
        // Use error suppression just in case tmp_name is missing/invalid, though unlikely if name is present
        $uploaded_hash = @md5_file($uploaded_file['tmp_name']);
        $uploaded_size = $uploaded_file['size'];
        $existing_id = 0;

        // Check recent uploads by this user to avoid duplication
        $args = [
            'post_type' => 'attachment',
            'post_author' => get_current_user_id(),
            'posts_per_page' => 50, // Check last 50 images
            'post_status' => 'inherit',
            'fields' => 'ids',
        ];

        $recent_attachments = get_posts($args);

        foreach ($recent_attachments as $att_id) {
            $file_path = get_attached_file($att_id);
            if ($file_path && file_exists($file_path)) {
                if (filesize($file_path) === $uploaded_size) {
                    if (md5_file($file_path) === $uploaded_hash) {
                        $existing_id = $att_id;
                        break;
                    }
                }
            }
        }

        if ($existing_id) {
            $attach_id = $existing_id;
        } else {
            // No duplicate found, proceed with upload
            if (!function_exists('media_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
            }
            $attach_id = media_handle_upload('featured_image', $post_id);
        }

        if (!is_wp_error($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
        }
    }

    // SUCCESS REDIRECT
    $redirect_target = home_url('/community/profile/');
    wp_redirect(add_query_arg('submit-success', $post_status, $redirect_target));
    exit;
}

add_action('wp_enqueue_scripts', function () {
    // Only load on the submit post page to keep things lean
    if (!is_page('community-submit-post') && !is_page('submit-post')) {
        return;
    }

    wp_enqueue_script(
        'ileg-submit-post',
        get_stylesheet_directory_uri() . '/assets/js/submit-post.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script('ileg-submit-post', 'IlegSubmitPost', [
        'nonce' => wp_create_nonce('community_submit_post'),
    ]);
}, 30);

/**
 * Redirect failed logins back to the frontend /login page.
 */
add_action('wp_login_failed', 'ileg_redirect_on_login_failed');

function ileg_redirect_on_login_failed($username)
{
    // Find the custom login page
    $login_page = get_page_by_path('login');
    if (!$login_page) {
        return; // fallback: let WP handle it
    }

    $login_url = get_permalink($login_page);

    // Preserve original redirect_to if present
    $redirect_to = isset($_REQUEST['redirect_to'])
        ? esc_url_raw(wp_unslash($_REQUEST['redirect_to']))
        : '';

    $args = array('login' => 'failed');
    if ($redirect_to) {
        $args['redirect_to'] = $redirect_to;
    }

    wp_redirect(add_query_arg($args, $login_url));
    exit;
}


/**
 * Use custom frontend Reset Password page in reset emails.
 */
add_filter('retrieve_password_message', 'ileg_custom_reset_email_link', 10, 4);
function ileg_custom_reset_email_link($message, $key, $user_login, $user_data)
{

    // URL of your frontend reset page (slug must be /reset-password/)
    $reset_url = add_query_arg(
        array(
            'key' => $key,
            'login' => rawurlencode($user_login),
        ),
        home_url('/reset-password/')
    );

    $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

    // Build a simple email message that uses our custom link
    $message = "Hi {$user_login},\r\n\r\n";
    $message .= "We received a request to reset your password on {$site_name}.\r\n\r\n";
    $message .= "To reset your password, click the link below:\r\n\r\n";
    $message .= "{$reset_url}\r\n\r\n";
    $message .= "If you did not request this, you can safely ignore this email.\r\n";

    return $message;
}

/**
 * AJAX: toggle like for a post (logged-in users only).
 */
add_action('wp_ajax_ileg_toggle_like', 'ileg_toggle_like');

function ileg_toggle_like()
{
    check_ajax_referer('dg_like_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'not_logged_in'], 401);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id || get_post_status($post_id) !== 'publish') {
        wp_send_json_error(['message' => 'invalid_post'], 400);
    }

    $user_id = get_current_user_id();
    $liked_posts = (array) get_user_meta($user_id, '_ileg_liked_posts', true);
    $liked_posts = array_map('intval', $liked_posts);
    $already_liked = in_array($post_id, $liked_posts, true);

    if ($already_liked) {
        // UNLIKE
        $liked_posts = array_values(array_diff($liked_posts, [$post_id]));
        $delta = -1;
        $liked = false;
    } else {
        // LIKE
        $liked_posts[] = $post_id;
        $liked_posts = array_values(array_unique($liked_posts));
        $delta = 1;
        $liked = true;
    }

    update_user_meta($user_id, '_ileg_liked_posts', $liked_posts);

    // Update post like count (never below 0)
    $count = (int) get_post_meta($post_id, 'likes', true);
    $count = max(0, $count + $delta);
    update_post_meta($post_id, 'likes', $count);

    wp_send_json_success([
        'liked' => $liked,
        'likes' => $count,
    ]);
}

/**
 * AJAX: track a view (for everyone).
 */
add_action('wp_ajax_ileg_track_view', 'ileg_track_view');
add_action('wp_ajax_nopriv_ileg_track_view', 'ileg_track_view');

function ileg_track_view()
{
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id || get_post_status($post_id) !== 'publish') {
        wp_send_json_error(['message' => 'invalid_post'], 400);
    }

    $count = (int) get_post_meta($post_id, 'views', true);
    $count++;
    update_post_meta($post_id, 'views', $count);

    wp_send_json_success(['views' => $count]);
}


add_action('wp_enqueue_scripts', function () {
    if (!is_page_template('page-submit-post.php'))
        return;

    wp_enqueue_editor();     // loads TinyMCE + Quicktags
    wp_enqueue_media();      // enables Add Media
});

add_filter('show_admin_bar', '__return_false');

// this is test only for comment
/* =========================================================
 * COMMENTS UX — HTML5, tidy fields, rating, badges, relative dates
 * =======================================================*/
add_action('after_setup_theme', function () {
    add_theme_support('html5', ['comment-form', 'comment-list']);
});

add_filter('comment_form_default_fields', function ($fields) {
    unset($fields['url']);
    $req = get_option('require_name_email');
    $aria = $req ? " aria-required='true' required" : '';
    $fields['author'] = '<p class="comment-form-author"><label for="author">Your name' . ($req ? ' *' : '') . '</label><input id="author" name="author" type="text"' . $aria . '></p>';
    $fields['email'] = '<p class="comment-form-email"><label for="email">Email' . ($req ? ' *' : '') . '</label><input id="email" name="email" type="email"' . $aria . '></p>';
    return $fields;
});

add_filter('comment_form_defaults', function ($d) {
    $d['title_reply'] = 'Give Feedback';
    $d['label_submit'] = 'Submit Feedback';
    $d['comment_notes_before'] = $d['comment_notes_after'] = '';

    $rating =
        '<fieldset class="ai-rate" aria-describedby="ai-rate-help">
    <legend class="ai-legend">How helpful was this article?</legend>
    <div class="ai-rate-options">
      <input type="radio" id="ai_rate_1" name="ai_rating" value="1"><label for="ai_rate_1"><i class="far fa-thumbs-down" aria-hidden="true"></i><span>Not Helpful</span></label>
      <input type="radio" id="ai_rate_2" name="ai_rating" value="2"><label for="ai_rate_2"><i class="far fa-frown" aria-hidden="true"></i><span>Needs Improvement</span></label>
      <input type="radio" id="ai_rate_3" name="ai_rating" value="3" checked><label for="ai_rate_3"><i class="far fa-smile" aria-hidden="true"></i><span>Helpful</span></label>
      <input type="radio" id="ai_rate_4" name="ai_rating" value="4"><label for="ai_rate_4"><i class="far fa-grin-stars" aria-hidden="true"></i><span>Very Helpful</span></label>
      <input type="radio" id="ai_rate_5" name="ai_rating" value="5"><label for="ai_rate_5"><i class="far fa-laugh-beam" aria-hidden="true"></i><span>Excellent</span></label>
    </div>
    <p id="ai-rate-help" class="ai-help">Pick one, then tell us why.</p>
  </fieldset>';

    $textarea = '
  <p class="comment-form-comment">
    <label for="comment">Share your feedback:</label>
    <textarea id="comment" name="comment" cols="45" rows="6" required placeholder="Your feedback helps us improve future content."></textarea>
  </p>';

    $nonce = wp_nonce_field('ai_comment_meta', 'ai_comment_nonce', true, false);
    $d['comment_field'] = $rating . $textarea . $nonce;

    $d['class_submit'] = 'ai-btn ai-btn-primary';
    $d['submit_field'] = '<p class="form-submit">%1$s %2$s</p>';
    $d['submit_button'] = '<button type="submit" class="%3$s">%4$s</button>';
    return $d;
});

add_filter('preprocess_comment', function ($data) {
    if (is_admin())
        return $data;
    if (!isset($_POST['ai_comment_nonce']) || !wp_verify_nonce($_POST['ai_comment_nonce'], 'ai_comment_meta'))
        return $data;
    $rating = isset($_POST['ai_rating']) ? intval($_POST['ai_rating']) : 0;
    if ($rating < 1 || $rating > 5)
        wp_die(__('Please select a rating before submitting.'), '', ['back_link' => true]);
    return $data;
});

add_action('comment_post', function ($comment_ID) {
    if (!isset($_POST['ai_comment_nonce']) || !wp_verify_nonce($_POST['ai_comment_nonce'], 'ai_comment_meta'))
        return;
    $rating = isset($_POST['ai_rating']) ? max(1, min(5, intval($_POST['ai_rating']))) : '';
    $contact = !empty($_POST['ai_contact_ok']) ? 1 : 0;
    $research = !empty($_POST['ai_research_ok']) ? 1 : 0;
    if ($rating)
        add_comment_meta($comment_ID, 'ai_rating', $rating, true);
    add_comment_meta($comment_ID, 'ai_contact_ok', $contact, true);
    add_comment_meta($comment_ID, 'ai_research_ok', $research, true);
});

add_filter('get_comment_text', function ($text, $comment) {
    $rating = get_comment_meta($comment->comment_ID, 'ai_rating', true);
    if ($rating) {
        $labels = [1 => 'Not Helpful', 2 => 'Needs Improvement', 3 => 'Helpful', 4 => 'Very Helpful', 5 => 'Excellent'];
        $label = $labels[intval($rating)] ?? (string) $rating;
        $badge = '<p class="ai-rating-badge ai-rating-' . intval($rating) . '" aria-label="Rating: ' . $label . '"><span>' . $label . '</span></p>';
        $text = $badge . $text;
    }
    return $text;
}, 10, 2);

add_filter('comment_form_logged_in', '__return_empty_string');

add_filter('get_comment_date', function ($date, $format, $comment) {
    if (is_admin() || !($comment instanceof WP_Comment))
        return $date;
    $now = (int) current_time('timestamp');
    $ts = (int) get_comment_time('U');
    if (!$ts)
        $ts = (int) mysql2date('U', $comment->comment_date);
    if (!$ts)
        return $date;
    $diff = max(0, $now - $ts);
    $days = (int) floor($diff / DAY_IN_SECONDS);

    if ($days === 0) {
        if ($diff >= HOUR_IN_SECONDS) {
            $h = (int) floor($diff / HOUR_IN_SECONDS);
            return sprintf(_n('%s hour ago', '%s hours ago', $h, 'your-textdomain'), $h);
        }$m = max(1, (int) floor($diff / MINUTE_IN_SECONDS));
        return sprintf(_n('%s minute ago', '%s minutes ago', $m, 'your-textdomain'), $m);
    }
    if ($days === 1)
        return __('Yesterday', 'your-textdomain');
    if ($days < 7)
        return sprintf(_n('%s day ago', '%s days ago', $days, 'your-textdomain'), $days);
    if ($days < 30) {
        $w = (int) floor($days / 7);
        return $w === 1 ? __('A week ago', 'your-textdomain') : sprintf(_n('%s week ago', '%s weeks ago', $w, 'your-textdomain'), $w);
    }
    if ($days < 365) {
        $mo = (int) floor($days / 30);
        return $mo === 1 ? __('A month ago', 'your-textdomain') : sprintf(_n('%s months ago', '%s months ago', $mo, 'your-textdomain'), $mo);
    }
    $y = (int) floor($days / 365);
    return $y === 1 ? __('A year ago', 'your-textdomain') : sprintf(_n('%s years ago', '%s years ago', $y, 'your-textdomain'), $y);
}, 10, 3);




/* =================================================
 * SEO: PER PAGE TITLE + META DESCRIPTION
 * ================================================= */
function ai_get_seo_page_meta_from_path()
{
    $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? strtolower(trim($path, '/')) : '';
    $path = str_replace(' ', '-', $path);

    $seo_map = [
        'about-us' => [
            'title' => 'About Us: Our Travel Mission',
            'description' => 'Learn how we help travelers explore smarter and go further.',
        ],
        'community' => [
            'title' => 'Share Your Journey. Inspire a Life Well-Lived.',
            'description' => 'We invite you to join our community and contribute your most meaningful travel experiences.',
        ],
        'travel-intelligence' => [
            'title' => 'Best Food & Culture Hotspots | Explore with Intera',
            'description' => 'Plan smarter trips with easy travel tips, from packing light and finding the best insurance to staying safe, saving money, and exploring with confidence.',
        ],
        'regions' => [
            'title' => 'Food, Culture, Hot Spots in One Map',
            'description' => 'Check where the nearest food hub, best culture and pub spots are on maps using GPS tracker technology.',
        ],
        'south-korea' => [
            'title' => 'South Korea Travel Guide | Seoul, K-Pop, Culture',
            'description' => 'Experience South Korea like a local. Explore Seoul\'s top attractions, dive into Korean culture, visit must-see K-pop spots, and plan outdoor adventures with your free travel PDF.',
        ],
        'japan' => [
            'title' => 'Japan Travel Guide | 7-Day Itinerary, Tokyo, Kyoto',
            'description' => 'Experience Japan like never before. Follow a 7-day itinerary, find the best travel seasons, taste iconic foods, and explore Tokyo, Kyoto, and Japan\'s great outdoors.',
        ],
        'thailand' => [
            'title' => 'Thailand Travel Guide | Islands, Bangkok, Food & Culture',
            'description' => 'Explore Thailand with ease. Visit stunning islands, taste legendary street food, plan budget trips, and uncover Bangkok\'s best sights with free travel PDFs and maps.',
        ],
        'blogs' => [
            'title' => 'Travel Blog | Stories, Hacks & Inspiration',
            'description' => 'Read travel stories, hacks, and insights from global explorers.',
        ],
        'contact-us' => [
            'title' => 'Contact Us | Travel Questions & Support',
            'description' => 'Need help? Reach out for travel questions, feedback, and collaboration ideas.',
        ],
    ];

    return $seo_map[$path] ?? null;
}

add_filter('pre_get_document_title', function ($title) {
    if (is_admin()) {
        return $title;
    }
    $seo = ai_get_seo_page_meta_from_path();
    return !empty($seo['title']) ? $seo['title'] : $title;
}, 99);

add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }
    $seo = ai_get_seo_page_meta_from_path();
    if (!empty($seo['description'])) {
        echo '<meta name="description" content="' . esc_attr($seo['description']) . '">' . "\n";
    }
}, 0);

/* =================================================
 * SEO: ROBOTS META
 * ================================================= */
add_action('wp_head', function () {
    echo '<meta name="robots" content="index, follow">';
    return;
}, 1);

/* =================================================
 * GLOBAL ROBOTS HEADERS
 * ================================================= */
add_action('send_headers', function () {
    header('X-Robots-Tag: index, follow, archive, snippet', true);
});