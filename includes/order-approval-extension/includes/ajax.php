<?php
    add_action("wp_ajax_persist_order_review_data", "persist_order_review_data");
    add_action("wp_ajax_nopriv_persist_order_review_data", "persist_order_review_data");
    add_action("wp_ajax_add_product_item_to_order", "add_product_item_to_order");
    add_action("wp_ajax_nopriv_add_product_item_to_order", "add_product_item_to_order");
    add_action("wp_ajax_approval_flow_proceed_to_checkout", "approval_flow_proceed_to_checkout");
    add_action("wp_ajax_nopriv_approval_flow_proceed_to_checkout", "approval_flow_proceed_to_checkout");
    add_action("wp_ajax_persist_order_shipping_method", "persist_order_shipping_method");
    add_action("wp_ajax_nopriv_persist_order_shipping_method", "persist_order_shipping_method");
    add_action("wp_ajax_apply_order_review_coupon", "apply_order_review_coupon");
    add_action("wp_ajax_nopriv_apply_order_review_coupon", "apply_order_review_coupon");
    add_action("wp_ajax_remove_order_approval_coupon", "remove_order_approval_coupon");
    add_action("wp_ajax_nopriv_remove_order_approval_coupon", "remove_order_approval_coupon");
    add_action("wp_ajax_exit_the_approval_flow", "exit_the_approval_flow");
    add_action("wp_ajax_nopriv_exit_the_approval_flow", "exit_the_approval_flow");
    add_action("wp_ajax_update_order_approval_order_details", "update_order_approval_order_details");
    add_action("wp_ajax_nopriv_update_order_approval_order_details", "update_order_approval_order_details");
    add_action("wp_ajax_delete_order_item_in_order_approval_flow", "delete_order_item_in_order_approval_flow");
    add_action("wp_ajax_nopriv_delete_order_item_in_order_approval_flow", "delete_order_item_in_order_approval_flow");

    function persist_order_review_data() {
        if (!wp_verify_nonce($_POST['nonce'], "persist_order_review_data_nonce")) {
            exit("No unauthorized access.");
        }  

        if (isset($_POST['order'], $_POST['order']['id'])) {
            $order_id = filter_var($_POST['order']['id'], FILTER_SANITIZE_NUMBER_INT);
            $order = new WC_Order($order_id);
            $order_items = $order->get_items();
            $production_time_updated = false;

            // Save cart & order items
            foreach ($_POST['order']['items'] as $order_item_id => $item) {
                if (isset($order_items[$order_item_id]) && is_a($order_items[$order_item_id], 'WC_Order_Item_Product')) {
                    $item_data = $order_items[$order_item_id]->get_data();
                    $subtotal = isset($item['quantity']) ? $item['quantity_price'] : $item_data['subtotal'];

                    if (isset($item['quantity'])) {
                        $order_items[$order_item_id]->set_quantity($item['quantity']);
                        $order_items[$order_item_id]->set_subtotal($item['quantity_price']);
                        $order_items[$order_item_id]->set_total($item['quantity_price']);
                        $order_items[$order_item_id]->save();

                        $order->calculate_totals(false);

                        $production_time = $order_items[$order_item_id]->get_meta('_production_time');

                        // Production time is set in the db
                        if ($production_time !== '') {

                            // Old production time is "rush"
                            if (strpos(strtolower($production_time), 'rush') !== false) {
                                $production_time_id = $order_items[$order_item_id]->get_meta('_linked_fee_production_time');
                                $order->remove_item($production_time_id);
                                $order->calculate_totals(false);
        
                                // Delete old rush fee regardless of the new status
                                Order_Item_Rush_Fee::delete_rush_fee_from_db($order_id, $order_item_id, $production_time_id);
        
                                // Production time stays "rush"
                                if (!isset($item['production_time']) ||
                                    isset($item['production_time']) && strpos(strtolower($item['production_time']), 'rush') !== false) {
                                    $rush_fee = new Order_Item_Rush_Fee($order_items[$order_item_id]);
            
                                    $rush_fee->set_item_subtotal($subtotal);
                                    $rush_fee->set_production_time($production_time);
                                    $rush_fee->save();
                                }

                                $production_time_updated = true;
                            }
                        }

                        $printing = $order_items[$order_item_id]->get_meta('_printing');

                        if ($printing !== '') {
                            $linked_fee_key = Order_Fee_Helper::get_linked_fee_key('_printing');
                            $fee_id = $order_items[$order_item_id]->get_meta($linked_fee_key);

                            if ((int)$fee_id > 0) {
                                $printing_fee = new Order_Item_Fee($order_items[$order_item_id]);

                                $printing_fee->set_fee_id($fee_id);
                                $printing_fee->set_db_key('_printing');
                                $printing_fee_data = $printing_fee->get_fee_data();
                                $printing_fee->delete();

                                $new_printing_fee = new Order_Item_Fee($order_items[$order_item_id], 'Printing: ' . $printing);
                                $new_printing_fee->set_item_subtotal($subtotal);
                                $new_printing_fee->set_surcharge_type($printing_fee_data['surcharge_type']);
                                $new_printing_fee->set_price_to_calculate($printing_fee_data['price_to_calculate']);
                                $new_printing_fee->set_db_value($printing);
                                $new_printing_fee->save();
                            }
                        }
                    }

                    if (isset($item['finish'])) {
                        wc_update_order_item_meta($order_item_id, '_finish', $item['finish']);
                    }

                    if (isset($item['production_time']) && $production_time_updated !== true) {
                        $rush_fee = new Order_Item_Rush_Fee($order_items[$order_item_id]);

                        if (strpos(strtolower($item['production_time']), 'rush') !== false) {
                            $rush_fee->set_item_subtotal($subtotal);
                            $rush_fee->set_production_time($item['production_time']);
                            $rush_fee->save();
                        } else {
                            if (isset($item['production_time_id'])) {
                                $rush_fee->set_fee_id($item['production_time_id']);
                                $rush_fee->delete();
                            }
                        }
                    }
                }
            }

            $order->set_discount_total(0);
            $order->calculate_shipping(); 

            update_cart_with_order_items($order);

            $order->set_shipping_total(WC()->cart->get_shipping_total());
            $order->save();
            $order = process_order_taxes($order);
            $order->calculate_totals(false);
        }	
        
        echo 1;

        wp_die();
    }

    function add_product_item_to_order() {
        $order_id = 0;
        $item_id = 0;

        if (!wp_verify_nonce($_POST['nonce'], "add_product_item_to_order_nonce")) {
            exit("No unauthorized access.");
        }

        if (isset($_POST['single_product']) && $_POST['single_product'] == 1) {
            $order_json = stripslashes($_POST['order']);
            $order_data = json_decode($order_json, true);
            $order_id = filter_var($order_data['id'], FILTER_SANITIZE_NUMBER_INT);
            $product_id = filter_var($order_data['item']['product_id'], FILTER_SANITIZE_NUMBER_INT);
            $quantity = filter_var($order_data['item']['quantity'], FILTER_SANITIZE_NUMBER_INT);
            $addons = isset($order_data['item']['addons']) ? $order_data['item']['addons'] : array();
        } else if (isset($_POST['order'], $_POST['order']['id']) && !empty($_POST['order']['item'])) {
            $order_id = filter_var($_POST['order']['id'], FILTER_SANITIZE_NUMBER_INT);
            $product_id = filter_var($_POST['order']['item']['product_id'], FILTER_SANITIZE_NUMBER_INT);
            $quantity = filter_var($_POST['order']['item']['quantity'], FILTER_SANITIZE_NUMBER_INT);
            $addons = isset($_POST['order']['item']['addons']) ? $_POST['order']['item']['addons'] : array();
        }

        if ($order_id > 0) {
            $product_in_order = false;
            $order = new WC_Order($order_id);
            $product = new WC_Product($product_id);
            $items = $order->get_items();
            $currency_db_key = get_currency_db_key($order->get_currency());
            $flat_price_data = get_post_meta($product_id, $currency_db_key);
            
            // Check if product is already in the order
            foreach ($items as $item) {
                $item_id = $item->get_id();
                $item_product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

                if ($product_id == $item_product_id) {
                    $item_quantity = $item->get_quantity();
                    $item_quantity += $quantity;

                    // Get flat price per item based on quantity
                    $price_per_item = get_price_per_item_from_flat_price_data($flat_price_data, $item_quantity);
                    
                    if ($price_per_item <= 0) {
                        break;
                    }

                    $item_total = $item_quantity * $price_per_item;
                    $item->set_quantity($item_quantity);
                    $item->set_total($item_total);
                    $item->save();

                    if (!empty($addons)) {
                        $order = process_order_item_addons($order, $item, $addons);
                    }

                    $product_in_order = true;

                    break;
                }
            }

            // Add product to order if it wasn't found
            if ($product_in_order === false) {
                $price_per_item = get_price_per_item_from_flat_price_data($flat_price_data, $quantity);

                if ($price_per_item > 0) {
                    $product->set_price($price_per_item);
                    $item_id = $order->add_product($product, $quantity);

                    if (!empty($addons)) {
                        $item = new WC_Order_Item_Product($item_id);
                        $order = process_order_item_addons($order, $item, $addons);
                    }
                }
            }

            //$taxable_address = $order->get_address('shipping');
            
            $order->calculate_shipping();

            update_cart_with_order_items($order);

			$chosen_methods = WC()->session->get('chosen_shipping_methods');

			if (is_array($chosen_methods) && !empty($chosen_methods)) {
				$chosen_method = $chosen_methods[0];
				$shipping_for_package = WC()->session->get('shipping_for_package_0');

				if (!is_null($shipping_for_package) && isset($shipping_for_package['rates'])) {
					$shipping_rates = WC()->session->get('shipping_for_package_0')['rates'];
				}

				if (isset($shipping_rates, $shipping_rates[$chosen_method])) {
					$shipping_rate_obj = $shipping_rates[$chosen_method];
				}
			}	

			if (isset($shipping_rate_obj)) {
				save_chosen_shipping_method_for_order($order, $shipping_rate_obj);
			}

            $order->set_shipping_total(WC()->cart->get_shipping_total());
            $order->save();
            
            $order = process_order_taxes($order);
            $order->calculate_totals(false);

            if (!empty($_FILES) && isset($_FILES['file'])) {
                $filename  = 'attachment-' . $item_id . '-';
                $filename .= rand(1000,9999) . '-';
                $filename .=  $_FILES['file']['name'];

                $_FILES['file']['name'] = $filename;

                $aws = new AWS_S3();
				$names[] = $aws->upload_file_to_s3($_FILES['file'], false);

				$upload = wc_add_order_item_meta($item_id, '_attachments_aws', $names, true);
            }

            echo 1;
        } else {
            echo 0;
        }
        
        wp_die();
    }

    function approval_flow_proceed_to_checkout() {
        if (!wp_verify_nonce($_POST['nonce'], "approval_flow_proceed_to_checkout_nonce")) {
            exit("No unauthorized access.");
        } 

        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        $order = new WC_Order($order_id);
        $pay_by_check = Order_Management::is_pay_by_check($order_id);

        if ($pay_by_check) {
            WC()->cart->empty_cart();
            echo get_site_url() . '/thankyou-page?success=check';
        } else {
            echo $order->get_checkout_payment_url();
        }

        exit();
    }

    function persist_order_shipping_method() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], "persist_order_shipping_method_nonce")) {
            exit("No unauthorized access.");
        }

        $chosen_method = filter_input(INPUT_POST, 'shipping_method', FILTER_SANITIZE_STRING);
        $shipping_rate_obj = WC()->session->get('shipping_for_package_0')['rates'][$chosen_method];
        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        $quoted_shipping = get_quoted_shipping($order_id);

        if (!is_null($chosen_method) && $chosen_method !== false && empty($quoted_shipping)) {
            WC()->session->set('chosen_shipping_methods', array($chosen_method));
        }

        echo 1;
        wp_die();
    }

    function apply_order_review_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], "apply_order_review_coupon_nonce")) {
            exit("No unauthorized access.");
        }

        $coupon_code = filter_input(INPUT_POST, 'coupon_code', FILTER_SANITIZE_STRING);

        if ($coupon_code !== '') {
            $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
            $order = new WC_Order($order_id);
            $coupon_code_formatted = wc_format_coupon_code($coupon_code);
			$coupon = new WC_Coupon($coupon_code_formatted);
            $order_result = false;

            try {
                $order_result = $order->apply_coupon($coupon);
                $cart_result = WC()->cart->apply_coupon($coupon_code_formatted);
            } catch (Exception $e) {
                echo 5;
                wp_die();
            }

            if ($order_result === true) {
                echo 1;
            } elseif (is_a($order_result, 'WP_Error')) {
                if (isset($order_result->errors['invalid_coupon'])) {
                    if (strpos($order_result->errors['invalid_coupon'][0], 'already applied') !== false) {
                        echo 2; 
                    } elseif (strpos($order_result->errors['invalid_coupon'][0], 'not exist') !== false) {
                        echo 3;
                    } elseif (strpos($order_result->errors['invalid_coupon'][0], 'not applicable') !== false) {
                        echo 4;
                    } else {
                        echo 5;
                    }
                } else {
                    echo 5;
                }
            } else {
                echo 5;
            }
        } else {
            echo 5;
        }

        wp_die();

    }

    function remove_order_approval_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], "remove_coupon_link_nonce")) {
            exit("No unauthorized access.");
        }

        $coupon_code = filter_input(INPUT_POST, 'coupon_code', FILTER_SANITIZE_STRING);

        if ($coupon_code !== '') {
            $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
            $order = new WC_Order($order_id);

            $order_result = $order->remove_coupon($coupon_code);
            $cart_result = WC()->cart->remove_coupon($coupon_code);
        }

        echo 1;
        wp_die();
    }

    function exit_the_approval_flow() {
        if (!wp_verify_nonce($_POST['nonce'], "exit_the_approval_flow_nonce")) {
            exit("No unauthorized access.");
        }

        if (!isset($_POST['forwarding_url'])) {
            echo 0;
            wp_die();
        }

        $forwarding_url_raw = preg_replace("/[\$_+!*'(),{}|\\^~\[\]`\"\>\<#%;?@&=]/", "", $_POST['forwarding_url']);
        $forwarding_url = filter_var($forwarding_url_raw, FILTER_SANITIZE_URL);
        $forwarding_url_array = parse_url($forwarding_url);
        $forwarding_url_array['complete_url'] = $forwarding_url;
        $forwarding_url_parsed = parse_forwarding_url($forwarding_url_array);

        // Exit the approval flow
        if ($forwarding_url_parsed) {
            WC()->cart->empty_cart();

            wp_send_json_success($forwarding_url_parsed);
        } else {
            wp_send_json_error();
        }

        wp_die();
    }

    function delete_order_item_in_order_approval_flow() {
        if (!wp_verify_nonce($_POST['nonce'], "delete_order_item_nonce")) {
            exit("No unauthorized access.");
        }

        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        $item_to_delete = filter_input(INPUT_POST, 'item_to_delete', FILTER_SANITIZE_NUMBER_INT);
        $order = wc_get_order($order_id);

        $order->remove_item($item_to_delete);
        $order->save();

        update_cart_with_order_items($order);

        $avatax_response = wc_avatax()->get_api()->calculate_cart_tax(WC()->cart);

        save_total_taxes_to_wp_orders($order_id, $avatax_response->get_total_tax());

        wp_send_json_success('Item removed.');
        wp_die();
    }

    function update_order_approval_order_details()  {
        // TODO add nonce
        $add_products = filter_input(INPUT_POST, 'add_products', FILTER_SANITIZE_NUMBER_INT);
        $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
        $quoted_shipping = get_quoted_shipping($order_id);
        $order = wc_get_order($order_id);
        $pay_by_check = Order_Management::is_pay_by_check($order_id);

        /*
        if (empty($quoted_shipping) && $add_products !== '1') {
                $shipping_details = WC()->session->get('shipping_for_package_0')['rates'][WC()->session->get('chosen_shipping_methods')[0]];

                $item = new WC_Order_Item_Shipping();

                if (isset($shipping_details) && $shipping_details != '') {
                        $item->set_method_title($shipping_details->get_label());
                        $item->set_method_id($shipping_details->get_method_id());
                        $item->set_total($shipping_details->get_cost());
                }


                $order->add_item($item);
                $order->calculate_totals(false);
        }
        */

        //$order = $this->update_billing_details(false);

        /*
        if ($_POST['ship_to_different_address'] == 1) {
                $order = $this->update_shipping_details(false);
        } else {
                $order = $this->update_order_address_details(
                        'shipping',
                        $this->get_order_address_details_from_post(),
                        false
                );
        }
        */

        if ($add_products !== '1') {
                if ($pay_by_check) {
                        Order_Management::approve_order($order);

                        WC()->cart->empty_cart();
                        $url = get_site_url() . '/thankyou-page?success=check';
                } else {
                        /* empty_cart clears the all the existing product of cart */
                        //$woocommerce->cart->empty_cart();
                        $url = $order->get_checkout_payment_url();
                }
        } else {
                $url = home_url() . '/checkout/add-products/?flow=order-approval&order=' . $order_id;
        }

        echo $url;

        wp_die();
    }
