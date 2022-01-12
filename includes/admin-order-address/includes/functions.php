<?php
    function add_order_address_overlay($order) {
        if ($order->get_status() === 'auto-draft') {
            ob_start();
            include ADMIN_ORDER_ADDRESS_PATH . 'templates/overlay.php';
            include ADMIN_ORDER_ADDRESS_PATH . 'templates/inputs.php';
            $html = ob_get_clean();
    
            echo $html;
        }
    }

    add_action('woocommerce_admin_order_data_after_shipping_address', 'add_order_address_overlay');