<?php

/**
 * The plugin module init file
 *
 * This file is used by WordPress to load custom modules and functions.
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

include 'functions.php';

ecard_require_module('dropdown');
ecard_require_module('shipping-dropdown');
ecard_require_module('order-approval-extension');
ecard_require_module('admin-accounting-orders-view');
ecard_require_module('modal');
ecard_require_module('address');
ecard_require_module('currency-dropdown');
ecard_require_module('fees-dropdown');
ecard_require_module('admin-order-address');
ecard_require_module('order-page-search');
ecard_require_module('order-actions');
ecard_require_module('simple-crypt');
ecard_require_module('order-token');
