<?php
    add_action('wp_ajax_recalculate_changed_currency_order', 'recalculate_changed_currency_order');
	add_action('wp_ajax_nopriv_recalculate_changed_currency_order', 'recalculate_changed_currency_order');
	add_action('wp_ajax_check_order_items_currency_entries', 'check_order_items_currency_entries');
    add_action('wp_ajax_nopriv_check_order_items_currency_entries', 'check_order_items_currency_entries');

	function check_order_items_currency_entries() {
		check_ajax_referer('order-item', 'security');
		
		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
		}

        try {
			if (!isset($_POST['order'], $_POST['order']['id'])) {
				throw new Exception(__('Order data insufficient.', 'woocommerce'));
			}

			$order_id = absint($_POST['order']['id']);
			$order    = wc_get_order($order_id);

			if (!$order) {
				throw new Exception(__('Invalid order', 'woocommerce'));
			}

			$currency = filter_var($_POST['order']['_order_currency'], FILTER_SANITIZE_STRING);
			
			if ($currency) {
				$key = '__' . strtolower($currency);
			} else {
				$key = '__usd';
			}

			$items_to_delete = array();
			$items = $order->get_items();

			foreach ($items as $item) {
				$product_id = $item->get_product_id();
				$item_id = $item->get_id();
				$items_for_ajax_check[] = $item_id;

				$flat_price_data = get_post_meta($product_id, $key, false);

				if (empty($flat_price_data) && $item->get_type() == 'line_item') {
					$items_to_delete[] = $item_id;

					$item_linked_fees = get_linked_fees_from_item_array($_POST['order']['items'], $item_id);

					if (!empty($item_linked_fees)) {
						foreach ($item_linked_fees as $fee_id) {
							$items_to_delete[] = (int)$fee_id;
						}
					}
				}
			}

			$shipping_items = $order->get_items('shipping');

			foreach ($shipping_items as $shipping_item_id => $shipping_item_object) {
				$items_to_delete[] = $shipping_item_id;
			}

			wp_send_json_success(array('items_to_delete' => $items_to_delete)); 
		} catch (Exception $e) {
			wp_send_json_error(array('error' => $e->getMessage()));
		}

		echo 1;
		wp_die();
    }

    function recalculate_changed_currency_order() {
		check_ajax_referer('order-item', 'security');
		
		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
		}
		
        try {
			if (!isset($_POST['order'], $_POST['order']['id'], $_POST['order']['items'], $_POST['order']['taxable_address'], $_POST['order']['_order_currency'])) {
				throw new Exception(__('Order data insufficient.', 'woocommerce'));
			}

			$order_id = absint($_POST['order']['id']);
			$order    = new WC_Order($order_id);

			if (!$order) {
				throw new Exception(__('Invalid order', 'woocommerce'));
			}

			//$tax_args = $_POST['order']['taxable_address'];
			$currency = filter_var($_POST['order']['_order_currency'], FILTER_SANITIZE_STRING);		

			if (isset($_POST['order']['items_to_delete'])) {
				$order = delete_items_from_order($order, $_POST['order']);
			}

			$order->set_currency($currency);
			$order->save();

			// Recalculate all the line items
			$items = $order->get_items();
			$items_with_linked_fees = array();

			foreach ($items as $item) {
				$item_id = $item->get_id();
				$item_price = get_new_currency_item_price($currency, $item);
				$item->set_subtotal($item_price);
				$item->set_total($item_price);
				//$item->calculate_taxes($tax_args);
				$item->save();

				$item_linked_fees = get_linked_fees_from_item_array($_POST['order']['items'], $item_id);

				if (!empty($item_linked_fees)) {
					$items_with_linked_fees[$item_id]['fees'] = $item_linked_fees;
					$items_with_linked_fees[$item_id]['price'] = $item_price;
				}
			}

			// Recalculate all the fees
			$order = recalculate_order_fees($order, $items_with_linked_fees, $tax_args);
		} catch (Exception $e) {
			wp_send_json_error(array('error' => $e->getMessage()));
		}

		wp_send_json_success(1);

		echo 1;
		wp_die();
    }
