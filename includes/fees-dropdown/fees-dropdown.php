<?php
    define ('FEES_DROPDOWN_URI',  ECARD_INCLUDES_URI . 'fees-dropdown/');
    define ('FEES_DROPDOWN_PATH',  ECARD_INCLUDES_PATH . 'fees-dropdown/');

    include 'includes/ajax.php';
    include 'includes/fee-post-type.php';
    include 'includes/functions.php';
    include 'includes/register-acf-fields.php';

    function add_fees_submenu_item() {
        add_submenu_page('edit.php?post_type=product', __('Fees', 'ecard'), __('Fees', 'ecard'), 'manage_options', 'edit.php?post_type=fee');
    }

    add_action('admin_menu', 'add_fees_submenu_item', 9);

    function enqueue_fees_admin_scripts() {
        $post_type = get_post_type();
        $pages = array('fee', 'shop_order');

        if (in_array($post_type, $pages)) {
            wp_register_style('boxshop-admin-fees', FEES_DROPDOWN_URI . 'css/styles.css', false, '5.5.1' . time() . mt_rand());
            wp_enqueue_style('boxshop-admin-fees');
            wp_enqueue_script('boxshop-admin-fees-scripts', FEES_DROPDOWN_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), false);
        }
    }

    add_action('admin_enqueue_scripts', 'enqueue_fees_admin_scripts');
