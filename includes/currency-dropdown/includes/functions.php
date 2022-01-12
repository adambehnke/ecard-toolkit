<?php
    function add_currency_dropdown($order) { 
        $currencies = get_currencies();
        $currency_options = array();        
        $current_option = get_current_currency_option($order);
        $disabled_statuses = array('processing', 'failed', 'completed');
        $disabled = in_array($order->get_status(), $disabled_statuses)  ? true : false;

        foreach ($currencies as $currency) {
            $currency_options[$currency['currency_code']] = $currency['currency_name'];
        }
        
        $dropdown = new Order_Dropdown(array(
            'data' => $currency_options,
            'current' => $current_option,
            'default' => 'Choose a currency',
            'attributes' => array( 
                'class' => 'currency-options dropdown-options',
                'name' => '_order_currency',
                'data-current' => $current_option,
                'disabled' => $disabled
            )
        ));

        $html  = '<p class="form-field form-field-wide wc-customer-user">';
        $html .= '<label for="_order_currency">Currency</label>';
        $html .= $dropdown->generate();
        $html .= '</p>';
        $html .= '<input type="hidden" name="_order_currency" value="' . $current_option . '" />';

        echo $html;
    }; 
             
    add_action('woocommerce_admin_order_data_after_order_details', 'add_currency_dropdown', 10, 1); 

    function get_current_currency_option($order) {
        $order_currency = $order->get_currency();
        $current_currency_option = 'Choose a currency';

        if (isset($_GET['currency'])) {
            $current_currency_option = filter_input(INPUT_GET, 'currency', FILTER_SANITIZE_STRING);
        } elseif (!is_null($order_currency) && $order_currency) {
            $current_currency_option = $order_currency;
        }

        return $current_currency_option;
    }

    function save_draft_order_currency($post_id, $post, $update) {
        if ($post->post_status === 'draft' && isset($_POST['_order_currency'])) {
            $currency = filter_input(INPUT_POST, '_order_currency', FILTER_SANITIZE_STRING);
            
            update_post_meta($post_id, '_order_currency', $currency);
        }
    }
    
    add_action('save_post_shop_order', 'save_draft_order_currency', 10, 3);

    function load_currency_confirmation_modal() {
        if (is_shop_order_page()) {
            $modal = new eCard\Modal();
    
            $modal->set_title('This will change the order currency to <span class="modal-currency"></span>, <span class="delete-items-text">delete the following items,</span> and recalculate the totals. Are you sure you want to proceed?');
            $modal->set_empty_content('items-to-delete', true);
            $modal->set_button_text('confirm', 'Proceed');
            $modal->set_status_animation(true);
            $modal->set_class('modal-confirmation modal-currency-confirmation');
            
            echo $modal->generate();
        }
    }
    
    add_action('admin_footer', 'load_currency_confirmation_modal');
    
    function get_linked_fees_from_item_array($items_array, $item_id) {
        $linked_fees = array();

        foreach ($items_array as $id => $item) {
            if ($id === $item_id && isset($item['linked_fees']) && !empty($item['linked_fees'])) {
                $linked_fees = $item['linked_fees'];
            }
        }

        return $linked_fees;
    }
    
    function get_new_currency_item_price($currency, $item) {
        $currency_db_key = get_currency_db_key($currency);
		$product_id = $item->get_product_id();
        
        $flat_price_data = get_post_meta($product_id, $currency_db_key);
        $price_per_item = get_price_per_item_from_flat_price_data($flat_price_data, $item->get_quantity());
        $new_item_price = $price_per_item * $item->get_quantity();
        
        return $new_item_price;
    }
    
    function calculate_and_set_new_fee_price(WC_Order_Item_Fee $fee, $initial_price, $fee_data, $tax_args, $currency = 'USD') {
        $new_fee_price = get_converted_fee_price($initial_price, $fee_data, $currency);  

        $fee->set_amount($new_fee_price);
        $fee->set_total($new_fee_price);
        //$fee->calculate_taxes($tax_args);
        $fee->save();        
    }

    function get_converted_fee_price($initial_price, $fee_data, $currency) {
        if ($fee_data['surcharge_type'] === 'percentage_based') {
            $new_fee_price = $initial_price * ($fee_data['price_to_calculate'] / 100);            
        } elseif ($fee_data['surcharge_type'] === 'flat_fee') {
            $exchange_rate = get_usd_to_currency_exchange_rate($currency);

            if ($currency !== 'USD') {
                $new_fee_price = $initial_price * $exchange_rate;
            } else {
                $new_fee_price = $fee_data['price_to_calculate'];
            }       
        }

        return $new_fee_price;
    }

    /**
     * Recalculates the order fees based on the items with linked fees and the tax args.
     */
    function recalculate_order_fees(WC_Order $order, $items_with_linked_fees, $tax_args) {
        $fees = $order->get_items('fee');

		if (!empty($items_with_linked_fees)) {
			foreach ($items_with_linked_fees as $item_with_fee_id => $item_with_fee_data) {
				foreach ($item_with_fee_data['fees'] as $fee_id) {
					if (isset($fees[$fee_id])) {
						$fee_data = $fees[$fee_id]->get_meta('_fee_data');

                        // Modify percentage based fee
						if (isset($fee_data['surcharge_type']) && $fee_data['surcharge_type'] == 'percentage_based') {
							calculate_and_set_new_fee_price($fees[$fee_id], $item_with_fee_data['price'], $fee_data, $tax_args);
							unset($fees[$fee_id]);
                        } 

                        if (empty($fee_data)) {
                            $fee_data = array();
                            $rush_type = get_post_meta($order->get_id(), '_rush_orders', true);

                            if ($rush_type !== '' && $rush_type !== 'none') {
                                $fee_data['price_to_calculate'] = Order_Item_Rush_Fee::get_price_to_calculate_by_data_key($rush_type);
                                $fee_data['surcharge_type'] = 'percentage_based';

                                calculate_and_set_new_fee_price($fees[$fee_id], $item_with_fee_data['price'], $fee_data, $tax_args);
							    unset($fees[$fee_id]);
                            }
                        }
					}
				}
			}
		}

        $order->calculate_totals(false);

        // Check the rest of the fees
		foreach ($fees as $fee) {
            $fee_data = $fee->get_meta('_fee_data');
            $currency = $order->get_currency();

            if (isset($fee_data['surcharge_type'])) {
                if ($fee_data['surcharge_type'] == 'percentage_based') {
                    $initial_price = $order->get_subtotal();                    
                } elseif ($fee_data['surcharge_type'] == 'flat_fee') {
                    $initial_price = $fee->get_amount();
                }

                calculate_and_set_new_fee_price($fee, $initial_price, $fee_data, $tax_args, $currency);
                $order->calculate_totals(false);
            }
		}

        $order = process_order_taxes($order);
        $order->calculate_totals(false);
        
        return $order;
    }
    
    function delete_items_from_order(WC_Order $order, array $post_order = array()) {
        foreach ($post_order['items'] as $item_id => $item) {
            if (in_array($item['type'], array('item', 'shipping')) && array_search($item_id, $post_order['items_to_delete']) !== false) {
                // Delete any linked fees first
                if (isset($item['linked_fees'])) {
                    foreach ($item['linked_fees'] as $fee_id) {
                        $order->remove_item($fee_id);

                        if (($position = array_search($fee_id, $post_order['items_to_delete'])) !== false) {
                            unset($post_order['items_to_delete'][$position]);
                        }
                    }
                }

                // Delete the order item
                $order->remove_item($item_id);
            }
        }

        if (!empty($items_to_delete)) {
            $order = process_order_taxes($order);
            $order->calculate_totals(false);
        }

        return $order;
    }
    
    function update_shipping_items(WC_Order $order, string $currency) {
        // Remove shipping for order with a currency that is not USD
        if ($currency !== 'USD') {
            $shipping_items = $order->get_items('shipping');

            foreach ($shipping_items as $item_id => $item) {
                $order->remove_item($item_id);
            }

            $order = process_order_taxes($order);
            $order->calculate_totals(false);
        }

        return $order;
    }  
    
