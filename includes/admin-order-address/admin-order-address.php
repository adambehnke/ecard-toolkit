<?php
    define ('ADMIN_ORDER_ADDRESS_URI',  ECARD_INCLUDES_URI . 'admin-order-address/');
    define ('ADMIN_ORDER_ADDRESS_PATH',  ECARD_INCLUDES_PATH . 'admin-order-address/');

    if (is_admin()) {
        include 'includes/ajax.php';
        include 'includes/functions.php';

        //require_module('address');
    }

    function enqueue_admin_order_address_scripts() {
        global $post;

        if (is_shop_order_page() && $post->post_status === 'auto-draft') {
            wp_register_style('boxshop-admin-order-address', ADMIN_ORDER_ADDRESS_URI .'css/styles.css', false, '5.5.1' . time() . mt_rand());
            wp_enqueue_style('boxshop-admin-order-address');
            wp_enqueue_script('boxshop-admin-order-address-scripts', ADMIN_ORDER_ADDRESS_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);
        }
    }

    add_action('admin_enqueue_scripts', 'enqueue_admin_order_address_scripts');
