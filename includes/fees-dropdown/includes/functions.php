<?php 

function add_fee_elements($item_id, $item) {
    global $post;

    $main_order_backup = $post;
    $fee_options = array();
    $html = '';
    $args = array(  
        'post_type' => 'fee',
        'post_status' => 'publish',
        'posts_per_page' => -1, 
        'orderby' => 'title', 
        'order' => 'ASC', 
    );

    $fees = new WP_Query($args); 
        
    while ($fees->have_posts()) {
        $fees->the_post(); 

        $id = get_the_id();
        $fee_options[$id] = array(
            'text' => get_the_title(),
            'attributes' => array(
                'action' => 'save'
            )
        );
    }

    $fee_options['custom'] = array(
        'text' => 'Custom fee',
        'attributes' => array(
            'action' => 'edit'
        )
    );

    $dropdown = new Order_Dropdown(array(
        'data' => $fee_options,
        'current' => 'Choose a fee',
        'default' => 'Choose a fee',
        'attributes' => array( 
            'class' => 'fee-options dropdown-options',
            'name' => 'fee_options',
            'order_item_id' => $item_id,
            'type' => 'fee'
        )
    ));

    $html .= $dropdown->generate();

    echo $html;

    wp_reset_postdata(); 

    $post = $main_order_backup;
}

add_action('woocommerce_after_order_fee_item_name', 'add_fee_elements', 10, 2);

function process_fees_in_order_totals_section($fee_data, $totals_lines, $order) {
    $fees_present = false;

    foreach ($totals_lines as $line) {
        if (strpos(strtolower($line), 'fees') !== false) {
            $fees_present = true;
        }
    }

    if (!$fees_present) {
        ob_start();
        include FEES_DROPDOWN_PATH . 'templates/totals-fees-row.php';
        $fee_data['totals_row_html'] = ob_get_clean();
    }
    
    return $fee_data;
}

function action_woocommerce_order_item_add_line_buttons($order) { 
    echo '<button type="button" class="button add-order-fee-select">Add fee</button>';
}; 
         
add_action('woocommerce_order_item_add_line_buttons', 'action_woocommerce_order_item_add_line_buttons', 99, 1);

function add_elements_to_line_item($item_id, $item, $product) {
    $linked_fees = get_item_linked_fees($item);
    
    if (!empty($linked_fees['linked_fees'])) {
        $linked_fees_json = json_encode($linked_fees);

        echo "<input type='hidden' class='linked-fees' value='" . $linked_fees_json . "' />";
    }
}

add_action('woocommerce_after_order_itemmeta', 'add_elements_to_line_item', 99, 3);

function add_linked_fee_class_to_item($class, $item, $order) { 
    $linked_fees = get_item_linked_fees($item);

    if (!empty($linked_fees['linked_fees'])) {
        $class .= ' has-linked-fees';
    }

    return $class; 
}; 

add_filter('woocommerce_admin_html_order_item_class', 'add_linked_fee_class_to_item', 10, 3); 

function get_item_linked_fees($item) {
    $linked_fees = array('linked_fees' => array());
    $meta = $item->get_meta_data();

    foreach ($meta as $meta_item) {
        $meta_item_data = $meta_item->get_data();

        if (strpos($meta_item_data['key'], 'linked_fee') !== false) {
            $linked_fees['linked_fees'][] = $meta_item_data['value'];
        }
    }

    return $linked_fees;
}