<?php
/**
 * Approve proof template.
 *
 * @author      Chetu
 * @package 	WooCommerce Plugin Templates/Templates
 * @version     1.0.0
 */
if ( !defined( 'ABSPATH' ) )
    exit; // Don't allow direct access

if ( isset( $_REQUEST['order'] ) ) {
    global $wpdb;

    $token_string = filter_var($_REQUEST['order'], FILTER_SANITIZE_STRING);
    $order_id = get_order_id_from_token($token_string);

    /*
    $order_id = base64_decode($token_string);

    if (!is_numeric($order_id)) {       
        $order_token_manager = new eCard\Order_Token_Manager();
        $order_id = $order_token_manager->get_order_id_from_db($token_string);
    }
    */
    
    $intent = 'approval';
    
    $check_order_status = get_post_meta( $order_id, '_order_status', true );
    $check_order_status_number = (int)str_replace('om_', '', $check_order_status);

    if ($check_order_status_number >= 12) {
        header('Location: ' . get_site_url() . '/thankyou-page?success=error');
    }

    if (isset($_REQUEST['intent'])) {
        $intent = filter_var($_REQUEST['intent'], FILTER_SANITIZE_STRING);
    }

    if (isset($check_order_status)) {
        $current_status = "SELECT order_status_name FROM {$wpdb->prefix}om_order_status WHERE order_status_id in (" . intval(preg_replace("/[^0-9]/", "", $check_order_status )). ")";
        $current_status = $wpdb->get_results($current_status, ARRAY_N);

        if (isset($current_status) && isset($current_status[0]) && isset($current_status[0][0])) {
            $current_status = $current_status[0][0];
        } else {
            $current_status = '1';
        }
    }

    $order = wc_get_order( $order_id );   

    if (!$order) {
        echo '<p>There has been a problem retrieving the order. Please contact customer service.</p>';

        exit;
    } else {
        $items = $order->get_items();
    }

    $payment_page = $order->get_checkout_payment_url(); // to get payment url page
    // scan db from proofs
    $select_proofs = "SELECT meta_key FROM {$wpdb->prefix}postmeta where post_id in ({$order_id}) and meta_key like '%_proof_%'";
    $meta_proofs = $wpdb->get_results($select_proofs, ARRAY_N);
    // reformat results into new array
    $proof_array = array();

    foreach ($meta_proofs as $key => $value) {
        preg_match('/_proof_(.*)_/', $value[0], $matches);

        if ($matches) {
            $item_id = $matches[1];
            $item = false;

            foreach ($items as $order_item_id => $order_item) {
                if ($item_id == $order_item_id) {
                    $item = $order_item;
                }
            }

            if ($item) {
                $product_id = $item->get_product_id();
                $product = new WC_Product($product_id);
                $product_status = $product->get_status();
    
                if ($product_status !== false && $product_status !== 'trash') {
                    $proof_array[] = $value[0];
                }
            }
        }
    }

    ?>
    <link rel="stylesheet" href="<?php echo ORDER_APPROVAL_EXTENSION_URI . 'css/approval-page.css?v=' . ORDER_APPROVAL_EXTENSION_VERSION; ?>">
        <div id="primary" class="content-area approval-page"> 
            <main id="main" class="site-main" role="main">
                <div class="headingnew"><h4>View Proof</h4></div>
                <div class="sectionnew approval-page-left">
                    <?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/hidden-fields.php'; ?>                     

                    <?php if ($intent === 'approval'): ?>
                        <?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/approval-controls.php'; ?>   
                    <?php else: ?>   
                        <h3>Your eCard proof</h3>              
                    <?php endif; ?>

                    <div class="sorting">
                        <?php 
                            if ($intent === 'changes') {
                                $section_title = 'Your eCard Proof';
                            } else {
                                $section_title = 'Proof Correct?';
                            }
                        ?>
                        <h3><?php $section_title; ?></h3>
                        <div class="proofs">
                            <?php foreach ($proof_array as $key => $proof_key): ?>
                            <?php                                
                                $proof_approval_url = get_post_meta($order_id, "{$proof_key}", true);
                                $proof_approval_url_ext = substr(strrchr($proof_approval_url,'.'),1);
                            ?>  
                                <?php if (strtolower($proof_approval_url_ext) === "pdf"): ?>
                                    <object data="<?php echo $proof_approval_url . '#toolbar=0&navpanes=0&scrollbar=0'; ?>" type="application/pdf" width="465" height="600">
                                        alt : <a href="<?php echo $proof_approval_url; ?>">https://staging.ecardsystems.net/wp-content/plugins/order-management/assets/images/ecslogo.jpg</a>
                                    </object>
                                <?php else: ?>
                                    <img src="<?php echo $proof_approval_url; ?> ">

                                    <p class="approval-proof-larger-version">
                                        View larger version of proof<br />
                                        <a href="<?php echo $proof_approval_url; ?>" target="_blank"><?php echo $proof_approval_url; ?> </a>
                                    </p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($intent === 'approval'): ?>
                        <div class="visible">
                            <a href="<?php echo get_approval_page_need_to_submit_changes_href(); ?>" class="btn btn-green">I need to submit changes</a>
                        </div>     
                    <?php elseif ($intent === 'changes'): ?>
                        <?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/edit-section.php'; ?>                    
                        <?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/button-submit-changes.php'; ?>
                        <?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/approval-controls.php'; ?>                    
                    <?php endif; ?>

                    <?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/disclaimer.php'; ?>
                    
                </div>

                <?php include ORDER_APPROVAL_EXTENSION_PATH . 'templates/approval-page/sidebar.php'; ?>

                
            </main><!-- #main -->
        </div><!-- #primary -->
        <?php
} else {
    echo "Approval Page Coming Soon.";
}
