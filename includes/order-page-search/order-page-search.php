<?php
    define ('ORDER_PAGE_SEARCH_URI', ECARD_INCLUDES_URI . 'order-page-search/');
    define ('ORDER_PAGE_SEARCH_PATH', ECARD_INCLUDES_PATH . 'order-page-search/');

    include ORDER_PAGE_SEARCH_PATH . '/includes/functions.php';

    function enqueue_order_page_search_assets() {
        if (is_shop_order_page()) {
            wp_register_style('order-page-search', ORDER_PAGE_SEARCH_URI . 'css/styles.css', false, '5.5.1' . time() . mt_rand());
            wp_enqueue_style('order-page-search');
            wp_enqueue_script('order-page-search-scripts', ORDER_PAGE_SEARCH_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);
            wp_localize_script('order-page-search-scripts', 'orderPageSearch',
                array( 
                    'homeUrl' => home_url(),
                )
            );
        }
    }

    add_action('admin_enqueue_scripts', 'enqueue_order_page_search_assets');
