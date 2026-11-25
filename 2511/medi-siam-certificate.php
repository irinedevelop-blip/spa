<?php
/*
Plugin Name: Medi Siam Certificate
Description: Покупка подарочных сертификатов WooCommerce: сумма, получатель, PDF-сертификат, email-доставка, проверка и погашение.
Version: 1.3.0
Author: Medi Siam
*/

if (!defined('ABSPATH')) {
    exit;
}

define('MSCERT_VERSION', '1.3.0');
define('MSCERT_PATH', plugin_dir_path(__FILE__));
define('MSCERT_URL', plugin_dir_url(__FILE__));

if (is_file(MSCERT_PATH . 'vendor/autoload.php')) {
    require_once MSCERT_PATH . 'vendor/autoload.php';
}

/*
|--------------------------------------------------------------------------
| ОСНОВНЫЕ ПУТИ
|--------------------------------------------------------------------------
*/

function mscert_purchase_template_path() {
    return MSCERT_PATH . 'assets/templates/purchase-certificate.php';
}

function mscert_pdf_template_path() {
    return MSCERT_PATH . 'assets/templates/certificate-pdf.php';
}

/*
|--------------------------------------------------------------------------
| АКТИВАЦИЯ
|--------------------------------------------------------------------------
*/

register_activation_hook(__FILE__, 'mscert_activate_plugin');

function mscert_activate_plugin() {
    mscert_register_certificate_cpt();
    mscert_create_certificate_product();
    mscert_create_purchase_page();
    mscert_force_classic_checkout_page();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

/*
|--------------------------------------------------------------------------
| CLASSIC CHECKOUT
|--------------------------------------------------------------------------
*/

function mscert_force_classic_checkout_page() {
    if (!class_exists('WooCommerce') || !function_exists('wc_get_page_id')) {
        return;
    }

    $checkout_page_id = (int) wc_get_page_id('checkout');

    if ($checkout_page_id < 1) {
        return;
    }

    $page = get_post($checkout_page_id);

    if (!$page) {
        return;
    }

    if (strpos((string) $page->post_content, '[woocommerce_checkout]') === false) {
        wp_update_post([
            'ID'           => $checkout_page_id,
            'post_content' => '[woocommerce_checkout]',
        ]);
    }
}

/*
|--------------------------------------------------------------------------
| CPT СЕРТИФИКАТОВ
|--------------------------------------------------------------------------
*/

add_action('init', 'mscert_register_certificate_cpt');

function mscert_register_certificate_cpt() {
    register_post_type('ms_certificate', [
        'labels' => [
            'name'          => 'Сертификаты',
            'singular_name' => 'Сертификат',
            'menu_name'     => 'Сертификаты SPA',
            'add_new'       => 'Добавить сертификат',
            'add_new_item'  => 'Добавить сертификат',
            'edit_item'     => 'Редактировать сертификат',
            'new_item'      => 'Новый сертификат',
            'view_item'     => 'Посмотреть сертификат',
            'search_items'  => 'Искать сертификаты',
            'not_found'     => 'Сертификаты не найдены',
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'menu_icon'       => 'dashicons-tickets-alt',
        'supports'        => ['title'],
        'capability_type' => 'post',
    ]);
}

/*
|--------------------------------------------------------------------------
| СКРЫТЫЙ ТОВАР WOOCOMMERCE
|--------------------------------------------------------------------------
*/

function mscert_create_certificate_product() {
    if (!class_exists('WooCommerce')) {
        return 0;
    }

    $existing_id = (int) get_option('mscert_product_id');

    if ($existing_id > 0 && get_post($existing_id)) {
        return $existing_id;
    }

    $product_id = wp_insert_post([
        'post_title'   => 'Подарочный сертификат',
        'post_name'    => 'gift-certificate-medi-siam',
        'post_status'  => 'publish',
        'post_type'    => 'product',
        'post_content' => 'Подарочный сертификат Medi Siam SPA.',
    ]);

    if (!$product_id || is_wp_error($product_id)) {
        return 0;
    }

    update_post_meta($product_id, '_regular_price', '0');
    update_post_meta($product_id, '_price', '0');
    update_post_meta($product_id, '_virtual', 'yes');
    update_post_meta($product_id, '_sold_individually', 'no');
    update_post_meta($product_id, '_manage_stock', 'no');
    update_post_meta($product_id, '_stock_status', 'instock');

    wp_set_object_terms($product_id, 'simple', 'product_type');

    if (function_exists('wc_get_product')) {
        $product = wc_get_product($product_id);

        if ($product) {
            $product->set_catalog_visibility('hidden');
            $product->save();
        }
    }

    update_option('mscert_product_id', (int) $product_id);

    return (int) $product_id;
}

/*
|--------------------------------------------------------------------------
| СТРАНИЦА ПОКУПКИ
|--------------------------------------------------------------------------
*/

function mscert_create_purchase_page() {
    $existing_page_id = (int) get_option('mscert_purchase_page_id');

    if ($existing_page_id > 0 && get_post($existing_page_id)) {
        return $existing_page_id;
    }

    $page = get_page_by_path('purchase-certificate');

    if ($page) {
        update_option('mscert_purchase_page_id', (int) $page->ID);
        return (int) $page->ID;
    }

    $page_id = wp_insert_post([
        'post_title'   => 'Покупка сертификата',
        'post_name'    => 'purchase-certificate',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '[ms_certificate_purchase]',
    ]);

    if (!$page_id || is_wp_error($page_id)) {
        return 0;
    }

    update_option('mscert_purchase_page_id', (int) $page_id);

    return (int) $page_id;
}

/*
|--------------------------------------------------------------------------
| СТИЛИ И СКРИПТЫ
|--------------------------------------------------------------------------
*/

add_action('wp_enqueue_scripts', 'mscert_enqueue_assets');

function mscert_enqueue_assets() {
    $css_path = MSCERT_PATH . 'assets/css/certificate.css';
    $js_path  = MSCERT_PATH . 'assets/js/certificate.js';

    wp_enqueue_style(
        'mscert-certificate',
        MSCERT_URL . 'assets/css/certificate.css',
        [],
        is_file($css_path) ? filemtime($css_path) : MSCERT_VERSION
    );

    wp_enqueue_script(
        'mscert-certificate',
        MSCERT_URL . 'assets/js/certificate.js',
        [],
        is_file($js_path) ? filemtime($js_path) : MSCERT_VERSION,
        true
    );

    wp_localize_script(
        'mscert-certificate',
        'MSCERT',
        [
            'minAmount' => mscert_get_min_amount(),
            'maxAmount' => mscert_get_max_amount(),
            'currency'  => '₸',
        ]
    );
}

/*
|--------------------------------------------------------------------------
| ШОРТКОД СТРАНИЦЫ ПОКУПКИ
|--------------------------------------------------------------------------
*/

add_shortcode('ms_certificate_purchase', 'mscert_purchase_shortcode');

function mscert_purchase_shortcode() {
    if (!class_exists('WooCommerce')) {
        return '<div class="mscert-notice">Для покупки сертификата нужен активный WooCommerce.</div>';
    }

    $product_id = mscert_create_certificate_product();

    if (!$product_id) {
        return '<div class="mscert-notice">Не удалось создать товар сертификата.</div>';
    }

    $template = mscert_purchase_template_path();

    if (!is_file($template) || !is_readable($template)) {
        return '<div class="mscert-notice">Не найден шаблон страницы покупки: '
            . esc_html($template)
            . '</div>';
    }

    $preview_css_path = MSCERT_PATH . 'assets/css/purchase-certificate.css';

    if (is_file($preview_css_path)) {
        wp_enqueue_style(
            'mscert-purchase-certificate',
            MSCERT_URL . 'assets/css/purchase-certificate.css',
            ['mscert-certificate'],
            filemtime($preview_css_path)
        );
    }

    ob_start();
    include $template;

    return (string) ob_get_clean();
}

/*
|--------------------------------------------------------------------------
| ДОБАВЛЕНИЕ СЕРТИФИКАТА В КОРЗИНУ
|--------------------------------------------------------------------------
*/

add_action('admin_post_mscert_add_to_cart', 'mscert_handle_add_to_cart');
add_action('admin_post_nopriv_mscert_add_to_cart', 'mscert_handle_add_to_cart');

function mscert_handle_add_to_cart() {
    if (!class_exists('WooCommerce')) {
        wp_die('WooCommerce не активен.');
    }

    $nonce = isset($_POST['mscert_nonce'])
        ? sanitize_text_field(wp_unslash($_POST['mscert_nonce']))
        : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'mscert_add_to_cart')) {
        wp_die('Ошибка безопасности.');
    }

    $product_id = isset($_POST['product_id'])
        ? absint($_POST['product_id'])
        : 0;

    if (!$product_id) {
        $product_id = mscert_create_certificate_product();
    }

    if (!$product_id || !get_post($product_id)) {
        wp_die('Товар сертификата не найден.');
    }

    $first_name = sanitize_text_field(
        wp_unslash($_POST['recipient_first_name'] ?? '')
    );

    $last_name = sanitize_text_field(
        wp_unslash($_POST['recipient_last_name'] ?? '')
    );

    $sender = sanitize_text_field(
        wp_unslash($_POST['sender_name'] ?? '')
    );

    $email = sanitize_email(
        wp_unslash($_POST['delivery_email'] ?? '')
    );

    $date = sanitize_text_field(
        wp_unslash($_POST['delivery_date'] ?? '')
    );

    $design = sanitize_key(
        wp_unslash($_POST['design'] ?? 'gold')
    );

    $message = sanitize_textarea_field(
        wp_unslash($_POST['message'] ?? '')
    );

    $custom_amount = isset($_POST['custom_amount'])
        ? trim((string) wp_unslash($_POST['custom_amount']))
        : '';

    if ($custom_amount !== '') {
        $amount = (float) wc_format_decimal($custom_amount);
    } else {
        $amount = isset($_POST['amount'])
            ? (float) wc_format_decimal(wp_unslash($_POST['amount']))
            : 0;
    }

    $amount = mscert_normalize_amount($amount);

    $return_url = wp_get_referer();

    if (!$return_url) {
        $return_url = home_url('/purchase-certificate/');
    }

    if (!$first_name || !$last_name) {
        wc_add_notice('Введите имя и фамилию получателя.', 'error');
        wp_safe_redirect($return_url);
        exit;
    }

    if ($email && !is_email($email)) {
        wc_add_notice('Проверьте email получателя.', 'error');
        wp_safe_redirect($return_url);
        exit;
    }

    if (
        $amount < mscert_get_min_amount() ||
        $amount > mscert_get_max_amount()
    ) {
        wc_add_notice('Некорректная сумма сертификата.', 'error');
        wp_safe_redirect($return_url);
        exit;
    }

    if (!in_array($design, ['gold', 'dark', 'classic'], true)) {
        $design = 'gold';
    }

    if (!function_exists('WC')) {
        wp_die('Корзина WooCommerce недоступна.');
    }

    if (!WC()->cart) {
        wc_load_cart();
    }

    if (!WC()->cart) {
        wp_die('Не удалось загрузить корзину WooCommerce.');
    }

    $unique_id = wp_generate_uuid4();

    $cart_item_data = [
        'mscert' => [
            'unique_id'  => $unique_id,
            'amount'     => $amount,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'sender'     => $sender,
            'email'      => $email,
            'date'       => $date,
            'design'     => $design,
            'message'    => $message,
        ],
        'unique_key' => md5($unique_id . microtime(true)),
    ];

    $cart_item_key = WC()->cart->add_to_cart(
        $product_id,
        1,
        0,
        [],
        $cart_item_data
    );

    if (!$cart_item_key) {
        wc_add_notice('Не удалось добавить сертификат в заказ.', 'error');
        wp_safe_redirect($return_url);
        exit;
    }

    wc_add_notice('Сертификат добавлен в заказ.', 'success');

    $checkout_url = function_exists('wc_get_checkout_url')
        ? wc_get_checkout_url()
        : wc_get_cart_url();

    wp_safe_redirect($checkout_url);
    exit;
}

