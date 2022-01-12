<?php 
    $back_button_href  = home_url();
    $back_button_href .= '/checkout/?flow=order-approval';

    if (isset($_GET['order'])) {
        $order_id = filter_var($_GET['order'], FILTER_SANITIZE_NUMBER_INT); 
        $back_button_href .= '&order=' . $order_id;
    }
?>

<div class="add-products-buttons floating-buttons">
    <div class="container">
        <a href="<?php echo esc_url($back_button_href); ?>" class="button-back-to-order-review button">
            Review Order
        </a>

        <?php if (is_product()): ?>
        <a href="<?php echo get_add_products_url($order_id); ?>" class="button-add-more-products button">
            <span class="text-desktop">Add More Products</span>
            <span class="text-mobile">Add Products</span>
        </a>
        <?php endif; ?>
        <input type="button" id="approval-flow-proceed-to-checkout" class="button" value="Continue To Checkout" data-order="<?php echo esc_attr($order_id); ?>" data-nonce="<?php echo wp_create_nonce("approval_flow_proceed_to_checkout_nonce"); ?>">
    </div>
    <?php if (is_product()): ?>
        <div class="single-product-message-window"></div>
    <?php endif; ?>
</div>