<?php
    function get_order_item_edit_html($product_id, $item) {
        $html  = '';
        $html .= get_quantity_select_html($product_id, $item);
        $html .= get_attributes_select_html($product_id, $item);

        return $html;
    }

    function get_quantity_select_html($product_id, $item) {
        $html = '';
        $flat_currency = new Flat_Currency();
        $quantities = $flat_currency->get_quantities_prices_based_on_product_id($product_id);
        $store_currency_lower = '__' . strtolower(get_woocommerce_currency());
        $store_currency_symbol = get_woocommerce_currency_symbol();
        $current_quantity = $item->get_quantity();
        $item_totals = get_order_item_totals($item);

        foreach ($quantities as $key => $quantity) {
            $quantities[$key]['quantity'] = $quantity['sleeves'][0];
        }

        if (isset($quantities[$store_currency_lower])) {		
            $html .= '<div class="order-review-edit-container shown-active shown-saving">';
            $html .= '<label>';
            $html .= 'Quantity';
            $html .= '</label>';
            $html .= '<select class="order-review-edit order-review-quantity-edit" data-current="' . esc_attr($current_quantity) . '">';

            foreach($quantities[$store_currency_lower]['quantity'] as $key => $quantity) {
                $price = $quantity['price'];

                if ($quantity['sleeve'] == $current_quantity) {
                    $selected = ' selected';

                    if ($item_totals['discount'] > 0) {
                        continue;
                    }
                } else {
                    $selected = '';
                }
                $html .= '<option value="' . $quantity['sleeve'] . '"' . $selected;
                $html .= ' data-price="' . $price . '">';
                $html .= $quantity['sleeve'] . '-' . $store_currency_symbol . $price;
                $html .=  '</option>';
            }

            $html .= '<select>';
            $html .= '</div>';

        }

        return $html;
    }

    function get_attributes_select_html($product_id, $item) {
        $html = '';
        $product = wc_get_product($product_id);
        $attributes = $product->get_attributes();
        $attributes_array = array('finish' => 'Finish', 'production-time' => 'Production Time');
        $item_meta = $item->get_meta_data();

        foreach ($item_meta as $data) {
            $meta_key = Order_Key_Converter::convert($data->key);
			$meta_pairs[$meta_key] = $data->value;
        }
        
        foreach ($attributes_array as $key => $name) {
            $current = '';
            $dropdown_attributes = array();

            if ($key === 'finish') {
                $current = isset($meta_pairs['_finish']) ? $meta_pairs['_finish'] : '';
            } elseif ($key === 'production-time') {
                $current = isset($meta_pairs['_production_time']) ? $meta_pairs['_production_time'] : '';

                if (isset($meta_pairs['_linked_fee_production_time'])) {
                    $dropdown_attributes['data-item-id'] = intval($meta_pairs['_linked_fee_production_time']);
                }
            }

            if (isset($attributes[$key])) {
                $html .= get_attribute_dropdown_html($attributes[$key], $current, $name, $dropdown_attributes);
            }
        }
        
        return $html;
    }

    function get_attribute_dropdown_html($attributes_object, $current, $label, $dropdown_attributes = array()) {
        $html = '';
        $data = $attributes_object->get_data();
        $class_key = strtolower($label);
        $class_key = str_replace(' ', '-', $class_key);
        $name_key = str_replace('-', '_', $class_key);
        $dropdown_attributes = array_merge($dropdown_attributes, array(
            'class' => 'order-review-edit order-review-' . $class_key . '-edit',
            'name' => 'order_review_' . $name_key . '_edit',
            'data-current' => $current
        ));

        $dropdown = new Order_Dropdown(array(
            'label' => $label,
            'data' => $data['options'],
            'current' => $current,
            'attributes' => $dropdown_attributes
        ));

        $html .= '<div class="order-review-edit-container shown-active shown-saving">';
        $html .= $dropdown->generate(true);
        $html .= '</div>';

        return $html;
    }

    function product_is_in_addon_categories($product_id, $addon_page_id) {
        $product_categories = get_the_terms($product_id, 'product_cat');
        $addon_categories = get_the_terms($addon_page_id, 'product_cat');

        foreach ($product_categories  as $product_category) {
            foreach ($addon_categories as $addon_category) {
                if ($product_category->term_id === $addon_category->term_id) {
                    return true;
                }
            }
        }

        return false;
    }

    function get_order_review_controls_html($order_id) {
        $add_products_url = get_add_products_url($order_id);
        
        $html  = '';
        $html .= '<span class="order-review-quantity-control hidden-active hidden-saving" id="order-review-quantity-control-edit" data-action="edit">Edit Items</span>';
        $html .= '<span class="order-review-quantity-control hidden-active hidden-saving has-status-animation" id="order-review-quantity-control-add" data-action="add" data-url="' . $add_products_url . '"><i class="fa fa-asterisk icon-spinner"></i><span>Add Products</span></span>';
		$html .= '<span class="order-review-quantity-control shown-active hidden-saving" id="order-review-quantity-control-save" data-action="save" data-nonce="' . wp_create_nonce("persist_order_review_data_nonce") .'">Save Items</span>';
        $html .= '<span class="order-review-quantity-control shown-saving" id="order-review-quantity-control-saving"><i class="fa fa-asterisk icon-spinner"></i>Saving order, please wait</span>';

        return $html;
    }

    function get_message_box_html($message = '', $icon = '') {
        $defaults = array(
            'message' => '{{MESSAGE}}',
            'icon' => '{{ICON}}'
        );

        if ($message !== '') {
            $defaults['message'] = esc_html($message);
        }

        if ($icon !== '') {
            $defaults['icon'] = esc_attr($icon);
        }

        $html = '';
        $html .= '<div class="message">';
        $html .= '<div class="message-inner">';
        $html .= '<i class="fa fa-' . $defaults['icon'] . '" aria-hidden="true"></i><span>' . $defaults['message'] . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function add_coupons_form_to_the_order_review_sidebar() {
        if (is_order_approval()) {
            ob_start();
            include ORDER_APPROVAL_EXTENSION_PATH . '/templates/coupon-form.php';
            $html = ob_get_contents();
            ob_end_clean();
    
            echo $html;
        }
    }

    add_action('woocommerce_review_order_before_submit', 'add_coupons_form_to_the_order_review_sidebar', 10, 0); 

    function get_order_approval_flow_checkout_button_html($paid_order_id) {
        $html  = '';
        $html .= '<span id="order_approval_pay_button" class="button has-status-animation"><i class="fa fa-asterisk icon-spinner"></i><span>Continue to Checkout</span></span>';
        $html .= "<script type='text/javascript'>
                    admin_url_order_update = '" . admin_url('admin-ajax.php') . "';
                    order_id_update_existing = $paid_order_id;
                        jQuery(document).ready(function(){
                            jQuery('.wc_payment_methods.payment_methods, .shopping-cart-wrapper').hide();
                        });
                   </script>";
        $html .= wp_nonce_field('order_approval_flow_address_saving', 'order_approval_flow_address_saving_nonce');
        
        return $html;
    }

    function get_order_approval_delete_order_item_modal_html() {
        $modal = new eCard\Modal();

        $modal->set_class('modal-confirmation modal-delete-order-item');
        $modal->set_title('This will remove the following order item. <br />Are you sure you want to proceed?');
        $modal->set_empty_content('order-item-to-delete items-to-delete', true);
        $modal->set_status_animation(true);
        $modal->set_button_text('confirm', 'Remove');
        $modal->set_nonce(wp_create_nonce("delete_order_item_nonce"));
        $modal->set_hidden_field('item_to_delete', '');
            
        echo $modal->generate();
    }

    add_action('woocommerce_after_checkout_form', 'get_order_approval_delete_order_item_modal_html' );
