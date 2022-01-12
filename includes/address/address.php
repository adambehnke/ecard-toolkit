<?php
    define ('ADDRESS_URI',  ECARD_INCLUDES_URI . 'address/');
    define ('ADDRESS_PATH',  ECARD_INCLUDES_PATH . 'address/');

    include 'includes/functions.php';

    function enqueue_address_admin_scripts() {
        $post_type = get_post_type();
        $pages = array('shop_order');

        wp_enqueue_script('boxshop-address-scripts',  ADDRESS_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);
    }

    add_action('admin_enqueue_scripts', 'enqueue_address_admin_scripts');
