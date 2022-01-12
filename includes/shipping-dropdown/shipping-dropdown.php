<?php
    define ('SHIPPING_DROPDOWN_URI',  ECARD_INCLUDES_URI . 'shipping-dropdown/');
    define ('SHIPPING_DROPDOWN_PATH',  ECARD_INCLUDES_PATH . 'shipping-dropdown/');

    if (is_admin()) {
        include 'includes/ajax.php';
        include 'includes/functions.php';

        add_action( 'plugins_loaded', 'shipping_dropdown_load_dependencies' );
        function shipping_dropdown_load_dependencies() {
          include_once(WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-session.php'); // Abstract for session implementations
          include_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-session-handler.php');    // WC Session class
          include_once(WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-cart-totals.php');
          include_once(WP_PLUGIN_DIR . '/woocommerce/includes/wc-cart-functions.php');
        }
    }

    function enqueue_shipping_admin_scripts() {
        if (is_shop_order_page()) {
            wp_register_style('boxshop-admin-shipping', SHIPPING_DROPDOWN_URI .'css/styles.css', false, '5.5.1' . time() . mt_rand());
            wp_enqueue_style('boxshop-admin-shipping');
            wp_enqueue_script('boxshop-admin-shipping-scripts', SHIPPING_DROPDOWN_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);
        }
    }

    add_action('admin_enqueue_scripts', 'enqueue_shipping_admin_scripts');
