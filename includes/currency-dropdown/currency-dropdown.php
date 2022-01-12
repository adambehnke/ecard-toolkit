<?php
    define ('CURRENCY_DROPDOWN_URI',  ECARD_INCLUDES_URI . 'currency-dropdown/');
    define ('CURRENCY_DROPDOWN_PATH',  ECARD_INCLUDES_PATH . 'currency-dropdown/');
    define ('CURRENCY_CAD_EXCHANGE_RATE', 1.3415);

    if (is_admin()) {
        include 'includes/ajax.php';
        include 'includes/functions.php';
    }

    function enqueue_currency_admin_scripts() {
        if (is_shop_order_page()) {
            wp_register_style('boxshop-admin-currency', CURRENCY_DROPDOWN_URI . 'css/styles.css', false, '5.5.1' . time() . mt_rand());
            wp_enqueue_style('boxshop-admin-currency');
            wp_enqueue_script('boxshop-admin-currency-scripts', CURRENCY_DROPDOWN_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);
        }
    }

    add_action('admin_enqueue_scripts', 'enqueue_currency_admin_scripts');
