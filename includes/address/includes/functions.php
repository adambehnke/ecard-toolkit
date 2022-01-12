<?php
    function get_tax_args_from_post_address() {
        $tax_args = array();
        $address_fields = get_address_fields();
        
        if (isset($_POST['taxable_address'])) {
            $data = $_POST['taxable_address'];
        } else {
            $data = $_POST;
        }

		foreach ($address_fields as $field) {
            if (isset($data[$field]) && $data[$field] != '') {
                $tax_args[$field] = wc_strtoupper(wc_clean(wp_unslash($data[$field])));
            }
        }
        
        return $tax_args;
    }

    // TODO: refactor class-flat-currency to use this module instead of its own function
    function get_order_address_from_post($type = 'billing') {
        $details = array();
        $fields = array('first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country');

        foreach ($fields as $field) {
            if (isset($_POST[$type . '_' . $field])) {
                $value = filter_var($_POST[$type . '_' . $field], FILTER_SANITIZE_STRING);
    
                if ($value !== '') {
                    $details[$field] = filter_var($_POST[$type . '_' . $field], FILTER_SANITIZE_STRING);
                }
            }
        }

        return $details;
    }

    function save_order_addresses($order, $addresses) {
        if (isset($addresses['billing'])) {
            $order->set_address($addresses['billing'], 'billing');
        }

        if (isset($addresses['shipping'])) {
            $order->set_address($addresses['shipping'], 'shipping');
        }

        $order->save();

        return $order;
    }

    function get_address_fields() {
        return array('country', 'state', 'postcode', 'city');
    }

    /**
     * Saves any changed address parts.
     * 
     * @param string $data encoded parameters
     * @return void
     */
    function save_changed_order_address_parts($data) {
        $decoded_data = urldecode($data);
        $data_exploded = explode("&", $decoded_data);
        $data_array = array();
        $changed_parts = array();

        foreach ($data_exploded as $data_part) {
            $data_part_exploded = explode("=", $data_part);

            if (isset($data_part_exploded[1]) && $data_part_exploded[1] !== '') {
                $data_array[$data_part_exploded[0]] = $data_part_exploded[1];
            }
        }

        if (isset($data_array['existing_order_id'])) {
            $ship_to_different_address = false;
            $recalculate_order = false;
            $order = wc_get_order($data_array['existing_order_id']);

            if (isset($data_array['ship_to_different_address']) && $data_array['ship_to_different_address'] == 1) {
                $ship_to_different_address = true;
            } else {
                $ship_to_different_address = false;
            }

            foreach (array('billing', 'shipping') as $type) {
                $address_parts[$type] = $order->get_address($type);

                foreach ($address_parts[$type] as $key => $part) {
                    if (isset($data_array[$type . '_' . $key])) {
                        if ($part !== $data_array[$type . '_' . $key]) {
                            $changed_parts[] = $type . '_' . $key;
                        }
                    }
                }
            }

            if (!empty($changed_parts)) {
                foreach ($changed_parts as $part) {
                    $method_name = 'set_' . $part;
                    $order->{$method_name}($data_array[$part]);
                }

                $recalculate_order = true;                
            }

            if (!$ship_to_different_address) {
                $billing_address = $order->get_address('billing');
                $order->set_address($billing_address, 'shipping');
                $order->save();

                $recalculate_order = true; 
            }

            if ($recalculate_order) {
                $order = process_order_taxes($order);
                $order->calculate_totals(false);
            }
        }
    }

    function order_should_ship_to_different_address($order_id) {
        if (is_numeric($order_id)) {
            $order = wc_get_order($order_id);

            if (is_a($order, 'WC_Order')) {
                $billing_shipping_addresses_same = are_billing_shipping_details_same($order);

                if (!$billing_shipping_addresses_same) {
                    return true;
                }
            }
        }

        return false;
    }

    function are_billing_shipping_details_same(WC_Order $order) {
        if ($order->get_billing_address_1() != $order->get_shipping_address_1()) {
            return false;
        }

        if ($order->get_billing_address_2() != $order->get_shipping_address_2()) {
            return false;
        }

        if ($order->get_billing_city() != $order->get_shipping_city()) {
            return false;
        }

        if ($order->get_billing_country() != $order->get_shipping_country()) {
            return false;
        }

        if ($order->get_billing_state() != $order->get_shipping_state()) {
            return false;
        }

        if ($order->get_billing_postcode() != $order->get_shipping_postcode()) {
            return false;
        }

        return true;
    }
