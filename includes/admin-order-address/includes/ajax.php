<?php
    add_action('wp_ajax_save_address_to_draft_order', 'save_address_to_draft_order');
    add_action('wp_ajax_nopriv_save_address_to_draft_order', 'save_address_to_draft_order');

	function save_address_to_draft_order() {
		check_ajax_referer('order-item', 'security');
		
		if (!current_user_can('edit_shop_orders')) {
			wp_die(-1);
		}

        try {
			if (!isset($_POST['order'], $_POST['order']['id'])) {
				throw new Exception(__('Order data insufficient.', 'woocommerce'));
			}

			$order_id       = absint($_POST['order']['id']);
			$order          = wc_get_order($order_id);
            $fields         = array('address_1', 'country', 'state', 'postcode', 'city');
            $address_types  = array('billing', 'shipping');

			if (!$order) {
				throw new Exception(__('Invalid order', 'woocommerce'));
			}

            foreach ($address_types as $type) {
                if (isset($_POST['order']['address'][$type]) && $_POST['order']['address'][$type]['allFieldsHaveInfo'] === 'true') {
                    foreach ($fields as $field) {
                        $value = filter_var($_POST['order']['address'][$type][$field], FILTER_SANITIZE_STRING);

                        update_post_meta($order_id, '_' . $type . '_' . $field, $value);
                    }
                }
            }

			// TODO: replace with eCard/Order_Date
			// Fixes the bug with certain items not getting taxes properly calculated
			$order->set_date_created(date("Y-m-d H:i:s"));
			$order->save();

			wp_send_json_success(); 
		} catch (Exception $e) {
			wp_send_json_error(array('error' => $e->getMessage()));
		}

		echo 1;
		wp_die();
    }
