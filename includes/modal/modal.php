<?php
    define ('MODAL_URI',  ECARD_INCLUDES_URI . 'modal/');
    define ('MODAL_PATH',  ECARD_INCLUDES_PATH . 'modal/');

    include MODAL_PATH . 'includes/classes/class-modal.php';

    function enqueue_modal_assets() {
        wp_register_style('modal-ecard', MODAL_URI . 'css/styles.css', false, '5.5.1' . time() . mt_rand());
        wp_enqueue_style('modal-ecard');
        wp_enqueue_script('modal-ecard-scripts', MODAL_URI . 'js/scripts.js', array('jquery'), '5.5.1' . time() . mt_rand(), true);
    }

    add_action('admin_enqueue_scripts', 'enqueue_modal_assets');
    add_action('wp_enqueue_scripts', 'enqueue_modal_assets');
