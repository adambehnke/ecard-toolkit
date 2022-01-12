<?php

/**
 * The plugin functions file
 *
 * This file is used by WordPress to hold all toolkit-specific shared functions.
 *
 * @link              https://ecardsystems.com
 * @since             1.0.0
 * @package           Ecard
 *
 * @wordpress-plugin
 * Plugin Name:       eCard Toolkit
 * Plugin URI:        https://ecardsystems.com
 * Description:       Custom function store.
 * Version:           1.0.0
 * Author:            eCardSystems
 * Author URI:        https://ecardsystems.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ecard
 * Domain Path:       /languages
 */

define ('ECARD_INCLUDES_PATH',  plugin_dir_path( __FILE__ ));
define ('ECARD_INCLUDES_URI', plugin_dir_url(__FILE__));

if (!function_exists('ecard_get_environment')) {
    function ecard_get_environment() {
        $env = '';
    
        if (strstr($_SERVER['HTTP_HOST'], 'dev')) {
          return 'dev';
        } elseif (strstr($_SERVER['HTTP_HOST'], 'stage')) {
          return 'stage';
        } elseif (strstr($_SERVER['HTTP_HOST'], 'www')) {
          return 'prod';
        }
    
        return 'prod';
    }
}

function ecard_require_module($module) {
  require_once ECARD_INCLUDES_PATH . $module . '/' . $module . '.php';
}

  function remove_order_shipping($order) {
        $shipping_items = $order->get_items('shipping');

        foreach ($shipping_items as $shipping_item_id => $shipping_item) {
            $order->remove_item($shipping_item_id);
        }

        return $order;
    }

    function set_chosen_shipping_method_session_key($order) {
        $chosen_shipping_method = get_chosen_shipping_method_from_plugin_file($order);

        if (!empty($chosen_shipping_method) && isset($chosen_shipping_method['key'])) {
            WC()->session->set('chosen_shipping_methods', array($chosen_shipping_method['key']));
        }

        /*
        $file = WP_PLUGIN_DIR . '/fedex-woocommerce-shipping/includes/data-wf-service-codes.php';

        if (file_exists($file)) {
            $chosen_shipping_method = array();
            $chosen_shipping_method['name'] = $order->get_shipping_method();
            $shipping_methods = include($file);
    
            foreach ($shipping_methods as $method_key => $method_name) {
                if ($chosen_shipping_method['name'] === $method_name) {
                    $chosen_shipping_method['key'] = get_complete_shipping_method_key($method_key);
                }
            }
    
            if (isset($chosen_shipping_method['key'])) {
                WC()->session->set('chosen_shipping_methods', array($chosen_shipping_method['key']));
            }
        }
        */
    }
    
    function get_chosen_shipping_method_from_plugin_file($order) {
        $chosen_shipping_method = array();
        $file = WP_PLUGIN_DIR . '/fedex-woocommerce-shipping/includes/data-wf-service-codes.php';

        if (file_exists($file)) {
            $chosen_shipping_method = array();
            $chosen_shipping_method['name'] = $order->get_shipping_method();
            $shipping_methods = include($file);
    
            foreach ($shipping_methods as $method_key => $method_name) {
                if ($chosen_shipping_method['name'] === $method_name) {
                    $chosen_shipping_method['key'] = get_complete_shipping_method_key($method_key);
                }
            }
    
            if (isset($chosen_shipping_method['key'])) {
                WC()->session->set('chosen_shipping_methods', array($chosen_shipping_method['key']));
            }
        }

        return $chosen_shipping_method;
    }

    function get_complete_shipping_method_key($method_key) {
        return 'wf_fedex_woocommerce_shipping:' . $method_key;
    }

    function get_error_message($type) {
        return 'There has been an error retrieving the ' . $type .' data';
    }

    function get_currencies() {
        global $wpdb;

        $currency_list = $wpdb->get_results( "select distinct currency_code, currency_name from {$wpdb->prefix}ecs_currencies", ARRAY_A );

        return $currency_list;
    }

    function require_module($module) {
        require_once INCLUDES_PATH . $module . '/' . $module . '.php';
    }

    function is_shop_order_page() {
        $post_type = get_post_type();
        $pages = array('shop_order');

        if (in_array($post_type, $pages)) {
            return true;
        }

        return false;
    }

    function get_price_per_item_from_flat_price_data($flat_price_data = array(), $item_quantity) {
        $price_per_item = -1.00;

        if (!empty($flat_price_data)) {
            // Account for duplicate DB entries, with the first one empty.
            if (count($flat_price_data) > 1 && empty($flat_price_data[0]) && !empty($flat_price_data[1])) {
                $flat_price_data = $flat_price_data[1];
            } elseif (!empty($flat_price_data[0])) {
                $flat_price_data = $flat_price_data[0];
            }

            $price_per_item = (float)$flat_price_data[0]['price'] / (float)$flat_price_data[0]['sleeve'];

            foreach ($flat_price_data as $key => $data) {
                if ($item_quantity >= (int)$data['sleeve']) {
                    $price_per_item = (float)$data['price'] / (float)$data['sleeve'];
                }
            }
        }

        return $price_per_item;
    }

    function get_currency_db_key($currency) {
        if ($currency) {
            $key = '__' . strtolower($currency);
        } else {
            $key = '__usd';
        }

        return $key;
    }

    /*
    function get_usd_to_currency_exchange_rate($currency) {
        $exchange_rates = array(
            'CAD' => 1.3415
        );

        if (isset($exchange_rates[$currency])) {
            return $exchange_rates[$currency];
        }
            
        return 1;
    }
    */

function insert_partner_google_tag($include_footer = TRUE) {

  $env = ecard_get_environment();
    switch($env) {
      case 'dev':
          ?>
        <!-- Google Tag Manager: Dev -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl+ '&gtm_auth=sWC6qU8WenpLc2WKbDRm1A&gtm_preview=env-4&gtm_cookies_win=x';f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-5XKH47W');</script>
        <!-- End Google Tag Manager -->
        <?php 
        if ($include_footer) { 
        ?>
        <!-- Google Tag Manager (noscript): Dev -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5XKH47W&gtm_auth=sWC6qU8WenpLc2WKbDRm1A&gtm_preview=env-4&gtm_cookies_win=x"height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <?php
        }
        break;
      case 'stage':
          ?>
        <!-- Google Tag Manager: Staging -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl+'&gtm_auth=EgRZRVnSvcBDrD2L--1h7Q&gtm_preview=env-3&gtm_cookies_win=x';f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-5XKH47W');</script>
        <!-- End Google Tag Manager -->
        <?php 
        if ($include_footer) { 
        ?>
        <!-- Google Tag Manager (noscript): Staging -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5XKH47W&gtm_auth=EgRZRVnSvcBDrD2L--1h7Q&gtm_preview=env-3&gtm_cookies_win=x"height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <?php
        }
        break;
      case 'prod':
          ?>
        <!-- Google Tag Manager: Live -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl+ '&gtm_auth=NSgz7Wc_Rrub9t8by-eouQ&gtm_preview=env-1&gtm_cookies_win=x';f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-5XKH47W');</script>
        <!-- End Google Tag Manager -->
        <?php 
        if ($include_footer) { 
        ?>
        <!-- Google Tag Manager (noscript): Live -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5XKH47W&gtm_auth=NSgz7Wc_Rrub9t8by-eouQ&gtm_preview=env-1&gtm_cookies_win=x"height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <?php
        }
        break;
    }
}