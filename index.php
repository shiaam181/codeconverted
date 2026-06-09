<?php
/**
 * Main Router - Entry point for the PHP application
 * 
 * This file handles all routing, similar to TanStack Router in the React version.
 */

session_start();

// Load configuration
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/cart.php';
require_once __DIR__ . '/includes/models.php';
require_once __DIR__ . '/includes/admin-helpers.php';
require_once __DIR__ . '/includes/flipkart-scraper.php';

// Handle cart actions (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_to_cart':
            cart_add([
                'product_id' => $_POST['product_id'],
                'tenant_id' => $_POST['tenant_id'] ?? null,
                'tenant_slug' => $_POST['tenant_slug'] ?? null,
                'slug' => $_POST['slug'],
                'title' => $_POST['title'],
                'image_url' => $_POST['image_url'] ?? null,
                'unit_price' => (float) $_POST['unit_price'],
                'mrp' => !empty($_POST['mrp']) ? (float) $_POST['mrp'] : null,
                'max_quantity' => !empty($_POST['max_quantity']) ? (int) $_POST['max_quantity'] : null,
                'rating' => $_POST['rating'] ?? null,
                'rating_count' => $_POST['rating_count'] ?? null,
                'brand' => $_POST['brand'] ?? null,
            ], (int) ($_POST['qty'] ?? 1));
            flash('success', 'Added to cart');
            redirect($_POST['redirect'] ?? '/');
            break;
            
        case 'update_qty':
            cart_set_qty($_POST['product_id'], (int) $_POST['qty']);
            redirect($_POST['redirect'] ?? '/cart');
            break;
            
        case 'remove_from_cart':
            cart_remove($_POST['product_id']);
            flash('success', 'Item removed');
            redirect($_POST['redirect'] ?? '/cart');
            break;
            
        case 'clear_cart':
            cart_clear();
            redirect($_POST['redirect'] ?? '/');
            break;
            
        case 'place_order':
            require __DIR__ . '/pages/place-order.php';
            exit;
            break;
            
        case 'mark_paid':
            $markOrderId = $_POST['order_id'] ?? '';
            if ($markOrderId) {
                $ref = strtoupper(substr($markOrderId, 0, 8));
                supabase_rpc('mark_order_payment_submitted', [
                    '_order_id' => $markOrderId,
                    '_payment_reference' => $ref,
                ]);
            }
            $tenant = $_SESSION['current_tenant'] ?? null;
            $orderLink = $tenant ? "/t/{$tenant['slug']}/order/{$markOrderId}" : "/order/{$markOrderId}";
            redirect($orderLink);
            break;
    }
}

// Get the request path
$path = current_path();
$path = rtrim($path, '/') ?: '/';

