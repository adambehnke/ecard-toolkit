<?php
    define ('DROPDOWN_URI',  ECARD_INCLUDES_URI . 'dropdown/');
    define ('DROPDOWN_PATH',  ECARD_INCLUDES_PATH . 'dropdown/');

    include 'includes/classes/class-order-dropdown.php';

    function enqueue_dropdown_admin_scripts() {
        $post_type = get_post_type();
        $pages = array('shop_order');

        if (in_array($post_type, $pages)) {
            // Load common WC Order styles
            wp_register_style('boxshop-dropdown-styles', DROPDOWN_URI . 'css/styles.css', false, '5.5.1' . time() . mt_rand());
            wp_enqueue_style('boxshop-dropdown-styles');

            // Load common WC Order dropdown functionality
            wp_enqueue_script('boxshop-dropdown-scripts', DROPDOWN_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);

            // Register order dropdown types
            wp_localize_script(
                'boxshop-dropdown-scripts',
                'dropdownTypes',
                array(
                    'fees' => 'fee',
                    'shipping' => 'shipping'
                )
            );
        }
    }

    add_action('admin_enqueue_scripts', 'enqueue_dropdown_admin_scripts');
