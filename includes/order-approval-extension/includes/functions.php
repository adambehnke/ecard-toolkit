<?php
    function is_order_approval() {
        if (is_page('thankyou-page') && isset($_GET['success']) && $_GET['success'] == 'check') {
            return false;
        }
        
        if (!is_null(WC()->cart)) {
            $cart_contents = WC()->cart->get_cart();

            foreach ($cart_contents as $cart_item) {
                if (isset($cart_item['existing_order_id'])) {
                    return true;
                }
            }
        }
    
        if ((isset($_GET['flow']) && $_GET['flow'] === 'order-approval') ||
            (isset($_POST['flow']) && $_POST['flow'] === 'order-approval')) {
            return true;
        }

        if (isset($_GET['wc-ajax']) && $_GET['wc-ajax'] === 'update_order_review') {
            $order_id = get_existing_order_id_from_post_data($_POST['post_data']);

            return $order_id > 0 ? true : false;
        }

        if (is_page('add-products') || is_page('approval-page')) {
            return true;
        }
    
        return false;
    }

    function is_order_approval_single_product() {
        if (is_order_approval()) {
            if (isset($_GET['single-product']) && $_GET['single-product'] == 1) {
                return true;
            }
        }

        return false;
    }

    /**
    * Output the Order review table for the checkout.
    *
    * @param bool $deprecated Deprecated param.
    */
    /*
    function woocommerce_order_review($deprecated = false) {
        wc_get_template(
            'checkout/review-order.php',
            array(
                'checkout' => WC()->checkout(),
                'order_approval' => is_order_approval()
           )
       );
    }
    */
    
    add_shortcode('checkout_add_products', 'checkout_add_products_callback');

    function checkout_add_products_callback($atts) {
        if (is_order_approval()) {
            ob_start();
            include ORDER_APPROVAL_EXTENSION_PATH . 'templates/add-products-main.php';
            $html = ob_get_contents();
            ob_end_clean();
    
            echo $html;
        }
    }
    
    add_action('template_redirect', 'remove_short_description_on_add_products');

    function remove_short_description_on_add_products() {
        if (is_page('add-products')) {
            remove_action('woocommerce_after_shop_loop_item', 'boxshop_template_loop_categories', 10);
            remove_action('woocommerce_after_shop_loop_item', 'boxshop_template_loop_product_sku', 30);
            remove_action('woocommerce_after_shop_loop_item', 'boxshop_template_loop_short_description', 40);
            remove_action('woocommerce_after_shop_loop_item', 'boxshop_template_loop_short_description_listview', 65);  
        }
    }

    add_action('widgets_init', 'ts_order_review_product_categories_load_widgets');

    function ts_order_review_product_categories_load_widgets() {
        register_widget('Order_Review_Product_Categories_Widget');
    }

    add_action('widgets_init', 'order_review_add_products_widgets_init');

    function order_review_add_products_widgets_init() {
        register_sidebar(array(
            'name'          => 'Order Review - Add Products Sidebar',
            'id'            => 'add_products_sidebar',
            'before_widget' => '<section class="widget-container ts-product-categories-widget">',
            'after_widget'  => '</section>',
            'before_title'  => '<h3 class="widget-title heading-title">',
            'after_title'   => '</h2>',
       ));
    }
    
    add_filter('woocommerce_add_to_cart_redirect', 'redirect_single_product_add_to_cart');

    function redirect_single_product_add_to_cart() {
        if (is_order_approval()) {
            return wp_get_referer();
        } else {
            return wc_get_cart_url();
        }
    }

    // do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );

    function get_existing_order_id_from_post_data($post_data) {
        $existing_order_id = 0;
        $post_data_array = explode('&', $post_data);
        $parameter_key_string = 'existing_order_id=';

        foreach ($post_data_array as $key_value_pair) {
            if (strpos($key_value_pair, $parameter_key_string) !== false) {
                $existing_order_id = (int)str_replace($parameter_key_string, '', $key_value_pair);
            }
        }

        return $existing_order_id;
    }

    function get_existing_order_id_from_form_data()  {
        $existing_order_id = 0;
        
        if (isset($_POST['post_data'])) {
            $existing_order_id = get_existing_order_id_from_post_data($_POST['post_data']);
        } elseif (isset($_GET['order'])) {
            $existing_order_id = filter_var($_GET['order'], FILTER_SANITIZE_NUMBER_INT);
        }

        return $existing_order_id;
    }

    function get_existing_order_id_from_cart() {
        $cart_contents = WC()->cart->get_cart();

        foreach ($cart_contents as $cart_item) {
            if (isset($cart_item['existing_order_id'])) {
                return $cart_item['existing_order_id'];
            }
        }

        return 0;
    }

    function get_existing_order_id() {
        if (!empty(WC()->cart->get_cart_contents())) {
            foreach (WC()->cart->get_cart_contents() as $value) {
                if (isset($value['existing_order_id'])) {
                    return $value['existing_order_id'];
                }
            }
        }

        if (is_order_approval()) {
            return get_existing_order_id_from_form_data();
        }

        return false;
    }

    add_filter('woocommerce_checkout_redirect_empty_cart', 'remove_checkout_redirection_for_empty_cart');
    add_filter('woocommerce_checkout_update_order_review_expired', 'remove_checkout_redirection_for_empty_cart');

    function remove_checkout_redirection_for_empty_cart() {
        if (is_order_approval()) {
            return false;
        }
    }

    function process_and_display_order_review_shipping($order_id) {
        $packages = WC()->shipping->get_packages();
        $first    = true;

        foreach ($packages as $i => $package) {
            $chosen_method = isset(WC()->session->chosen_shipping_methods[ $i ]) ? WC()->session->chosen_shipping_methods[ $i ] : '';
            $product_names = array();
    
            if (count($packages) > 1) {
                foreach ($package['contents'] as $item_id => $values) {
                    $product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
                }
                $product_names = apply_filters('woocommerce_shipping_package_details_array', $product_names, $package);
            }

            wc_get_template(
                'cart/cart-shipping.php',
                array(
                    'order_id'                 => $order_id,
                    'nonce'                    => wp_create_nonce("persist_order_shipping_method_nonce"),
                    'package'                  => $package,
                    'available_methods'        => $package['rates'],
                    'show_package_details'     => count($packages) > 1,
                    'show_shipping_calculator' => true,
                    'package_details'          => implode(', ', $product_names),
                    'package_name'             => apply_filters('woocommerce_shipping_package_name', (($i + 1) > 1) ? sprintf(_x('Shipping %d', 'shipping packages', 'woocommerce'), ($i + 1)) : _x('Shipping', 'shipping packages', 'woocommerce'), $i, $package),
                    'index'                    => $i,
                    'chosen_method'            => $chosen_method,
                    'formatted_destination'    => WC()->countries->get_formatted_address($package['destination'], ', '),
                    'has_calculated_shipping'  => WC()->customer->has_calculated_shipping(),
               ),
           );
    
            $first = false;
        }
    }

    function check_product_in_cart($product_id, $quantity, $cart_items) {
        $product_cart_check = array();
        $product_cart_check['state'] = 'product_not_in_cart';

        foreach ($cart_items as $cart_item) {
            if ($product_id == $cart_item['product_id']) {
                $product_cart_check['key'] = $cart_item['key'];

                if ($quantity != $cart_item['quantity']) {
                    $product_cart_check['key'] = $cart_item['key'];
                    $product_cart_check['state'] = 'adjust_quantity';
                } else {
                    $product_cart_check['state'] = 'product_in_cart';
                }
            }
        }

        return $product_cart_check;
    }

    /**
     * Make sure the cart and the current order have the same items
     */
    function update_cart_with_order_items($order) {
        $order_items = $order->get_items();
        $cart_items = WC()->cart->get_cart();
        $cart_needed_adjustment = false;
        $order_items_found_in_cart = array();

        foreach ($order_items as $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            $product_cart_check = check_product_in_cart($product_id, $quantity, $cart_items);
            $unique_key = md5(microtime() . rand());

            if ($product_cart_check['state'] === 'adjust_quantity') {
                WC()->cart->set_quantity($product_cart_check['key'], $quantity);

                $cart_needed_adjustment = true;
            } elseif ($product_cart_check['state'] === 'product_not_in_cart') {
                WC()->cart->add_to_cart($product_id, $quantity, '', '', array(
                    'existing_order_id' => $order->get_id(),
                    'unique_key' => $unique_key,
                    'order_item_id' => $item->get_id(),
                    'order_item_total' => $item->get_total()
                ));
                
                $cart_needed_adjustment = true;
            } elseif ($product_cart_check['state'] === 'product_in_cart') {
                $order_items_found_in_cart[] = $item->get_id();
            }
        }

        // Check if cart has any items that the order does not
        foreach ($cart_items as $item) {
            if (!in_array($item['order_item_id'], $order_items_found_in_cart)) {
                WC()->cart->remove_cart_item($item['key']);

                $cart_needs_adjustment = true;
            }
        }

        if ($cart_needed_adjustment) {
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals(true);
        }

        set_chosen_shipping_method_session_key($order);
    }

    function process_order_item_addons($order, $item, $addons) {
        $item_subtotal = $item->get_subtotal();
        $item_name = $item->get_name();
        $fees = $order->get_fees();
        $fee_is_in_order = false;
        
        foreach ($addons as $addon) {
            if (isset($addon['price']) && $addon['price'] > 0) {
                if (strpos(strtolower($addon['name']), 'custom design') !== false) {
                    $fee_name_prefix = $addon['label'];
                } else if (strtolower($addon['name']) === 'printing') {
                    $fee_name_prefix = $addon['name'] . ': ' . $addon['label'];
                } else {
                    $fee_name_prefix = $addon['name'];
                }

                $order_item_fee = new Order_Item_Fee($item, $fee_name_prefix);
                $fee_is_in_order = $order_item_fee->is_in_order();

                if (!$fee_is_in_order) {
                    $order_item_fee->set_db_value($addon['label']);
                    $order_item_fee->set_surcharge_type($addon['price_type']);
                    $order_item_fee->set_price_to_calculate($addon['price']);
                    $order_item_fee_id = $order_item_fee->save();

                    $order = $order_item_fee->get_order();        
                    $order->add_item($order_item_fee);

                    WC()->cart->add_fee($order_item_fee->get_fee_name(), $order_item_fee->get_surcharge(), true, '');
                    WC()->cart->calculate_totals(true);
                }
            } else {
                if (strtolower($addon['name']) === 'printing') {
                    $order = Order_Item_Fee::delete_linked_fee_from_order_by_key($item, '_printing');
                } elseif (strtolower($addon['name']) === 'paper color') {
                    $item->add_meta_data( '_paper-color', $addon['label'], true);
                    $item->save();
                }
            }
        }

        return $order;
    }

    function add_addon_to_order_item_meta($item, $addon) {
        if (strpos(strtolower($addon['name']), 'custom design') !== false) {
            $meta_key = '_design-service';
        } else {
            $meta_key = strtolower($addon['name']);
            $meta_key = str_replace(' ', '-', $meta_key);
            $meta_key = '_' . $meta_key;
        }

        wc_add_order_item_meta($item->get_id(), $meta_key, $addon['label'], true);
    }

    function addon_fee_in_order($name, $fees) {
        foreach ($fees as $fee) {
            if ($fee->get_name() == $name) {
                return true;
            }
        }

        return false;
    }

    function get_cart_key_by_product_id($product_id, $items) {
        foreach ($items as $key => $item) {
            if (isset($item['product_id']) && $item['product_id'] == $product_id) {
                return $key;
            }
        }

        return '';
    }

    function save_chosen_shipping_method_for_order($order, $shipping_rate_obj) {
        if (!is_null($shipping_rate_obj) && isset($shipping_rate_obj) && 
             $shipping_rate_obj != '' && 
             $shipping_rate_obj->method_id != 'request_shipping_quote') {
            $order = remove_order_shipping($order);

            $item = new WC_Order_Item_Shipping();
            $item->set_method_title($shipping_rate_obj->get_label());
            $item->set_method_id($shipping_rate_obj->get_method_id());
            $item->set_total($shipping_rate_obj->get_cost());
            $item->save();

            $order->add_item($item);
            $order->save();

            $order = process_order_taxes($order);
            $order->calculate_totals(false);
        }

        return $order;
    }

    function get_order_item_totals($item) {
        $item_totals['subtotal'] = $item->get_subtotal();
        $item_totals['total'] = $item->get_total();
        $item_totals['discount'] = $item_totals['subtotal'] - $item_totals['total'];

        return $item_totals;
    }

    function wc_order_totals_coupon_html($coupon) {
        if (is_string($coupon)) {
            $coupon = new WC_Coupon($coupon);
        }

        $amount = get_coupon_amount($coupon);
        $discount_amount_html = '-' . wc_price($amount);
        
        if ($coupon->get_free_shipping() && empty($amount)) {
            $discount_amount_html = __('Free shipping coupon', 'woocommerce');
        }

        $discount_amount_html = apply_filters('woocommerce_coupon_discount_amount_html', $discount_amount_html, $coupon);
        
        $url = add_query_arg('remove_coupon', rawurlencode($coupon->get_code()), wc_get_checkout_url());

        $coupon_html  = $discount_amount_html;
        $coupon_html .= ' <a href="'; 
        $coupon_html .= esc_url($url);
        $coupon_html .= '" class="woocommerce-remove-coupon" data-coupon="'; 
        $coupon_html .= esc_attr($coupon->get_code());
        $coupon_html .= '" data-nonce="';
        $coupon_html .= wp_create_nonce("remove_coupon_link_nonce") . '">'; 
        $coupon_html .= __('[Remove]', 'woocommerce'); 
        $coupon_html .= '</a>';

        echo wp_kses(apply_filters('woocommerce_cart_totals_coupon_html', $coupon_html, $coupon, $discount_amount_html), array_replace_recursive(wp_kses_allowed_html('post'), array('a' => array('data-coupon' => true, 'data-nonce' => true)))); // phpcs:ignore PHPCompatibility.PHP.NewFunctions.array_replace_recursiveFound
    }

    function get_coupon_amount(WC_Coupon $coupon) {
        $discount_type = $coupon->get_discount_type();       

        if ($discount_type === 'percent') {
            $coupon_code = $coupon->get_code();
            $display_cart_ex_tax = WC()->cart->display_cart_ex_tax;
            $amount = WC()->cart->get_coupon_discount_amount($coupon_code, $display_cart_ex_tax);
        } else {
            $amount = $coupon->get_amount();
        }

        return $amount;
    }

    add_filter('wc_add_to_cart_message', 'change_added_to_cart_notice', 10, 2);

    function change_added_to_cart_notice($message, $product_id) {
        if (is_order_approval()) {
            $message = get_message_box_html('Product added to order', 'check-circle');
        } 

        return $message;
    }

    function get_add_products_url($order_id) {
        return esc_url(home_url()) . '/checkout/add-products/?flow=order-approval&order=' . esc_attr($order_id);
    } 

    function get_order_review_url($order_id) {
        return esc_url(home_url()) . '/checkout/?flow=order-approval&order=' . esc_attr($order_id);
    }

    add_action('woocommerce_before_delete_order_item', 'delete_fee_references', 10, 1);

    function delete_fee_references($item_id) { 
        global $wpdb;

        $table = $wpdb->prefix . "woocommerce_order_items";
        $meta_table = $wpdb->prefix . "woocommerce_order_itemmeta";

        $sql = $wpdb->prepare(
            "SELECT order_item_type 
             FROM $table 
             WHERE order_item_id = %d", $item_id
        );

        $results = $wpdb->get_results($sql , ARRAY_A);

        if (!is_null($results) &&
            !empty($results[0]) &&
            isset($results[0]['order_item_type']) &&
            $results[0]['order_item_type'] == 'fee') {
                $sql = $wpdb->prepare(
                    "SELECT meta_value 
                    FROM $meta_table 
                    WHERE meta_key = '_fee_for_item'
                    AND order_item_id = %d", $item_id
                );

                $parent_item_results = $wpdb->get_results($sql , ARRAY_A);

                if (!is_null($parent_item_results) &&
                    !empty($parent_item_results[0]) &&
                    isset($parent_item_results[0]['meta_value'])) {
                        $parent_item = new WC_Order_Item_Product($parent_item_results[0]['meta_value']);
                        $fee = new Order_Item_Fee($parent_item);

                        $fee->set_fee_id($item_id);
                        $fee->delete_reference_from_parent_item();
                }
        }
    }

    function load_exit_modal() {
        if (is_order_approval()) {
            $modal = new eCard\Modal();

            $modal->set_class('modal-exit-approval-flow');
            $modal->set_title('You will exit the approval process. Proceed?');
            $modal->set_content('<p>You can always follow the approval link in your email to return.</p>');
            $modal->set_status_animation(true);
            $modal->set_button_text('confirm', 'Proceed');
            $modal->set_nonce(wp_create_nonce("exit_the_approval_flow_nonce"));
            
            echo $modal->generate();
        }
    }

    add_action('wp_footer', 'load_exit_modal');

    function parse_forwarding_url($url_array) {
        $site_url = get_site_url();
        $parsed_forwarding_url = '';

        if (isset($url_array['scheme'], $url_array['host'])) {
            if (!in_array($url_array['scheme'], array('http', 'https'))) {
                return false;
            }

            $forwarding_scheme_host = $url_array['scheme'] . '://' . $url_array['host'];

            if (strpos($site_url, $forwarding_scheme_host) === 0) {
                $parsed_forwarding_url = $forwarding_scheme_host;

                if (isset($url_array['path']) && strpos($url_array['path'], '/') === 0) {
                    $parsed_forwarding_url .= $url_array['path'];
                }

                return $parsed_forwarding_url;
            }
        } else {
            if (isset($url_array['path']) && strpos($url_array['path'], '/') === 0) {
                return $site_url . $url_array['path'];
            }
        }

        return false;
    }

    function save_order_addresses_from_post($order_id) {
        $addresses = array();
        $addresses['billing'] = get_order_address_from_post();
        $addresses['shipping'] = get_order_address_from_post('shipping');
        $order = wc_get_order($order_id);

        $order = save_order_addresses($order, $addresses);
        $order = process_order_taxes($order);

        return $order;
    }

    function save_total_taxes_to_wp_orders($order_id, $total_taxes) {
        global $wpdb;

        $table = $wpdb->prefix . 'orders';
        $rows_affected = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET order_tax = %f 
                WHERE post_id = %d;",
                $total_taxes, $order_id
            )
        );

        return $rows_affected;
    }

    /* 
     * Runs on each refresh of the order review sidebar.
     */
    function process_updated_order_review_data($data) {
        save_changed_order_address_parts($data);
    }

    add_action('woocommerce_checkout_update_order_review', 'process_updated_order_review_data', 10, 1);

    /**
     * Approval Page shortcode.
     *
     * This function will generate shortcode for the proof approval page.
     * file from the templates/folder.
     */
    function order_approval_page_shortcode() {
        include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/index.php';
    }

    add_shortcode('proof_approval', 'order_approval_page_shortcode');

    function get_approval_page_need_to_submit_changes_href() {
        $href = '';
        $allowed_parameters = array(
            'order' => '',
            't' => '',
        );

        foreach ($allowed_parameters as $parameter_key => $parameter_value) {
            if (isset($_REQUEST[$parameter_key])) {
                $allowed_parameters[$parameter_key] = filter_var($_REQUEST[$parameter_key], FILTER_SANITIZE_STRING);
            }
        }

        if ($allowed_parameters['order'] !== '') {
            $href .= home_url('approval-page/?order=' . $allowed_parameters['order']);

            if ($allowed_parameters['t'] !== '') {
                $href .= '&t=' . $allowed_parameters['t'];
            }

            $href .= '&intent=changes';
        }

        return $href;
    }

    function get_order_id_from_token($token_string) {
        $order_id = base64_decode($token_string);

        if (!is_numeric($order_id)) {       
            $order_token_manager = new eCard\Order_Token_Manager();
            $order_id = $order_token_manager->get_order_id_from_db($token_string);
        }

        return $order_id;
    }
