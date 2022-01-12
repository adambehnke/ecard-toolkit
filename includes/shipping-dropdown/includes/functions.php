<?php

function add_shipping_select($item_id, $item) {
    if (isset($_GET['shipping_select']) && 
        $_GET['shipping_select'] == 1 &&  
        is_object($item) &&
        is_a($item, 'WC_Order_Item_Shipping')) {
            $html = get_shipping_dropdown_html($item->get_order_id());
            echo $html;
        }
}

add_action('woocommerce_before_order_itemmeta', 'add_shipping_select', 10, 2); 

function get_shipping_dropdown_html($order_id, $current = 'default') {
    $html = '';
    $order = new WC_Order($order_id);
    $shipping_rates = get_shipping_rates_based_on_order($order);
    
    if (!empty($shipping_rates)) {
        $html = add_shipping_dropdown_elements($shipping_rates, $current);
    }
    
    return $html;
}

function get_shipping_rates_based_on_order(WC_Order $order) {
    global $woocommerce;

    $shipping_rates = array();
    $customer_id = $order->get_customer_id();

    $session_class = apply_filters('woocommerce_session_handler', 'WC_Session_Handler');
    $woocommerce->session = new $session_class();
    $woocommerce->cart = new WC_Cart();
    $woocommerce->cart->empty_cart();
    $woocommerce->customer = new WC_Customer($customer_id);

    //$tax_args = get_tax_args_from_post_address();
    $tax_args = $order->get_address('shipping');

    $woocommerce->customer->set_shipping_address_1($tax_args['address_1']);
    $woocommerce->customer->set_shipping_address_2($tax_args['address_2']);
    $woocommerce->customer->set_shipping_country($tax_args['country']);
    $woocommerce->customer->set_shipping_state($tax_args['state']);
    $woocommerce->customer->set_shipping_postcode($tax_args['postcode']);
    $woocommerce->customer->set_shipping_city($tax_args['city']);

    $order_items = $order->get_items();

    foreach ($order_items as $item) {
        $product_id = $item->get_product_id();
        $quantity = $item->get_quantity();
        $woocommerce->cart->add_to_cart($product_id, $quantity);
    }

    $woocommerce->shipping->reset_shipping();
    $woocommerce->cart->calculate_shipping();
    $woocommerce->cart->calculate_totals(true);

    $shipping = $woocommerce->session->get('shipping_for_package_0');

    if (!is_null($shipping)) {
        $shipping_rates = $shipping['rates'];
    }

    if (isset($shipping_rates['request_shipping_quote'])) {
        unset($shipping_rates['request_shipping_quote']);
    }

    return $shipping_rates;
}

function add_buttons_to_button_line($order) { 
    echo '<button type="button" class="button add-order-shipping-select">Add shipping</button>';
    echo '<button type="button" class="button save-action-custom">Save</button>';
}; 

function add_shipping_dropdown_elements($shipping_rates, $current = 'default') {
    $shipping_options = array();
    $default = 'Choose a shipping method';
    $html = '';

    if ($current === 'default') {
        $current = $default;
    }

    foreach ($shipping_rates as $rate) {
        $label      = $rate->get_label();
        $price      = $rate->get_cost();
        $text       = $label . ' - ' . wc_price($price);
        $method_id  = $rate->get_method_id();
        $id         = $rate->get_id();

        $shipping_options[$id] = array(
            'text' => $text,
            'attributes' => array(
                'label' => $label,
                'price' => $price,
                'method-id' => $method_id
            )
        );
    }

    $dropdown = new Order_Dropdown(array(
        'data' => $shipping_options,
        'current' => $current,
        'default' => $default,
        'attributes' => array( 
            'class' => 'shipping-options dropdown-options',
            'name' => 'shipping_options',
            'type' => 'shipping',
        )
    ));

    $html .= $dropdown->generate();

    return $html;
}

add_action('woocommerce_order_item_add_line_buttons', 'add_buttons_to_button_line', 99, 1);

function load_confirmation_modal() {
    if (is_shop_order_page()) {
        $modal = new eCard\Modal();

        $modal->set_title('This will remove the following shipping item(s). <br />Are you sure you want to proceed?');
        $modal->set_empty_content('shipping-items-to-delete items-to-delete', true);
        $modal->set_button_text('confirm', 'Remove');
        $modal->set_class('modal-confirmation modal-shipping-confirmation');
        
        echo $modal->generate();
    }
}

add_action('admin_footer', 'load_confirmation_modal');

/**
 * Recalculates the order shipping. Removes the current order shipping and 
 * adds back the same shipping method with the new price. Recalculates taxes.
 * 
 * @param WC_Order $order The order.
 * @return WC_Order $order The modified order.
 */
function recalculate_order_shipping(WC_Order $order) {
    $order->save();

    $shipping_recalculated = false;
    $shipping_rates = get_shipping_rates_based_on_order($order);
    $current_shipping_method = $order->get_shipping_method();

    foreach ($shipping_rates as $rate) {
        $label = $rate->get_label();

        if ($label === $current_shipping_method) {
            $order = save_chosen_shipping_method_for_order($order, $rate);
            $order->save();
            $shipping_recalculated = true;
        }
    }

    if ($shipping_recalculated) {
        $order = process_order_taxes($order);
        $order->calculate_totals(false);
    }

    return $order;
}
