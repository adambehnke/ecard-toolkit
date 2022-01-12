<?php
    add_action('wp_ajax_add_order_shipping_item_with_dropdown', 'add_order_shipping_item_with_dropdown');
    add_action('wp_ajax_nopriv_add_order_shipping_item_with_dropdown', 'add_order_shipping_item_with_dropdown');
    add_action('wp_ajax_process_added_shipping_data', 'process_added_shipping_data');
    add_action('wp_ajax_nopriv_process_added_shipping_data', 'process_added_shipping_data');
    add_action('wp_ajax_add_order_shipping_dropdown', 'add_order_shipping_dropdown');
    add_action('wp_ajax_nopriv_add_order_shipping_dropdown', 'add_order_shipping_dropdown');
    add_action('wp_ajax_save_address_for_draft_order', 'save_address_for_draft_order');
    add_action('wp_ajax_nopriv_save_address_for_draft_order', 'save_address_for_draft_order');
    add_action('wp_ajax_remove_shipping_items_from_order', 'remove_shipping_items_from_order');
    add_action('wp_ajax_nopriv_remove_shipping_items_from_order', 'remove_shipping_items_from_order');
    add_action('wp_ajax_remove_shipping_item_from_order', 'remove_shipping_item_from_order');
    add_action('wp_ajax_nopriv_remove_shipping_item_from_order', 'remove_shipping_item_from_order');
    
    function add_order_shipping_item_with_dropdown() {
        check_ajax_referer('order-item', 'security');
    
        if (!current_user_can('edit_shop_orders')) {
            wp_die(-1);
        }
    
        $response = array();
    
        try {
            $order_id   = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
            $order      = wc_get_order($order_id);
            //$order      = remove_order_shipping($order);
    
            if (!$order) {
                throw new Exception(__('Invalid order', 'woocommerce'));
            }

            //$tax_args = get_tax_args_from_post_address();

            if (isset($_POST['orderAddresses'])) {
                $order = save_order_addresses($order, $_POST['orderAddresses']);
            }

            $shipping = new WC_Order_Item_Shipping();
            $shipping->set_name(sprintf(__('Shipping', 'woocommerce')));
            $shipping->save();
    
            $order->add_item($shipping);
            $order->calculate_totals(false);
    
            ob_start();
            $_GET['shipping_select'] = true;
            include WP_PLUGIN_DIR .'/woocommerce/includes/admin/meta-boxes/views/html-order-items.php';
            $response['html'] = ob_get_clean();
        } catch (Exception $e) {
            wp_send_json_error(array('error' => $e->getMessage()));
        }

        wp_send_json_success($response);

        echo 1;
        wp_die();
    }

    function process_added_shipping_data() {
		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
        }

		$shipping_data = array();
		$option_id = filter_var($_POST['option_id'], FILTER_SANITIZE_NUMBER_INT);
		$option_name = filter_var($_POST['option_name'], FILTER_SANITIZE_STRING);
		$order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
        $order_item_id = filter_var($_POST['order_item_id'], FILTER_SANITIZE_NUMBER_INT);
        $error_message = get_error_message('shipping');

		if ($option_id !== false && $option_name !== false && $order_id !== false && $order_item_id !== false) {
            $tax_args = get_tax_args_from_post_address();

            if (isset($_POST['attributes'], $_POST['attributes']['data-method-id'])) {
                $method_id = filter_var($_POST['attributes']['data-method-id'], FILTER_SANITIZE_STRING);
            }

            if (isset($_POST['attributes'], $_POST['attributes']['data-price'])) {
                $price = filter_var($_POST['attributes']['data-price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            }

            if (isset($_POST['attributes'], $_POST['attributes']['data-label'])) {
                $option_name = filter_var($_POST['attributes']['data-label'], FILTER_SANITIZE_STRING, FILTER_FLAG_ALLOW_FRACTION);
            }
            
            if ($method_id !== false && $price !== false) {
                $order = new WC_Order($order_id);

                $order_item_shipping = new WC_Order_Item_Shipping($order_item_id);
                $order_item_shipping->set_method_title($option_name);
                $order_item_shipping->set_method_id($method_id);
                $order_item_shipping->set_total($price);
                $order_item_shipping->save();

                $order = process_order_taxes($order);

                $order->calculate_totals(false);
                
                $shipping_data['price'] = $price;
                $shipping_data['order']['amount'] = number_format((float)$order->get_shipping_total(), 2, '.', '');
			    $shipping_data['order']['total'] = number_format((float)$order->get_total(), 2, '.', '');
			    $shipping_data['success'] = true;
            } else {
                $shipping_data['error'] = $error_message;
            }
		} else {
			$shipping_data['error'] = $error_message;
		}

		echo json_encode($shipping_data);
		wp_die();
    }
    
    function add_order_shipping_dropdown() {
        check_ajax_referer('order-item', 'security');
    
        if (!current_user_can('edit_shop_orders')) {
            wp_die(-1);
        }

        $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
        $order = new WC_Order($order_id);
        $chosen_shipping_method = get_chosen_shipping_method_from_plugin_file($order);

        if (!empty($chosen_shipping_method) && isset($chosen_shipping_method['key'])) {
            $current = $chosen_shipping_method['key'];
        } else {
            $current = 'default';
        }

        if ($order_id !== false) {
            $html = get_shipping_dropdown_html($order_id, $current);

            wp_send_json_success($html);
            echo 1;
        } else {
            echo 0;
        }

        wp_die();
    }

    function remove_shipping_items_from_order() {
        check_ajax_referer('order-item', 'security');
    
        if (!current_user_can('edit_shop_orders')) {
            wp_die(-1);
        }

        $order_calculate = false;
        $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
        $order = new WC_Order($order_id);

        if (isset($_POST['items_to_delete'])) {
            foreach ($_POST['items_to_delete'] as $item_id) {
                $order->remove_item((int)$item_id);
            }

            $order->save();
            $order_calculate = true;
        }

        if ($order_calculate) {
            $tax_args = get_tax_args_from_post_address();

            $order = process_order_taxes($order);

            //$order->calculate_taxes($tax_args);
            $order->calculate_totals(false);
        }

        wp_send_json_success();
        wp_die();
    }

    function remove_shipping_item_from_order() {
        check_ajax_referer('order-item', 'security');
    
        if (!current_user_can('edit_shop_orders')) {
            wp_die(-1);
        }

        try {
            $order_id = filter_var($_POST['order']['id'], FILTER_SANITIZE_NUMBER_INT);
            $order = wc_get_order($order_id);
            $item_to_delete = filter_var($_POST['order']['item_to_delete'], FILTER_SANITIZE_NUMBER_INT);

            if ($item_to_delete) {
                $order->remove_item($item_to_delete);
                $order->save();

                $order = process_order_taxes($order);

                $order->calculate_totals(false);
            }
    
            ob_start();
            include WP_PLUGIN_DIR .'/woocommerce/includes/admin/meta-boxes/views/html-order-items.php';
            $response['html'] = ob_get_clean();
    
            wp_send_json_success($response);
        } catch (Exception $e) {
			wp_send_json_error(array('error' => $e->getMessage()));
		}

        wp_die();
    }
