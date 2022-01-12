<?php
    define ('ORDER_APPROVAL_EXTENSION_URI',  ECARD_INCLUDES_URI . 'order-approval-extension/');
    define ('ORDER_APPROVAL_EXTENSION_PATH',  ECARD_INCLUDES_PATH . 'order-approval-extension/');
    define ('ORDER_APPROVAL_EXTENSION_VERSION', '1.3.4');

    ecard_require_module('modal');

    include 'includes/ajax.php';
    include 'includes/functions.php';
    include 'includes/layout.php';
    include 'includes/classes/class-order-review-product-categories-widget.php';

    function enqueue_order_approval_scripts() {
      if (is_order_approval()) {
        wp_register_style('order-approval-flow', ORDER_APPROVAL_EXTENSION_URI . 'css/styles.css', false, '5.5.1' . time() . mt_rand());
        wp_enqueue_style('order-approval-flow');
        wp_enqueue_script('order-approval-flow-scripts', ORDER_APPROVAL_EXTENSION_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);

        wp_localize_script(
          'order-approval-flow-scripts',
          'templates',
          array(
            'messageBox' => get_message_box_html()
          )
        );

        if (is_product()) {
          wp_localize_script(
            'order-approval-flow-scripts',
            'orderApprovalData',
            array(
              'singleNonce' => wp_create_nonce("add_product_item_to_order_nonce")
            )
          );
        }
      }
    }

    add_action('wp_enqueue_scripts', 'enqueue_order_approval_scripts');
