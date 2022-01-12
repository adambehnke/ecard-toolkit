<?php 
    add_action( 'add_meta_boxes', 'add_order_page_search_box' );

    function add_order_page_search_box() {
        add_meta_box('order_page_search_field', __('Search Orders','woocommerce'), 'add_order_page_search_box_template', 'shop_order', 'side', 'core' );
    }

    function add_order_page_search_box_template() {
        global $post;

        ob_start();
        include ORDER_PAGE_SEARCH_PATH . 'templates/search-box.php';
        $html = ob_get_clean();

        echo $html;
    }