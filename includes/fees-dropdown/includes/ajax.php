<?php 
	add_action('wp_ajax_add_order_fee_item_with_dropdown', 'add_order_fee_item_with_dropdown');
	add_action('wp_ajax_nopriv_add_order_fee_item_with_dropdown', 'add_order_fee_item_with_dropdown');
	add_action('wp_ajax_process_added_fee_data', 'process_added_fee_data');
	add_action('wp_ajax_nopriv_process_added_fee_data', 'process_added_fee_data');
	add_action('wp_ajax_remove_order_item_linked_fees', 'remove_order_item_linked_fees');
	add_action('wp_ajax_nopriv_remove_order_item_linked_fees', 'remove_order_item_linked_fees');

    function add_order_fee_item_with_dropdown() {
		check_ajax_referer('order-item', 'security');

		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
		}

		$response = array();

		try {
			$order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
			$order    = wc_get_order($order_id);

			if (!$order) {
				throw new Exception(__('Invalid order', 'woocommerce'));
			}

			$amount = isset($_POST['amount']) ? wc_clean(wp_unslash($_POST['amount'])) : 0;
			$tax_args = get_tax_args_from_post_address();

			if (strstr($amount, '%')) {
				$formatted_amount = $amount;
				$percent          = floatval(trim($amount, '%'));
				$amount           = $order->get_total() * ($percent / 100);
			} else {
				$amount           = floatval($amount);
				$formatted_amount = wc_price($amount, array('currency' => $order->get_currency()));
			}

			$fee = new WC_Order_Item_Fee();
			$fee->set_amount($amount);
			$fee->set_total($amount);
			$fee->set_name(sprintf(__('Fee', 'woocommerce'), wc_clean($formatted_amount)));
			//$fee->calculate_taxes($tax_args);
			$fee->save();

			$order->add_item($fee);

			//process_order_taxes($order);

			//$order->calculate_taxes($tax_args);
			$order->calculate_totals(false);

			ob_start();
			include WP_PLUGIN_DIR .'/woocommerce/includes/admin/meta-boxes/views/html-order-items.php';
			$response['html'] = ob_get_clean();
		} catch (Exception $e) {
			wp_send_json_error(array('error' => $e->getMessage()));
		}

		wp_send_json_success($response);

		echo 1;
		wp_die();
	}

	function process_added_fee_data() {
		check_ajax_referer('order-item', 'security');

		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
		}

		$fee_data = array();
		$option_id = filter_var($_POST['option_id'], FILTER_SANITIZE_NUMBER_INT);
		$option_name = filter_var($_POST['option_name'], FILTER_SANITIZE_STRING);
		$order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
		$order_item_id = filter_var($_POST['order_item_id'], FILTER_SANITIZE_NUMBER_INT);
		$error_message = get_error_message('fee');

		if ($option_id !== false && $option_name !== false && $order_id !== false && $order_item_id !== false) {
			$tax_args = get_tax_args_from_post_address();

			$fee_post_meta = get_post_meta($option_id);
			$fee_data['price_to_calculate'] = isset($fee_post_meta['fee_amount']) && $fee_post_meta['fee_amount'][0] !== '' ? $fee_post_meta['fee_amount'][0] : 0;
			$fee_data['surcharge_type'] = isset($fee_post_meta['fee_type']) ? $fee_post_meta['fee_type'][0] : '';
			$fee_data['name'] = $option_name;

			$order = new WC_Order($order_id);
			$item = new WC_Order_Item_Fee($order_item_id);
	
			if (strpos($fee_data['surcharge_type'], 'percentage') !== false) {
				$subtotal = $order->get_subtotal();
				$fee_data['price'] = $subtotal * $fee_data['price_to_calculate'] / 100;
			} else {
				$fee_data['price'] = $fee_data['price_to_calculate'];
			}
				
			$currency = $order->get_currency();
	
			if ($currency !== 'USD') {
				$fee_data['price'] = get_converted_fee_price($fee_data['price'], $fee_data, $currency);
			}
	
			$fee_data['price'] = number_format((float)$fee_data['price'], 2, '.', '');
			$fee_data_object = Order_Fee_Data_Factory::create($fee_data);
	
			$item->set_name($option_name);
			$item->set_amount($fee_data['price']);
			$item->set_total($fee_data['price']);
			$item->add_meta_data('_fee_data', $fee_data_object->get());
			//$item->calculate_taxes($tax_args);
			$item->save();
		
			$order = process_order_taxes($order);

			$order->calculate_totals(false);
		
			/*
			$taxes = $item->get_taxes();
		
			if (isset($taxes['total']) && is_array($taxes['total'])) {
				$taxes['total'] = array_values($taxes['total']);
	
				if (isset($taxes['total'][0])) {
					$fee_data['taxes'] = number_format((float)$taxes['total'][0], 2, '.', '');
				} else {
					$fee_data['taxes'] = number_format(0, 2, '.', '');
				}
			} else {
				$fee_data['taxes']['new_order_item'] = 0;
			}
			*/
		
			$rush_order = detect_rush_order_by_name($option_name);
		
			if ($rush_order !== false) {
				update_post_meta($order_id, '_rush_orders', $rush_order);
			}
		
			$fee_data['order']['amount'] = number_format((float)$order->get_total_fees(), 2, '.', '');
			//$fee_data['order']['taxes'] = number_format((float)$order->get_total_tax(), 2, '.', '');
			$fee_data['order']['total'] = number_format((float)$order->get_total(), 2, '.', '');
			$fee_data['success'] = true;
		
			if (isset($_POST['totals_section_labels'])) {
				$fee_data = process_fees_in_order_totals_section($fee_data, $_POST['totals_section_labels'], $order);
			}
		} else {
			$fee_data['error'] = $error_message;
		}

		echo json_encode($fee_data);
		wp_die();
	}

	function remove_order_item_linked_fees() {
		check_ajax_referer('order-item', 'security');

		if (!current_user_can('edit_shop_orders') ||
			!isset($_POST['order']['linked_fees'])) {
			wp_die(-1);
		}

		try {
			$deletion_statuses = array();

			foreach ($_POST['order']['linked_fees'] as $fee_id) {
				$result = wc_delete_order_item($fee_id);

				if ($result === true) {
					$deletion_statuses[] = 1;
				} else {
					$deletion_statuses[] = 0;
				}
			} 

			if (in_array(0, $deletion_statuses)) {
				echo 0;
			} else {
				echo 1;
			}

			wp_die();
		} catch (Exception $e) {
			wp_send_json_error(array('error' => $e->getMessage()));
		}

		wp_die();
	}
