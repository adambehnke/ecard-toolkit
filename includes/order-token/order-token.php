<?php
    define ('ORDER_TOKEN_PATH',  ECARD_INCLUDES_PATH . 'order-token/');

    ecard_require_module('simple-crypt');

    include ORDER_TOKEN_PATH . 'includes/classes/class-order-token.php';
    include ORDER_TOKEN_PATH . 'includes/classes/class-order-token-manager.php';