/*
|--------------------------------------------------------------------------
| ДИНАМИЧЕСКАЯ ЦЕНА
|--------------------------------------------------------------------------
*/

add_action(
    'woocommerce_before_calculate_totals',
    'mscert_set_dynamic_cart_price',
    20
);

function mscert_set_dynamic_cart_price($cart) {
    if (is_admin() && !wp_doing_ajax()) {
        return;
    }

    if (!$cart || !is_a($cart, 'WC_Cart')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (
            !empty($cart_item['mscert']['amount']) &&
            !empty($cart_item['data']) &&
            is_object($cart_item['data'])
        ) {
            $cart_item['data']->set_price(
                (float) $cart_item['mscert']['amount']
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| ДАННЫЕ В КОРЗИНЕ
|--------------------------------------------------------------------------
*/

add_filter(
    'woocommerce_get_item_data',
    'mscert_cart_item_data',
    10,
    2
);

function mscert_cart_item_data($item_data, $cart_item) {
    if (empty($cart_item['mscert'])) {
        return $item_data;
    }

    $certificate = $cart_item['mscert'];

    $recipient = trim(
        ($certificate['first_name'] ?? '')
        . ' '
        . ($certificate['last_name'] ?? '')
    );

    $item_data[] = [
        'name'  => 'Получатель',
        'value' => esc_html($recipient),
    ];

    if (!empty($certificate['sender'])) {
        $item_data[] = [
            'name'  => 'От кого',
            'value' => esc_html($certificate['sender']),
        ];
    }

    $item_data[] = [
        'name'  => 'Номинал',
        'value' => esc_html(
            mscert_format_amount($certificate['amount'] ?? 0)
        ),
    ];

    if (!empty($certificate['date'])) {
        $item_data[] = [
            'name'  => 'Дата отправки',
            'value' => esc_html($certificate['date']),
        ];
    }

    if (!empty($certificate['message'])) {
        $item_data[] = [
            'name'  => 'Пожелание',
            'value' => esc_html($certificate['message']),
        ];
    }

    return $item_data;
}

add_filter(
    'woocommerce_cart_item_name',
    'mscert_cart_item_name',
    10,
    2
);

function mscert_cart_item_name($name, $cart_item) {
    if (empty($cart_item['mscert'])) {
        return $name;
    }

    $certificate = $cart_item['mscert'];

    $recipient = trim(
        ($certificate['first_name'] ?? '')
        . ' '
        . ($certificate['last_name'] ?? '')
    );

    if (!$recipient) {
        return 'Подарочный сертификат';
    }

    return 'Подарочный сертификат для ' . esc_html($recipient);
}

/*
|--------------------------------------------------------------------------
| СОХРАНЕНИЕ ДАННЫХ В ЗАКАЗ
|--------------------------------------------------------------------------
*/

add_action(
    'woocommerce_checkout_create_order_line_item',
    'mscert_save_order_line_item',
    10,
    4
);

function mscert_save_order_line_item(
    $item,
    $cart_item_key,
    $values,
    $order
) {
    if (empty($values['mscert'])) {
        return;
    }

    $certificate = $values['mscert'];

    $recipient = trim(
        ($certificate['first_name'] ?? '')
        . ' '
        . ($certificate['last_name'] ?? '')
    );

    $item->add_meta_data(
        'Получатель сертификата',
        $recipient,
        true
    );

    $item->add_meta_data(
        'Сумма сертификата',
        mscert_format_amount($certificate['amount'] ?? 0),
        true
    );

    if (!empty($certificate['sender'])) {
        $item->add_meta_data(
            'От кого',
            $certificate['sender'],
            true
        );
    }

    if (!empty($certificate['email'])) {
        $item->add_meta_data(
            'Email получателя',
            $certificate['email'],
            true
        );
    }

    if (!empty($certificate['date'])) {
        $item->add_meta_data(
            'Дата отправки',
            $certificate['date'],
            true
        );
    }

    if (!empty($certificate['message'])) {
        $item->add_meta_data(
            'Пожелание',
            $certificate['message'],
            true
        );
    }

    $hidden_meta = [
        '_mscert_unique_id'  => $certificate['unique_id'] ?? '',
        '_mscert_amount'     => $certificate['amount'] ?? 0,
        '_mscert_first_name' => $certificate['first_name'] ?? '',
        '_mscert_last_name'  => $certificate['last_name'] ?? '',
        '_mscert_sender'     => $certificate['sender'] ?? '',
        '_mscert_email'      => $certificate['email'] ?? '',
        '_mscert_date'       => $certificate['date'] ?? '',
        '_mscert_design'     => $certificate['design'] ?? 'gold',
        '_mscert_message'    => $certificate['message'] ?? '',
    ];

    foreach ($hidden_meta as $key => $value) {
        $item->add_meta_data($key, $value, true);
    }
}

/*
|--------------------------------------------------------------------------
| ГЕНЕРАЦИЯ СЕРТИФИКАТОВ ПОСЛЕ ОПЛАТЫ
|--------------------------------------------------------------------------
*/

add_action(
    'woocommerce_payment_complete',
    'mscert_generate_certificates_for_order',
    10,
    1
);

add_action(
    'woocommerce_order_status_processing',
    'mscert_generate_certificates_for_order',
    10,
    1
);

add_action(
    'woocommerce_order_status_completed',
    'mscert_generate_certificates_for_order',
    10,
    1
);

add_action(
    'woocommerce_order_status_on-hold',
    'mscert_generate_certificates_for_order',
    10,
    1
);

function mscert_generate_certificates_for_order($order_id) {
    $order_id = absint($order_id);
    $order    = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    if ($order->get_meta('_mscert_generated') === 'yes') {
        return;
    }

    $certificate_items = 0;
    $generated_items   = 0;

    foreach ($order->get_items() as $item_id => $item) {
        $unique_id = (string) $item->get_meta('_mscert_unique_id');

        if (!$unique_id) {
            continue;
        }

        $certificate_items++;

        $existing_cert_id = mscert_find_certificate_by_unique_id(
            $unique_id
        );

        if ($existing_cert_id) {
            $generated_items++;
            mscert_maybe_schedule_or_send($existing_cert_id);
            continue;
        }

        $amount = (float) $item->get_meta('_mscert_amount');

        $first_name = sanitize_text_field(
            $item->get_meta('_mscert_first_name')
        );

        $last_name = sanitize_text_field(
            $item->get_meta('_mscert_last_name')
        );

        $sender = sanitize_text_field(
            $item->get_meta('_mscert_sender')
        );

        $email = sanitize_email(
            $item->get_meta('_mscert_email')
        );

        $date = sanitize_text_field(
            $item->get_meta('_mscert_date')
        );

        $design = sanitize_key(
            $item->get_meta('_mscert_design') ?: 'gold'
        );

        $message = sanitize_textarea_field(
            $item->get_meta('_mscert_message')
        );

        if (!in_array($design, ['gold', 'dark', 'classic'], true)) {
            $design = 'gold';
        }

        $number = mscert_generate_number(
            $order_id,
            $item_id,
            $unique_id
        );

        $cert_id = wp_insert_post([
            'post_type'   => 'ms_certificate',
            'post_status' => 'publish',
            'post_title'  => $number
                . ' — '
                . trim($first_name . ' ' . $last_name),
        ]);

        if (!$cert_id || is_wp_error($cert_id)) {
            continue;
        }

        $token = wp_generate_password(24, false, false);

        $meta = [
            '_mscert_unique_id'    => $unique_id,
            '_mscert_view_token'   => $token,
            '_mscert_number'       => $number,
            '_mscert_status'       => 'active',
            '_mscert_amount'       => $amount,
            '_mscert_first_name'   => $first_name,
            '_mscert_last_name'    => $last_name,
            '_mscert_sender'       => $sender,
            '_mscert_email'        => $email,
            '_mscert_date'         => $date,
            '_mscert_design'       => $design,
            '_mscert_message'      => $message,
            '_mscert_order_id'     => $order_id,
            '_mscert_order_item_id'=> $item_id,
            '_mscert_created_at'   => current_time('mysql'),
            '_mscert_file_version' => 1,
        ];

        foreach ($meta as $key => $value) {
            update_post_meta($cert_id, $key, $value);
        }

        $file_data = mscert_generate_certificate_file($cert_id);

        if (!mscert_file_data_is_valid($file_data)) {
            continue;
        }

        mscert_save_generated_file_data(
            $cert_id,
            $file_data
        );

        mscert_maybe_schedule_or_send($cert_id);

        $generated_items++;
    }

    if (
        $certificate_items > 0 &&
        $generated_items === $certificate_items
    ) {
        $order->update_meta_data('_mscert_generated', 'yes');
        $order->save();
    }
}

/*
|--------------------------------------------------------------------------
| ПОИСК И НОМЕР СЕРТИФИКАТА
|--------------------------------------------------------------------------
*/

function mscert_generate_number($order_id, $item_id, $unique_id) {
    $hash = strtoupper(
        substr(
            md5($order_id . '-' . $item_id . '-' . $unique_id),
            0,
            6
        )
    );

    return 'MS-' . date_i18n('ym') . '-' . $hash;
}

function mscert_find_certificate_by_unique_id($unique_id) {
    $posts = get_posts([
        'post_type'      => 'ms_certificate',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'meta_key'       => '_mscert_unique_id',
        'meta_value'     => $unique_id,
        'fields'         => 'ids',
    ]);

    return !empty($posts) ? (int) $posts[0] : 0;
}

function mscert_find_certificate_by_number($number) {
    $posts = get_posts([
        'post_type'      => 'ms_certificate',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'meta_key'       => '_mscert_number',
        'meta_value'     => $number,
        'fields'         => 'ids',
    ]);

    return !empty($posts) ? (int) $posts[0] : 0;
}

/*
|--------------------------------------------------------------------------
| DATA URI
|--------------------------------------------------------------------------
*/

function mscert_file_data_uri($relative_path) {
    $relative_path = ltrim((string) $relative_path, '/');
    $path          = MSCERT_PATH . $relative_path;

    if (!is_file($path) || !is_readable($path)) {
        return '';
    }

    $contents = file_get_contents($path);

    if ($contents === false || $contents === '') {
        return '';
    }

    $extension = strtolower(
        pathinfo($path, PATHINFO_EXTENSION)
    );

    $types = [
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    $mime = $types[$extension]
        ?? 'application/octet-stream';

    if (function_exists('mime_content_type')) {
        $detected = mime_content_type($path);

        if (
            $detected &&
            $detected !== 'application/octet-stream'
        ) {
            $mime = $detected;
        }
    }

    return 'data:'
        . $mime
        . ';base64,'
        . base64_encode($contents);
}

/*
|--------------------------------------------------------------------------
| HTML PDF-СЕРТИФИКАТА
|--------------------------------------------------------------------------
*/

function mscert_render_certificate_html($cert_id) {
    $cert_id = absint($cert_id);

    if (!$cert_id) {
        return '';
    }

    $design = sanitize_key(
        get_post_meta(
            $cert_id,
            '_mscert_design',
            true
        ) ?: 'gold'
    );

    if (!in_array($design, ['gold', 'dark', 'classic'], true)) {
        $design = 'gold';
    }

    $data = [
        'number' => sanitize_text_field(
            get_post_meta(
                $cert_id,
                '_mscert_number',
                true
            )
        ),

        'amount' => (float) get_post_meta(
            $cert_id,
            '_mscert_amount',
            true
        ),

        'first_name' => sanitize_text_field(
            get_post_meta(
                $cert_id,
                '_mscert_first_name',
                true
            )
        ),

        'last_name' => sanitize_text_field(
            get_post_meta(
                $cert_id,
                '_mscert_last_name',
                true
            )
        ),

        'sender' => sanitize_text_field(
            get_post_meta(
                $cert_id,
                '_mscert_sender',
                true
            )
        ),

        'message' => sanitize_textarea_field(
            get_post_meta(
                $cert_id,
                '_mscert_message',
                true
            )
        ),

        'design' => $design,
    ];

    $template = mscert_pdf_template_path();

    if (!is_file($template) || !is_readable($template)) {
        update_post_meta(
            $cert_id,
            '_mscert_last_error',
            'Не найден шаблон сертификата: ' . $template
        );

        return '';
    }

    ob_start();

    include $template;

    return (string) ob_get_clean();
}

function mscert_render_certificate_html_fallback($cert_id) {
    return mscert_render_certificate_html($cert_id);
}

function mscert_prepare_html_for_pdf($html) {
    if (!is_string($html) || $html === '') {
        return '';
    }

    if (
        function_exists('mb_check_encoding') &&
        !mb_check_encoding($html, 'UTF-8') &&
        function_exists('mb_convert_encoding')
    ) {
        $html = mb_convert_encoding(
            $html,
            'UTF-8',
            'auto'
        );
    }

    return $html;
}

/*
|--------------------------------------------------------------------------
| ПРОВЕРКА И СОХРАНЕНИЕ ДАННЫХ ФАЙЛА
|--------------------------------------------------------------------------
*/

function mscert_file_data_is_valid($file_data) {
    return (
        is_array($file_data) &&
        !empty($file_data['path']) &&
        is_file($file_data['path']) &&
        filesize($file_data['path']) > 100 &&
        !empty($file_data['url']) &&
        !empty($file_data['type'])
    );
}

function mscert_save_generated_file_data(
    $cert_id,
    $file_data
) {
    if (!mscert_file_data_is_valid($file_data)) {
        return false;
    }

    update_post_meta(
        $cert_id,
        '_mscert_file_path',
        $file_data['path']
    );

    update_post_meta(
        $cert_id,
        '_mscert_file_url',
        $file_data['url']
    );

    update_post_meta(
        $cert_id,
        '_mscert_file_type',
        $file_data['type']
    );

    if (!empty($file_data['version'])) {
        update_post_meta(
            $cert_id,
            '_mscert_file_version',
            absint($file_data['version'])
        );
    }

    return true;
}

/*
|--------------------------------------------------------------------------
| ГЕНЕРАЦИЯ PDF / HTML
|--------------------------------------------------------------------------
*/

function mscert_generate_certificate_file($cert_id) {
    $cert_id = absint($cert_id);

    $empty_result = [
        'path'       => '',
        'url'        => '',
        'direct_url' => '',
        'type'       => '',
        'version'    => 0,
    ];

    if (!$cert_id) {
        return $empty_result;
    }

    delete_post_meta($cert_id, '_mscert_last_error');

    $upload = wp_upload_dir();

    if (!empty($upload['error'])) {
        update_post_meta(
            $cert_id,
            '_mscert_last_error',
            'Ошибка WordPress Uploads: '
            . $upload['error']
        );

        return $empty_result;
    }

    $dir = trailingslashit($upload['basedir'])
        . 'medi-siam-certificates/';

    $base_url = trailingslashit($upload['baseurl'])
        . 'medi-siam-certificates/';

    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }

    if (!is_dir($dir)) {
        update_post_meta(
            $cert_id,
            '_mscert_last_error',
            'Не удалось создать папку: ' . $dir
        );

        return $empty_result;
    }

    if (!is_writable($dir)) {
        update_post_meta(
            $cert_id,
            '_mscert_last_error',
            'Папка недоступна для записи: ' . $dir
        );

        return $empty_result;
    }

    $template = mscert_pdf_template_path();

    if (!is_file($template) || !is_readable($template)) {
        update_post_meta(
            $cert_id,
            '_mscert_last_error',
            'Не найден шаблон сертификата: '
            . $template
        );

        return $empty_result;
    }

    $number = sanitize_text_field(
        get_post_meta(
            $cert_id,
            '_mscert_number',
            true
        )
    );

    if (!$number) {
        $number = 'MS-'
            . date_i18n('ym')
            . '-'
            . strtoupper(
                substr(
                    md5(
                        $cert_id
                        . '-'
                        . microtime(true)
                    ),
                    0,
                    6
                )
            );

        update_post_meta(
            $cert_id,
            '_mscert_number',
            $number
        );
    }

    $safe_number = sanitize_file_name($number);

    $version = absint(
        get_post_meta(
            $cert_id,
            '_mscert_file_version',
            true
        )
    );

    if ($version < 1) {
        $version = 1;

        update_post_meta(
            $cert_id,
            '_mscert_file_version',
            $version
        );
    }

    $file_slug = $safe_number . '-v' . $version;

    $html = mscert_render_certificate_html($cert_id);

    if (!is_string($html) || trim($html) === '') {
        update_post_meta(
            $cert_id,
            '_mscert_last_error',
            'Шаблон сертификата вернул пустой HTML. Проверьте assets/templates/certificate-pdf.php.'
        );

        $empty_result['version'] = $version;

        return $empty_result;
    }

    $html = mscert_prepare_html_for_pdf($html);

    $token = (string) get_post_meta(
        $cert_id,
        '_mscert_view_token',
        true
    );

    if (!$token) {
        $token = wp_generate_password(24, false, false);

        update_post_meta(
            $cert_id,
            '_mscert_view_token',
            $token
        );
    }

    $public_url = add_query_arg(
        [
            'mscert_view' => $number,
            'token'       => $token,
            'v'           => $version,
        ],
        home_url('/')
    );

    if (
        class_exists('\Dompdf\Dompdf') &&
        class_exists('\Dompdf\Options')
    ) {
        $pdf_path = $dir . $file_slug . '.pdf';

        try {
            $cache_dir = trailingslashit(
                $upload['basedir']
            ) . 'dompdf-cache/';

            $temp_dir = trailingslashit(
                $upload['basedir']
            ) . 'dompdf-temp/';

            if (!is_dir($cache_dir)) {
                wp_mkdir_p($cache_dir);
            }

            if (!is_dir($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }

            $font_dir = MSCERT_PATH
                . 'vendor/dompdf/dompdf/lib/fonts/';

            if (!is_dir($font_dir)) {
                $font_dir = $cache_dir;
            }

            $options = new \Dompdf\Options();

            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isFontSubsettingEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('dpi', 96);
            $options->set('fontDir', $font_dir);
            $options->set('fontCache', $cache_dir);
            $options->set('tempDir', $temp_dir);
            $options->set('chroot', ABSPATH);

            $dompdf = new \Dompdf\Dompdf($options);

            $dompdf->loadHtml($html, 'UTF-8');

            $dompdf->setPaper([
                0,
                0,
                595.28,
                280.63,
            ]);

            $dompdf->render();

            $pdf_output = $dompdf->output();

            if (
                !is_string($pdf_output) ||
                strlen($pdf_output) < 1000
            ) {
                throw new RuntimeException(
                    'Dompdf вернул пустой или слишком маленький PDF.'
                );
            }

            if (is_file($pdf_path)) {
                @unlink($pdf_path);
            }

            $written = file_put_contents(
                $pdf_path,
                $pdf_output,
                LOCK_EX
            );

            clearstatcache(true, $pdf_path);

            $actual_size = is_file($pdf_path)
                ? (int) filesize($pdf_path)
                : 0;

            if (
                $written === false ||
                $written < 1000 ||
                $actual_size < 1000
            ) {
                if (is_file($pdf_path)) {
                    @unlink($pdf_path);
                }

                throw new RuntimeException(
                    'Не удалось записать PDF. Записано: '
                    . (int) $written
                    . ' Б; размер файла: '
                    . $actual_size
                    . ' Б.'
                );
            }

            @chmod($pdf_path, 0644);

            return [
                'path'       => $pdf_path,
                'url'        => $public_url,
                'direct_url' => $base_url
                    . $file_slug
                    . '.pdf',
                'type'       => 'pdf',
                'version'    => $version,
            ];
        } catch (\Throwable $error) {
            update_post_meta(
                $cert_id,
                '_mscert_last_error',
                'Ошибка PDF: '
                . $error->getMessage()
            );

            if (is_file($pdf_path)) {
                @unlink($pdf_path);
            }
        }
    }

    $html_path = $dir . $file_slug . '.html';

    if (is_file($html_path)) {
        @unlink($html_path);
    }

    $html_written = file_put_contents(
        $html_path,
        $html,
        LOCK_EX
    );

    clearstatcache(true, $html_path);

    $html_size = is_file($html_path)
        ? (int) filesize($html_path)
        : 0;

    if (
        $html_written === false ||
        $html_written < 100 ||
        $html_size < 100
    ) {
        if (is_file($html_path)) {
            @unlink($html_path);
        }

        $previous_error = (string) get_post_meta(
            $cert_id,
            '_mscert_last_error',
            true
        );

        $write_error = 'Не удалось записать HTML-файл. '
            . 'Записано: '
            . (int) $html_written
            . ' Б; размер: '
            . $html_size
            . ' Б.';

        update_post_meta(
            $cert_id,
            '_mscert_last_error',
            trim($previous_error . ' ' . $write_error)
        );

        $empty_result['version'] = $version;

        return $empty_result;
    }

    @chmod($html_path, 0644);

    return [
        'path'       => $html_path,
        'url'        => $public_url,
        'direct_url' => $base_url
            . $file_slug
            . '.html',
        'type'       => 'html',
        'version'    => $version,
    ];
}

/*
|--------------------------------------------------------------------------
| ПУБЛИЧНЫЙ ПРОСМОТР
|--------------------------------------------------------------------------
*/

add_action(
    'template_redirect',
    'mscert_public_certificate_view'
);

function mscert_public_certificate_view() {
    if (empty($_GET['mscert_view'])) {
        return;
    }

    $number = sanitize_text_field(
        wp_unslash($_GET['mscert_view'])
    );

    $token = isset($_GET['token'])
        ? sanitize_text_field(
            wp_unslash($_GET['token'])
        )
        : '';

    if (!$number || !$token) {
        status_header(403);
        wp_die('Доступ запрещён.');
    }

    $cert_id = mscert_find_certificate_by_number(
        $number
    );

    if (!$cert_id) {
        status_header(404);
        wp_die('Сертификат не найден.');
    }

    $saved_token = (string) get_post_meta(
        $cert_id,
        '_mscert_view_token',
        true
    );

    if (
        !$saved_token ||
        !hash_equals($saved_token, $token)
    ) {
        status_header(403);
        wp_die('Неверная ссылка сертификата.');
    }

    $file_path = (string) get_post_meta(
        $cert_id,
        '_mscert_file_path',
        true
    );

    $file_type = (string) get_post_meta(
        $cert_id,
        '_mscert_file_type',
        true
    );

    if (
        !$file_path ||
        !is_file($file_path) ||
        filesize($file_path) < 100
    ) {
        $file_data = mscert_generate_certificate_file(
            $cert_id
        );

        if (mscert_file_data_is_valid($file_data)) {
            mscert_save_generated_file_data(
                $cert_id,
                $file_data
            );

            $file_path = $file_data['path'];
            $file_type = $file_data['type'];
        }
    }

    if (
        !$file_path ||
        !is_file($file_path) ||
        filesize($file_path) < 100
    ) {
        status_header(404);

        $last_error = get_post_meta(
            $cert_id,
            '_mscert_last_error',
            true
        );

        if (
            current_user_can('manage_options') &&
            $last_error
        ) {
            wp_die(
                'Файл сертификата не найден.<br><br>'
                . esc_html($last_error)
            );
        }

        wp_die('Файл сертификата не найден.');
    }

    nocache_headers();

    if ($file_type === 'pdf') {
        header('Content-Type: application/pdf');
        header(
            'Content-Disposition: inline; filename="'
            . basename($file_path)
            . '"'
        );
        header(
            'Content-Length: '
            . filesize($file_path)
        );

        readfile($file_path);
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');

    readfile($file_path);
    exit;
}

/*
|--------------------------------------------------------------------------
| EMAIL-ДОСТАВКА
|--------------------------------------------------------------------------
*/

function mscert_maybe_schedule_or_send($cert_id) {
    $cert_id = absint($cert_id);

    if (!$cert_id) {
        return;
    }

    if (
        get_post_meta(
            $cert_id,
            '_mscert_email_sent',
            true
        ) === 'yes'
    ) {
        return;
    }

    $date = (string) get_post_meta(
        $cert_id,
        '_mscert_date',
        true
    );

    if ($date) {
        try {
            $timezone = wp_timezone();

            $date_time = new DateTime(
                $date . ' 10:00:00',
                $timezone
            );

            $timestamp = $date_time->getTimestamp();

            if ($timestamp > time()) {
                $scheduled = wp_next_scheduled(
                    'mscert_send_scheduled_email',
                    [$cert_id]
                );

                if (!$scheduled) {
                    wp_schedule_single_event(
                        $timestamp,
                        'mscert_send_scheduled_email',
                        [$cert_id]
                    );
                }

                update_post_meta(
                    $cert_id,
                    '_mscert_email_scheduled',
                    'yes'
                );

                return;
            }
        } catch (\Throwable $error) {
            // Если дата некорректна — отправляем сразу.
        }
    }

    mscert_send_certificate_email($cert_id);
}

add_action(
    'mscert_send_scheduled_email',
    'mscert_send_certificate_email'
);

function mscert_send_certificate_email($cert_id) {
    $cert_id = absint($cert_id);

    if (!$cert_id) {
        return false;
    }

    if (
        get_post_meta(
            $cert_id,
            '_mscert_email_sent',
            true
        ) === 'yes'
    ) {
        return true;
    }

    $order_id = absint(
        get_post_meta(
            $cert_id,
            '_mscert_order_id',
            true
        )
    );

    $order = $order_id
        ? wc_get_order($order_id)
        : false;

    $recipient_email = sanitize_email(
        get_post_meta(
            $cert_id,
            '_mscert_email',
            true
        )
    );

    $buyer_email = $order
        ? sanitize_email($order->get_billing_email())
        : '';

    $to = $recipient_email ?: $buyer_email;

    if (!$to || !is_email($to)) {
        update_post_meta(
            $cert_id,
            '_mscert_last_email_error',
            'Не указан корректный email.'
        );

        return false;
    }

    $number = sanitize_text_field(
        get_post_meta(
            $cert_id,
            '_mscert_number',
            true
        )
    );

    $file_path = (string) get_post_meta(
        $cert_id,
        '_mscert_file_path',
        true
    );

    $file_url = (string) get_post_meta(
        $cert_id,
        '_mscert_file_url',
        true
    );

    if (
        !$file_path ||
        !is_file($file_path) ||
        filesize($file_path) < 100
    ) {
        $file_data = mscert_generate_certificate_file(
            $cert_id
        );

        if (mscert_file_data_is_valid($file_data)) {
            mscert_save_generated_file_data(
                $cert_id,
                $file_data
            );

            $file_path = $file_data['path'];
            $file_url  = $file_data['url'];
        }
    }

    if (!$file_url) {
        update_post_meta(
            $cert_id,
            '_mscert_last_email_error',
            'Не создана ссылка сертификата.'
        );

        return false;
    }

    $subject = 'Ваш подарочный сертификат Medi Siam — '
        . $number;

    $message = '
    <div style="font-family:Arial,sans-serif;background:#f6efe4;padding:32px;">
        <div style="max-width:640px;margin:0 auto;background:#fffaf1;border:1px solid #d8bd83;padding:32px;">
            <h1 style="margin:0 0 12px;color:#3a2212;">Подарочный сертификат Medi Siam</h1>
            <p style="font-size:16px;color:#5a4333;">Ваш сертификат готов.</p>
            <p><strong>Номер:</strong> '
            . esc_html($number)
            . '</p>
            <p><a href="'
            . esc_url($file_url)
            . '" style="display:inline-block;padding:14px 24px;background:#4a2528;color:#fff;text-decoration:none;">Открыть сертификат</a></p>
        </div>
    </div>';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
    ];

    $attachments = [];

    if (
        $file_path &&
        is_file($file_path) &&
        filesize($file_path) > 100
    ) {
        $attachments[] = $file_path;
    }

    $sent = wp_mail(
        $to,
        $subject,
        $message,
        $headers,
        $attachments
    );

    if (!$sent) {
        update_post_meta(
            $cert_id,
            '_mscert_last_email_error',
            'WordPress не смог отправить письмо.'
        );

        return false;
    }

    if (
        $buyer_email &&
        $buyer_email !== $to &&
        is_email($buyer_email)
    ) {
        wp_mail(
            $buyer_email,
            'Копия подарочного сертификата — '
            . $number,
            $message,
            $headers,
            $attachments
        );
    }

    delete_post_meta(
        $cert_id,
        '_mscert_last_email_error'
    );

    update_post_meta(
        $cert_id,
        '_mscert_email_sent',
        'yes'
    );

    update_post_meta(
        $cert_id,
        '_mscert_email_sent_at',
        current_time('mysql')
    );

    return true;
}

/*
|--------------------------------------------------------------------------
| АДМИН-КОЛОНКИ
|--------------------------------------------------------------------------
*/

add_filter(
    'manage_ms_certificate_posts_columns',
    'mscert_admin_columns'
);

function mscert_admin_columns($columns) {
    return [
        'cb'               => '<input type="checkbox" />',
        'title'            => 'Сертификат',
        'mscert_number'    => 'Номер',
        'mscert_recipient' => 'Получатель',
        'mscert_amount'    => 'Сумма',
        'mscert_status'    => 'Статус',
        'mscert_order'     => 'Заказ',
        'mscert_file'      => 'Файл',
        'date'             => 'Дата',
    ];
}

add_action(
    'manage_ms_certificate_posts_custom_column',
    'mscert_admin_column_content',
    10,
    2
);

function mscert_admin_column_content($column, $post_id) {
    if ($column === 'mscert_number') {
        echo esc_html(
            get_post_meta(
                $post_id,
                '_mscert_number',
                true
            )
        );
    }

    if ($column === 'mscert_recipient') {
        echo esc_html(
            trim(
                get_post_meta(
                    $post_id,
                    '_mscert_first_name',
                    true
                )
                . ' '
                . get_post_meta(
                    $post_id,
                    '_mscert_last_name',
                    true
                )
            )
        );
    }

    if ($column === 'mscert_amount') {
        echo esc_html(
            mscert_format_amount(
                get_post_meta(
                    $post_id,
                    '_mscert_amount',
                    true
                )
            )
        );
    }

    if ($column === 'mscert_status') {
        $status = get_post_meta(
            $post_id,
            '_mscert_status',
            true
        ) ?: 'active';

        if ($status === 'redeemed') {
            echo '<span style="color:#9b1c1c;font-weight:700;">Погашен</span>';
        } else {
            echo '<span style="color:#137333;font-weight:700;">Активен</span>';
        }
    }

    if ($column === 'mscert_order') {
        $order_id = absint(
            get_post_meta(
                $post_id,
                '_mscert_order_id',
                true
            )
        );

        if ($order_id) {
            echo '<a href="'
                . esc_url(
                    admin_url(
                        'post.php?post='
                        . $order_id
                        . '&action=edit'
                    )
                )
                . '">#'
                . esc_html($order_id)
                . '</a>';
        }
    }

    if ($column === 'mscert_file') {
        $url = get_post_meta(
            $post_id,
            '_mscert_file_url',
            true
        );

        if ($url) {
            echo '<a href="'
                . esc_url($url)
                . '" target="_blank" rel="noopener">Открыть</a>';
        } else {
            echo '—';
        }
    }
}

/*
|--------------------------------------------------------------------------
| МЕТАБОКС СЕРТИФИКАТА
|--------------------------------------------------------------------------
*/

add_action('add_meta_boxes', function () {
    add_meta_box(
        'mscert_details',
        'Данные сертификата',
        'mscert_render_metabox',
        'ms_certificate',
        'normal',
        'high'
    );
});

function mscert_render_metabox($post) {
    $number = get_post_meta(
        $post->ID,
        '_mscert_number',
        true
    );

    $status = get_post_meta(
        $post->ID,
        '_mscert_status',
        true
    ) ?: 'active';

    $amount = (float) get_post_meta(
        $post->ID,
        '_mscert_amount',
        true
    );

    $file_url = get_post_meta(
        $post->ID,
        '_mscert_file_url',
        true
    );

    $file_path = get_post_meta(
        $post->ID,
        '_mscert_file_path',
        true
    );

    $file_type = get_post_meta(
        $post->ID,
        '_mscert_file_type',
        true
    );

    $file_version = absint(
        get_post_meta(
            $post->ID,
            '_mscert_file_version',
            true
        )
    );

    $last_regenerated = get_post_meta(
        $post->ID,
        '_mscert_last_regenerated_at',
        true
    );

    $last_error = get_post_meta(
        $post->ID,
        '_mscert_last_error',
        true
    );

    $last_email_error = get_post_meta(
        $post->ID,
        '_mscert_last_email_error',
        true
    );

    $redeemed_at = get_post_meta(
        $post->ID,
        '_mscert_redeemed_at',
        true
    );
    ?>
    <div class="mscert-admin-box">
        <p>
            <strong>Номер:</strong>
            <?php echo esc_html($number); ?>
        </p>

        <p>
            <strong>Статус:</strong>
            <?php
            echo esc_html(
                $status === 'redeemed'
                    ? 'Погашен'
                    : 'Активен'
            );
            ?>
        </p>

        <p>
            <strong>Сумма:</strong>
            <?php echo esc_html(mscert_format_amount($amount)); ?>
        </p>

        <p>
            <strong>Версия файла:</strong>
            <?php echo esc_html($file_version ?: 1); ?>
        </p>

        <?php if ($last_regenerated): ?>
            <p>
                <strong>Последняя перегенерация:</strong>
                <?php echo esc_html($last_regenerated); ?>
            </p>
        <?php endif; ?>

        <?php if ($file_type): ?>
            <p>
                <strong>Тип файла:</strong>
                <?php echo esc_html($file_type); ?>
            </p>
        <?php endif; ?>

        <?php if ($file_path): ?>
            <p>
                <strong>Путь файла:</strong><br>
                <code style="display:block;white-space:normal;">
                    <?php echo esc_html($file_path); ?>
                </code>
            </p>

            <?php if (is_file($file_path)): ?>
                <p>
                    <strong>Размер файла:</strong>
                    <?php echo esc_html(size_format(filesize($file_path))); ?>
                </p>
            <?php else: ?>
                <p style="color:#b00020;">
                    <strong>Файл не найден по указанному пути.</strong>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($last_error): ?>
            <p style="color:#b00020;">
                <strong>Ошибка генерации:</strong><br>
                <code style="display:block;white-space:normal;">
                    <?php echo esc_html($last_error); ?>
                </code>
            </p>
        <?php endif; ?>

        <?php if ($last_email_error): ?>
            <p style="color:#b00020;">
                <strong>Ошибка отправки:</strong><br>
                <code style="display:block;white-space:normal;">
                    <?php echo esc_html($last_email_error); ?>
                </code>
            </p>
        <?php endif; ?>

        <?php if ($redeemed_at): ?>
            <p>
                <strong>Дата погашения:</strong>
                <?php echo esc_html($redeemed_at); ?>
            </p>
        <?php endif; ?>

        <?php if ($file_url): ?>
            <p>
                <a
                    class="button button-primary"
                    href="<?php echo esc_url($file_url); ?>"
                    target="_blank"
                    rel="noopener"
                >
                    Открыть сертификат
                </a>
            </p>
        <?php endif; ?>

        <p>
            <a
                class="button"
                href="<?php
                echo esc_url(
                    wp_nonce_url(
                        admin_url(
                            'admin-post.php?action=mscert_regenerate_file&cert_id='
                            . $post->ID
                        ),
                        'mscert_regenerate_file_'
                        . $post->ID
                    )
                );
                ?>"
            >
                Перегенерировать файл сертификата
            </a>
        </p>

        <?php if ($status !== 'redeemed'): ?>
            <p>
                <a
                    class="button"
                    href="<?php
                    echo esc_url(
                        wp_nonce_url(
                            admin_url(
                                'admin-post.php?action=mscert_redeem&cert_id='
                                . $post->ID
                            ),
                            'mscert_redeem_'
                            . $post->ID
                        )
                    );
                    ?>"
                >
                    Погасить сертификат
                </a>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/*
|--------------------------------------------------------------------------
| ПЕРЕГЕНЕРАЦИЯ
|--------------------------------------------------------------------------
*/

add_action(
    'admin_post_mscert_regenerate_file',
    'mscert_admin_regenerate_file'
);

function mscert_admin_regenerate_file() {
    if (!current_user_can('manage_options')) {
        wp_die('Недостаточно прав.');
    }

    $cert_id = isset($_GET['cert_id'])
        ? absint($_GET['cert_id'])
        : 0;

    $nonce = isset($_GET['_wpnonce'])
        ? sanitize_text_field(
            wp_unslash($_GET['_wpnonce'])
        )
        : '';

    if (
        !$cert_id ||
        !wp_verify_nonce(
            $nonce,
            'mscert_regenerate_file_' . $cert_id
        )
    ) {
        wp_die('Ошибка безопасности.');
    }

    delete_post_meta(
        $cert_id,
        '_mscert_last_error'
    );

    $current_version = absint(
        get_post_meta(
            $cert_id,
            '_mscert_file_version',
            true
        )
    );

    if ($current_version < 1) {
        $current_version = 1;
    }

    $new_version = $current_version + 1;

    update_post_meta(
        $cert_id,
        '_mscert_file_version',
        $new_version
    );

    $file_data = mscert_generate_certificate_file(
        $cert_id
    );

    if (mscert_file_data_is_valid($file_data)) {
        mscert_save_generated_file_data(
            $cert_id,
            $file_data
        );

        update_post_meta(
            $cert_id,
            '_mscert_last_regenerated_at',
            current_time('mysql')
        );
    }

    wp_safe_redirect(
        admin_url(
            'post.php?post='
            . $cert_id
            . '&action=edit&mscert_regenerated=1&v='
            . $new_version
        )
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ПОГАШЕНИЕ
|--------------------------------------------------------------------------
*/

add_action(
    'admin_post_mscert_redeem',
    'mscert_admin_redeem_certificate'
);

function mscert_admin_redeem_certificate() {
    if (!current_user_can('manage_options')) {
        wp_die('Недостаточно прав.');
    }

    $cert_id = isset($_GET['cert_id'])
        ? absint($_GET['cert_id'])
        : 0;

    $nonce = isset($_GET['_wpnonce'])
        ? sanitize_text_field(
            wp_unslash($_GET['_wpnonce'])
        )
        : '';

    if (
        !$cert_id ||
        !wp_verify_nonce(
            $nonce,
            'mscert_redeem_' . $cert_id
        )
    ) {
        wp_die('Ошибка безопасности.');
    }

    update_post_meta(
        $cert_id,
        '_mscert_status',
        'redeemed'
    );

    update_post_meta(
        $cert_id,
        '_mscert_redeemed_at',
        current_time('mysql')
    );

    update_post_meta(
        $cert_id,
        '_mscert_redeemed_by',
        get_current_user_id()
    );

    wp_safe_redirect(
        admin_url(
            'post.php?post='
            . $cert_id
            . '&action=edit'
        )
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| СТРАНИЦА ПРОВЕРКИ
|--------------------------------------------------------------------------
*/

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=ms_certificate',
        'Проверить сертификат',
        'Проверить сертификат',
        'manage_options',
        'mscert-check',
        'mscert_render_check_page'
    );
});

function mscert_render_check_page() {
    $number = isset($_GET['mscert_number'])
        ? sanitize_text_field(
            wp_unslash($_GET['mscert_number'])
        )
        : '';

    $cert_id = $number
        ? mscert_find_certificate_by_number($number)
        : 0;
    ?>
    <div class="wrap">
        <h1>Проверить сертификат</h1>

        <form method="get" action="">
            <input
                type="hidden"
                name="post_type"
                value="ms_certificate"
            >

            <input
                type="hidden"
                name="page"
                value="mscert-check"
            >

            <p>
                <input
                    type="text"
                    name="mscert_number"
                    value="<?php echo esc_attr($number); ?>"
                    class="regular-text"
                    placeholder="Например: MS-2610-ABC123"
                >

                <button class="button button-primary">
                    Проверить
                </button>
            </p>
        </form>

        <?php if ($number): ?>
            <hr>

            <?php if (!$cert_id): ?>
                <div class="notice notice-error">
                    <p>Сертификат не найден.</p>
                </div>
            <?php else: ?>
                <?php
                $status = get_post_meta(
                    $cert_id,
                    '_mscert_status',
                    true
                ) ?: 'active';

                $amount = (float) get_post_meta(
                    $cert_id,
                    '_mscert_amount',
                    true
                );

                $recipient = trim(
                    get_post_meta(
                        $cert_id,
                        '_mscert_first_name',
                        true
                    )
                    . ' '
                    . get_post_meta(
                        $cert_id,
                        '_mscert_last_name',
                        true
                    )
                );
                ?>

                <div
                    class="card"
                    style="max-width:680px;padding:20px;"
                >
                    <h2>
                        <?php echo esc_html(get_the_title($cert_id)); ?>
                    </h2>

                    <p>
                        <strong>Номер:</strong>
                        <?php echo esc_html($number); ?>
                    </p>

                    <p>
                        <strong>Получатель:</strong>
                        <?php echo esc_html($recipient); ?>
                    </p>

                    <p>
                        <strong>Сумма:</strong>
                        <?php echo esc_html(mscert_format_amount($amount)); ?>
                    </p>

                    <p>
                        <strong>Статус:</strong>

                        <?php if ($status === 'redeemed'): ?>
                            <span style="color:#9b1c1c;font-weight:700;">
                                Погашен
                            </span>
                        <?php else: ?>
                            <span style="color:#137333;font-weight:700;">
                                Активен
                            </span>
                        <?php endif; ?>
                    </p>

                    <?php if ($status !== 'redeemed'): ?>
                        <p>
                            <a
                                class="button button-primary"
                                href="<?php
                                echo esc_url(
                                    wp_nonce_url(
                                        admin_url(
                                            'admin-post.php?action=mscert_redeem&cert_id='
                                            . $cert_id
                                        ),
                                        'mscert_redeem_'
                                        . $cert_id
                                    )
                                );
                                ?>"
                            >
                                Погасить сертификат
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/*
|--------------------------------------------------------------------------
| CHECKOUT
|--------------------------------------------------------------------------
*/

function mscert_cart_has_certificate() {
    if (
        !function_exists('WC') ||
        !WC()->cart
    ) {
        return false;
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (!empty($cart_item['mscert'])) {
            return true;
        }
    }

    return false;
}

add_filter(
    'woocommerce_checkout_fields',
    'mscert_customize_checkout_fields',
    30
);

function mscert_customize_checkout_fields($fields) {
    if (!mscert_cart_has_certificate()) {
        return $fields;
    }

    $remove_billing = [
        'billing_country',
        'billing_state',
        'billing_city',
        'billing_postcode',
    ];

    foreach ($remove_billing as $field) {
        unset($fields['billing'][$field]);
    }

    $remove_shipping = [
        'shipping_country',
        'shipping_state',
        'shipping_city',
        'shipping_postcode',
    ];

    foreach ($remove_shipping as $field) {
        unset($fields['shipping'][$field]);
    }

    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['label'] = 'Ваше имя';
    }

    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['label'] = 'Ваша фамилия';
    }

    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = 'Ваш телефон';
        $fields['billing']['billing_phone']['placeholder'] = '+7 ___ ___ __ __';
    }

    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['label'] = 'Ваш email';
    }

    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] =
            'Адрес доставки по Караганде';

        $fields['billing']['billing_address_1']['placeholder'] =
            'Улица, дом, квартира или офис';

        $fields['billing']['billing_address_1']['required'] = false;
        $fields['billing']['billing_address_1']['priority'] = 75;
    }

    if (isset($fields['billing']['billing_address_2'])) {
        $fields['billing']['billing_address_2']['label'] =
            'Комментарий к адресу';

        $fields['billing']['billing_address_2']['placeholder'] =
            'Подъезд, этаж, ориентир';

        $fields['billing']['billing_address_2']['required'] = false;
        $fields['billing']['billing_address_2']['priority'] = 76;
    }

    $fields['billing']['mscert_delivery_type'] = [
        'type'     => 'radio',
        'label'    => 'Как получить сертификат?',
        'required' => true,
        'class'    => [
            'form-row-wide',
            'mscert-checkout-delivery-type',
        ],
        'priority' => 74,
        'options'  => [
            'email' =>
                'Получить электронно на email',

            'karaganda' =>
                'Доставка печатного сертификата по Караганде',
        ],
        'default' => 'email',
    ];

    return $fields;
}

add_filter(
    'woocommerce_checkout_posted_data',
    'mscert_checkout_posted_data'
);

function mscert_checkout_posted_data($data) {
    if (!mscert_cart_has_certificate()) {
        return $data;
    }

    $data['billing_country']  = 'KZ';
    $data['billing_state']    = '';
    $data['billing_city']     = 'Караганда';
    $data['billing_postcode'] = '';

    $data['shipping_country']  = 'KZ';
    $data['shipping_state']    = '';
    $data['shipping_city']     = 'Караганда';
    $data['shipping_postcode'] = '';

    return $data;
}

add_action(
    'woocommerce_after_checkout_validation',
    'mscert_validate_checkout_delivery',
    20,
    2
);

function mscert_validate_checkout_delivery(
    $data,
    $errors
) {
    if (!mscert_cart_has_certificate()) {
        return;
    }

    $delivery_type = isset($_POST['mscert_delivery_type'])
        ? sanitize_key(
            wp_unslash($_POST['mscert_delivery_type'])
        )
        : 'email';

    if (
        !in_array(
            $delivery_type,
            ['email', 'karaganda'],
            true
        )
    ) {
        $errors->add(
            'mscert_delivery_type_invalid',
            'Выберите способ получения сертификата.'
        );

        return;
    }

    if ($delivery_type === 'karaganda') {
        $address = isset($_POST['billing_address_1'])
            ? trim(
                sanitize_text_field(
                    wp_unslash(
                        $_POST['billing_address_1']
                    )
                )
            )
            : '';

        if (!$address) {
            $errors->add(
                'mscert_karaganda_address_required',
                'Для доставки печатного сертификата укажите адрес по Караганде.'
            );
        }
    }
}

add_action(
    'woocommerce_checkout_create_order',
    'mscert_save_delivery_type',
    20,
    2
);

function mscert_save_delivery_type(
    $order,
    $data
) {
    if (!mscert_cart_has_certificate()) {
        return;
    }

    $delivery_type = isset($_POST['mscert_delivery_type'])
        ? sanitize_key(
            wp_unslash($_POST['mscert_delivery_type'])
        )
        : 'email';

    if (
        !in_array(
            $delivery_type,
            ['email', 'karaganda'],
            true
        )
    ) {
        $delivery_type = 'email';
    }

    $order->update_meta_data(
        '_mscert_delivery_type',
        $delivery_type
    );

    $delivery_label = $delivery_type === 'karaganda'
        ? 'Доставка печатного сертификата по Караганде'
        : 'Электронно на email';

    $order->update_meta_data(
        'Способ получения сертификата',
        $delivery_label
    );
}

/*
|--------------------------------------------------------------------------
| СКРЫТИЕ ТОВАРА ИЗ КАТАЛОГА
|--------------------------------------------------------------------------
*/

add_action(
    'pre_get_posts',
    'mscert_hide_product_from_catalog'
);

function mscert_hide_product_from_catalog($query) {
    if (
        is_admin() ||
        !$query->is_main_query()
    ) {
        return;
    }

    $product_id = (int) get_option(
        'mscert_product_id'
    );

    if (!$product_id) {
        return;
    }

    if (
        is_shop() ||
        is_product_taxonomy() ||
        is_search()
    ) {
        $excluded = (array) $query->get(
            'post__not_in'
        );

        $excluded[] = $product_id;

        $query->set(
            'post__not_in',
            array_unique($excluded)
        );
    }
}

/*
|--------------------------------------------------------------------------
| ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
|--------------------------------------------------------------------------
*/

function mscert_get_min_amount() {
    return (float) apply_filters(
        'mscert_min_amount',
        1000
    );
}

function mscert_get_max_amount() {
    return (float) apply_filters(
        'mscert_max_amount',
        1000000
    );
}

function mscert_normalize_amount($amount) {
    $amount = round((float) $amount, 2);

    return max(0, $amount);
}

function mscert_format_amount($amount) {
    return number_format(
        (float) $amount,
        0,
        '.',
        ' '
    ) . ' ₸';
}

/*
|--------------------------------------------------------------------------
| УВЕДОМЛЕНИЕ DOMPDF
|--------------------------------------------------------------------------
*/

add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen')
        ? get_current_screen()
        : null;

    if (
        !$screen ||
        (
            $screen->post_type !== 'ms_certificate' &&
            $screen->id !== 'edit-ms_certificate'
        )
    ) {
        return;
    }

    if (class_exists('\Dompdf\Dompdf')) {
        echo '<div class="notice notice-success"><p><strong>Medi Siam Certificate:</strong> Dompdf подключён, PDF доступен.</p></div>';
    } else {
        echo '<div class="notice notice-warning"><p><strong>Medi Siam Certificate:</strong> Dompdf не найден. Сертификаты будут сохраняться как HTML.</p></div>';
    }
});
