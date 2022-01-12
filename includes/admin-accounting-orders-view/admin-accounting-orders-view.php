<?php
define ('ADMIN_ACCOUNTING_ORDERS_VIEW_URI',  ECARD_INCLUDES_URI . 'admin-accounting-orders-view/');
define ('ADMIN_ACCOUNTING_ORDERS_VIEW_PATH',  ECARD_INCLUDES_PATH . 'admin-accounting-orders-view/');

add_filter( 'manage_edit-shop_order_columns', 'prepare_accounting_orders_view', 20 );

function prepare_accounting_orders_view( $columns ) {
    $user = new OM\User(get_current_user_id());

    if ($user->has_clearance_for_level(1)) {
        $columns = unset_columns( $columns );
        $columns = add_accounting_columns( $columns );

        return $columns;
    }

    return $columns;
}

function add_accounting_columns( $columns ) {
    $user = new OM\User(get_current_user_id());

    if ($user->has_clearance_for_level(1)) {
        $new_columns = array();

        $new_columns['cb'] = __( '', 'ecard' );
        $new_columns['order_number'] = __( 'Order ID', 'ecard' );
        $new_columns['order_company'] = __( 'Company', 'ecard' );
        $new_columns['order_status'] = __( 'Order Status', 'ecard' );
        $new_columns['order_payment_status'] = __( 'Payment Status', 'ecard' );
        $new_columns['order_commission_status'] = __( 'Commission Status', 'ecard' );
        $new_columns['order_partner'] = __( 'Partner', 'ecard' );
        $new_columns['order_date'] = __( 'Date', 'ecard' );
        $new_columns['order_paid'] = __( 'Paid', 'ecard' );
        $new_columns['order_payment_method'] = __( 'Payment Method', 'ecard' );
        $new_columns['order_total'] = __( 'Total $', 'ecard' );
        $new_columns['mw_qbo_desktop_inv_status'] = __( 'QBD Status', 'ecard' );

        return $new_columns;
    } else {
        return $columns;
    }
}

function unset_columns( $columns ) {
    unset($columns['order_status']);
    unset($columns['order_date']);
    unset($columns['exported']);
    unset($columns['order_total']);

    return $columns;
}

function add_accounting_column_content( $column ) {
    global $post;

    $current = '';
    $meta = get_post_meta($post->ID);
    $order_icons = new Order_Icons($post->ID);
    $order_payment_status = new Order_Payment_Status($post->ID);
    $select_data = array(
        'order_payment_status' => PAYMENT_STATUS,
        'order_commission_status' => COMMISSION_STATUS
    );

    if ($column === 'order_company' && isset($meta['_billing_company'])) {
        echo $meta['_billing_company'][0];
    }

    if ($column === 'order_payment_method' && isset($meta['_payment_method'])) {
        echo $meta['_payment_method'][0];
    }

    if (array_key_exists($column, $select_data)) {
        $class = str_replace('_', '-', $column) . '-list enqueue-for-save';

        if (isset($meta['_'. $column], $meta['_'. $column][0])) {
            $current = $meta['_'. $column][0];
        }

        $dropdown = new Order_Dropdown(array(
            'data' => json_decode($select_data[$column], true),
            'current' => $current,
            'attributes' => array( 
                'class' => $class,
                'name' => $column,
                'data-current' => $current
            )
        ));

        echo $dropdown->generate();
    }

    if ($column === 'order_paid') {
        $html = '';

        if (isset($meta['_order_payment_status'], $meta['_order_payment_status'][0]) &&
            $meta['_order_payment_status'][0] !== '') {
            $icon = $order_payment_status->get_status_icon($meta['_order_payment_status'][0]);
            $html = $order_icons->get_data_graphics_image($icon);
        }

        echo $html;
    }

    if ($column === 'order_partner') {
        echo $order_icons->get_order_partner_icons($post->ID);
    }
}

add_action( 'manage_shop_order_posts_custom_column', 'add_accounting_column_content' );

function enqueue_accounting_admin_scripts() {
    global $pagenow;

    $current_user = new OM\User(get_current_user_id());

    if ($current_user->has_clearance_for_level(1)) {
        wp_register_style( 'boxshop-admin-accounting', ADMIN_ACCOUNTING_ORDERS_VIEW_URI . 'css/styles.css', false, '5.5.1' . time() . mt_rand() );
        wp_enqueue_style( 'boxshop-admin-accounting' );
        wp_enqueue_script( 'admin-accounting-orders-view', ADMIN_ACCOUNTING_ORDERS_VIEW_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true );
    }
}

add_action( 'admin_enqueue_scripts', 'enqueue_accounting_admin_scripts' );

function custom_woocommerce_admin_order_buyer_name($buyer, $object) { 
    $current_user = new OM\User(get_current_user_id());

    if ($current_user->has_clearance_for_level(1)) {
        return '';
    }
} 
 
add_filter('woocommerce_admin_order_buyer_name', 'custom_woocommerce_admin_order_buyer_name', 10, 2);

function payment_completed($order_id) {
    update_post_meta($order_id, '_order_payment_status', 'P');
}

add_action('woocommerce_payment_complete', 'payment_completed', 10, 1);

function add_admin_body_class( $classes ) {
    $current_user = new OM\User(get_current_user_id());

    if ($current_user->has_clearance_for_level(1)) {
        $classes .= ' is-user-level-1';
    }

    return $classes;
}

add_filter( 'admin_body_class', 'add_admin_body_class' );

/*
function update_orders_statuses_from_list() {
    global $wpdb;

    try {
        if (isset($_POST['order_payment_statuses'])) {
            foreach ($_POST['order_payment_statuses'] as $order_id => $status) {
                $status_name = $this->get_order_status_name($order_id);
                $order_payment_status = new Order_Payment_Status($order_id);
                $old_payment_status_key = $order_payment_status->get_meta_status(true);
                $new_payment_status_key = sanitize_text_field(wp_unslash($status));
                $old_payment_status_text = $order_payment_status->get_status_name($old_payment_status_key);
                $new_payment_status_text = $order_payment_status->get_status_name($new_payment_status_key);

                $order = new WC_Order($order_id);
                $order->update_status($new_payment_status_text);

                update_post_meta($order_id, '_order_payment_status', $new_payment_status_key);
                insert_event_log($order_id, $status_name, '53', $old_payment_status_text, $new_payment_status_text, 'Orders-uot-payment');
            }
        }


        if (isset($_POST['order_commission_statuses'])) {
            foreach ($_POST['order_commission_statuses'] as $order_id => $status) {
                $status_name = $this->get_order_status_name($order_id);
                $commission_status_obj = json_decode(COMMISSION_STATUS);
                $old_commission_status_key = get_post_meta($order_id, '_order_commission_status', true);
                $new_commission_status_key = sanitize_text_field(wp_unslash($status));
                $old_commission_status_text = $commission_status_obj->$old_commission_status_key;
                $new_commission_status_text = $commission_status_obj->$new_commission_status_key;

                update_post_meta($order_id, '_order_commission_status', $new_commission_status_key);
                insert_event_log($order_id, $status_name, '52', $old_commission_status_text, $new_commission_status_text, 'Orders-uot-commission');
            }
        }

        echo 1;

        die;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

add_action('wp_ajax_update_orders_statuses_from_list', 'update_orders_statuses_from_list');
*/
