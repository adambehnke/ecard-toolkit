<?php $order_id = filter_var($_GET['order'], FILTER_SANITIZE_NUMBER_INT); ?>
<div class="add-products-listing" data-order="<?php echo $order_id; ?>" data-nonce="<?php echo wp_create_nonce("add_product_item_to_order_nonce"); ?>">
    <?php 
        if (isset($_GET['cat'])) {
            $categories = filter_var($_GET['cat'], FILTER_SANITIZE_STRING);
        } else {
            $categories = 'stock-accessories,custom-accessories'; 
        }

        $shortcode = '[products category="' . $categories . '" paginate="true" limit="16"]';

        echo do_shortcode($shortcode);
    ?>
</div>

<?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/floating-buttons.php'; ?>