// Route the request
switch (true) {
    case $path === '/':
        require __DIR__ . '/pages/home.php';
        break;
        
    case preg_match('#^/product/([^/]+)$#', $path, $matches) === 1:
        $slug = $matches[1];
        require __DIR__ . '/pages/product.php';
        break;
        
    case $path === '/cart':
        require __DIR__ . '/pages/cart.php';
        break;
        
    case $path === '/checkout':
        require __DIR__ . '/pages/checkout.php';
        break;
        
    case $path === '/search':
        require __DIR__ . '/pages/search.php';
        break;
        
    case preg_match('#^/category/([^/]+)$#', $path, $matches) === 1:
        $slug = $matches[1];
        require __DIR__ . '/pages/category.php';
        break;
        
    case preg_match('#^/order/([^/]+)$#', $path, $matches) === 1:
        $orderId = $matches[1];
        require __DIR__ . '/pages/order.php';
        break;
        
    case preg_match('#^/admin(.*)$#', $path, $matches) === 1:
        $adminPath = $matches[1] ?: '/';
        $adminPath = rtrim($adminPath, '/') ?: '/';
        
        // Login doesn't require auth
        if ($adminPath === '/login') {
            require __DIR__ . '/admin/login.php';
            break;
        }
        
        // Logout
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['admin_action'] ?? '') === 'logout') {
            unset($_SESSION['admin_token'], $_SESSION['admin_user_id'], $_SESSION['admin_username']);
            redirect('/admin/login');
        }
        
        // All other admin pages require auth
        require_admin();
        
        switch (true) {
            case $adminPath === '/':
                require __DIR__ . '/admin/dashboard.php';
                break;
            case $adminPath === '/products':
                require __DIR__ . '/admin/products.php';
                break;
            case $adminPath === '/products/new':
            case $adminPath === '/products/edit':
                require __DIR__ . '/admin/product-form.php';
                break;
            case $adminPath === '/orders':
                require __DIR__ . '/admin/orders.php';
                break;
            case $adminPath === '/banners':
                require __DIR__ . '/admin/banners.php';
                break;
            case $adminPath === '/banners/new':
            case $adminPath === '/banners/edit':
                require __DIR__ . '/admin/banner-form.php';
                break;
            case $adminPath === '/categories':
                require __DIR__ . '/admin/categories.php';
                break;
            case $adminPath === '/categories/new':
            case $adminPath === '/categories/edit':
                require __DIR__ . '/admin/category-form.php';
                break;
            case $adminPath === '/theme':
                require __DIR__ . '/admin/theme.php';
                break;
            case $adminPath === '/upi':
                require __DIR__ . '/admin/upi.php';
                break;
            case $adminPath === '/upi/new':
            case $adminPath === '/upi/edit':
                require __DIR__ . '/admin/upi-form.php';
                break;
            case $adminPath === '/tenants':
                require __DIR__ . '/admin/tenants.php';
                break;
            case $adminPath === '/tenants/new':
                require __DIR__ . '/admin/tenant-form.php';
                break;
            case $adminPath === '/payment-offers':
                require __DIR__ . '/admin/payment-offers.php';
                break;
            case $adminPath === '/payment-offers/new':
            case $adminPath === '/payment-offers/edit':
                require __DIR__ . '/admin/offer-form.php';
                break;
            case $adminPath === '/homepage-layout':
                require __DIR__ . '/admin/homepage-layout.php';
                break;
            case $adminPath === '/homepage-layout/new':
            case $adminPath === '/homepage-layout/edit':
                require __DIR__ . '/admin/layout-section-form.php';
                break;
            default:
                http_response_code(404);
                require __DIR__ . '/pages/404.php';
                break;
        }
        break;
        
    case preg_match('#^/t/([^/]+)(.*)$#', $path, $matches) === 1:
        // Tenant routes
        $tenantSlug = $matches[1];
        $tenantPath = $matches[2] ?: '/';
        $tenant = get_tenant_by_slug($tenantSlug);
        if (!$tenant) {
            http_response_code(404);
            require __DIR__ . '/pages/404.php';
            break;
        }
        $_SESSION['current_tenant'] = $tenant;
        
        switch (true) {
            case $tenantPath === '/' || $tenantPath === '':
                require __DIR__ . '/pages/home.php';
                break;
            case preg_match('#^/product/([^/]+)$#', $tenantPath, $m) === 1:
                $slug = $m[1];
                require __DIR__ . '/pages/product.php';
                break;
            case $tenantPath === '/cart':
                require __DIR__ . '/pages/cart.php';
                break;
            case $tenantPath === '/checkout':
                require __DIR__ . '/pages/checkout.php';
                break;
            case $tenantPath === '/search':
                require __DIR__ . '/pages/search.php';
                break;
            case preg_match('#^/category/([^/]+)$#', $tenantPath, $m) === 1:
                $slug = $m[1];
                require __DIR__ . '/pages/category.php';
                break;
            case preg_match('#^/order/([^/]+)$#', $tenantPath, $m) === 1:
                $orderId = $m[1];
                require __DIR__ . '/pages/order.php';
                break;
            default:
                http_response_code(404);
                require __DIR__ . '/pages/404.php';
                break;
        }
        unset($_SESSION['current_tenant']);
        break;
        
    default:
        http_response_code(404);
        require __DIR__ . '/pages/404.php';
        break;
}
