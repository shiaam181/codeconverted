<?php
/**
 * Application Configuration
 */

define('APP_NAME', 'ShopMart');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('APP_DEBUG', getenv('APP_DEBUG') ?: true);

// Site defaults
define('DEFAULT_SITE_NAME', 'ShopMart');
define('DEFAULT_PRIMARY_COLOR', '#2874f0');
define('DEFAULT_SECONDARY_COLOR', '#fb641b');
define('DEFAULT_ACCENT_COLOR', '#ffe11b');

// Cart settings
define('CART_SESSION_KEY', 'shopmart_cart');
define('MAX_CART_QTY', 10);

// Delivery settings
define('FREE_DELIVERY_THRESHOLD', 500);
define('DELIVERY_CHARGE', 40);
