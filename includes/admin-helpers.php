<?php
/**
 * Admin Helper Functions
 */

/**
 * Supabase Auth - Login
 */
function supabase_auth_login(string $email, string $password): array {
    $url = SUPABASE_URL . '/auth/v1/token?grant_type=password';
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Content-Type: application/json',
    ];
    $body = json_encode(['email' => $email, 'password' => $password]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['error' => ['message' => 'Connection error: ' . $curlError]];
    }
    
    $data = json_decode($response, true) ?: [];
    if ($httpCode >= 400) {
        // Supabase returns error in different formats
        $msg = $data['error_description'] ?? $data['msg'] ?? $data['message'] ?? $data['error'] ?? 'Login failed (HTTP ' . $httpCode . ')';
        if (is_array($msg)) $msg = json_encode($msg);
        return ['error' => ['message' => $msg]];
    }
    return $data;
}

/**
 * Supabase Auth - Signup
 */
function supabase_auth_signup(string $email, string $password): array {
    $url = SUPABASE_URL . '/auth/v1/signup';
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Content-Type: application/json',
    ];
    $body = json_encode(['email' => $email, 'password' => $password]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['error' => ['message' => 'Connection error: ' . $curlError]];
    }
    
    $data = json_decode($response, true) ?: [];
    if ($httpCode >= 400) {
        $msg = $data['error_description'] ?? $data['msg'] ?? $data['message'] ?? $data['error'] ?? 'Signup failed (HTTP ' . $httpCode . ')';
        if (is_array($msg)) $msg = json_encode($msg);
        return ['error' => ['message' => $msg]];
    }
    return $data;
}

/**
 * Supabase RPC with auth token
 */
function supabase_rpc_with_token(string $fn, array $params, string $token): array {
    $url = SUPABASE_URL . '/rest/v1/rpc/' . $fn;
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

/**
 * Check if current session is admin
 */
function is_admin(): bool {
    return !empty($_SESSION['admin_token']) && !empty($_SESSION['admin_user_id']);
}

/**
 * Require admin auth, redirect to login if not
 */
function require_admin(): void {
    if (!is_admin()) {
        redirect('/admin/login');
    }
}

/**
 * Get admin stats for dashboard
 */
function get_admin_stats(): array {
    $products = supabase_query('products', ['select' => 'id', 'tenant_id' => 'is.null', 'limit' => '1000']);
    $categories = supabase_query('categories', ['select' => 'id', 'limit' => '1000']);
    $banners = supabase_query('banners', ['select' => 'id', 'limit' => '1000']);
    $orders = supabase_query('orders', ['select' => 'id,total', 'limit' => '1000']);
    
    $revenue = 0;
    if (is_array($orders) && !isset($orders['error'])) {
        foreach ($orders as $o) {
            $revenue += (float) ($o['total'] ?? 0);
        }
    }
    
    return [
        'products' => is_array($products) && !isset($products['error']) ? count($products) : 0,
        'categories' => is_array($categories) && !isset($categories['error']) ? count($categories) : 0,
        'banners' => is_array($banners) && !isset($banners['error']) ? count($banners) : 0,
        'orders' => is_array($orders) && !isset($orders['error']) ? count($orders) : 0,
        'revenue' => $revenue,
    ];
}

/**
 * Get admin products list
 */
function get_admin_products(string $search = ''): array {
    $params = [
        'select' => '*,product_images(id,url,sort_order)',
        'tenant_id' => 'is.null',
        'order' => 'created_at.desc',
        'limit' => '200',
    ];
    if ($search) {
        $params['title'] = 'ilike.%' . $search . '%';
    }
    $data = supabase_query('products', $params);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Get admin orders
 */
function get_admin_orders(): array {
    $data = supabase_query('orders', [
        'select' => '*',
        'order' => 'created_at.desc',
        'limit' => '200',
    ]);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Get admin banners
 */
function get_admin_banners(): array {
    $data = supabase_query('banners', ['select' => '*', 'order' => 'sort_order.asc']);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Get admin categories
 */
function get_admin_categories(): array {
    $data = supabase_query('categories', ['select' => '*', 'order' => 'sort_order.asc']);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Get admin tenants
 */
function get_admin_tenants(): array {
    $data = supabase_query('tenants', ['select' => '*', 'order' => 'created_at.desc']);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Get payment offers for admin
 */
function get_payment_offers_admin(): array {
    $data = supabase_query('payment_offers', ['select' => '*', 'order' => 'sort_order.asc']);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Get homepage layout for admin (all sections including disabled)
 */
function get_homepage_layout_admin(): array {
    $data = supabase_query('homepage_layout', ['select' => '*', 'order' => 'sort_order.asc']);
    return (is_array($data) && !isset($data['error'])) ? $data : [];
}

/**
 * Slugify helper
 */
function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return substr($s, 0, 80);
}

/**
 * Admin icon helper (simple text-based icons)
 */
function admin_icon(string $name): string {
    $icons = [
        'dashboard' => '📊',
        'users' => '👥',
        'package' => '📦',
        'folder' => '📁',
        'image' => '🖼️',
        'layout' => '📐',
        'tag' => '🏷️',
        'smartphone' => '📱',
        'palette' => '🎨',
        'cart' => '🛒',
        'logout' => '🚪',
    ];
    return $icons[$name] ?? '•';
}
