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
 * Upsert an app_settings row by key.
 * Tries PATCH first; if no row matched, falls back to POST (insert).
 */
function upsert_app_setting(string $key, string $value): array {
    $payload = [
        'value' => $value,
        'updated_at' => date('c'),
    ];
    
    // Try PATCH (update existing)
    $result = supabase_query('app_settings', ['key' => 'eq.' . $key], 'PATCH', $payload);
    
    // If result is empty array, the row didn't exist — insert it
    if (is_array($result) && empty($result) && !isset($result['error'])) {
        $payload['key'] = $key;
        $result = supabase_query('app_settings', [], 'POST', $payload);
    }
    
    return is_array($result) ? $result : [];
}

/**
 * Upload a file to Supabase Storage bucket "media".
 * Returns the public URL on success, null on failure.
 */
function upload_to_supabase_storage(array $file, string $folder): ?string {
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $path = $folder . '/' . generate_uuid() . '.' . $ext;
    $url = SUPABASE_URL . '/storage/v1/object/media/' . $path;
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . get_auth_token(),
        'Content-Type: ' . ($file['type'] ?: 'application/octet-stream'),
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file['tmp_name']));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) return null;
    
    // Get public URL
    $publicUrl = SUPABASE_URL . '/storage/v1/object/public/media/' . $path;
    return $publicUrl;
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
 * Seed tenant products from master catalog.
 * Copies all master products (tenant_id IS NULL) to the new tenant,
 * marking them as is_default_product = true.
 */
function seed_tenant_products(string $tenantId, string $tenantSlug): void {
    // Get master products
    $master = supabase_query('products', [
        'tenant_id' => 'is.null',
        'select' => 'id,title,slug,description,brand,price,mrp,discount_percent,stock,is_active,is_featured,category_id,is_assured',
        'limit' => '500',
    ]);
    
    if (empty($master) || isset($master['error'])) return;
    
    foreach ($master as $p) {
        $newSlug = $p['slug'] . '-' . $tenantSlug;
        $insert = [
            'tenant_id' => $tenantId,
            'title' => $p['title'],
            'slug' => $newSlug,
            'description' => $p['description'],
            'brand' => $p['brand'],
            'price' => $p['price'],
            'mrp' => $p['mrp'],
            'discount_percent' => $p['discount_percent'] ?? 0,
            'stock' => $p['stock'] ?? 10,
            'is_active' => $p['is_active'] ?? true,
            'is_featured' => $p['is_featured'] ?? false,
            'category_id' => $p['category_id'],
            'is_assured' => $p['is_assured'] ?? false,
            'is_default_product' => true,
            'source_product_id' => $p['id'],
        ];
        
        $newProduct = supabase_query('products', [], 'POST', $insert);
        
        // Copy product images
        if (!empty($newProduct) && !isset($newProduct['error'])) {
            $newProductId = is_array($newProduct[0] ?? null) ? $newProduct[0]['id'] : ($newProduct['id'] ?? null);
            if ($newProductId) {
                $images = supabase_query('product_images', [
                    'product_id' => 'eq.' . $p['id'],
                    'select' => 'url,sort_order',
                ]);
                if (!empty($images) && !isset($images['error'])) {
                    foreach ($images as $img) {
                        supabase_query('product_images', [], 'POST', [
                            'product_id' => $newProductId,
                            'url' => $img['url'],
                            'sort_order' => $img['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        }
    }
}

/**
 * Supabase Admin API - Update user (requires service_role key)
 */
function supabase_admin_update_user(string $userId, array $updates): array {
    $serviceKey = getenv('SUPABASE_SERVICE_ROLE_KEY');
    if (!$serviceKey) {
        return ['error' => true, 'message' => 'SUPABASE_SERVICE_ROLE_KEY not configured'];
    }
    
    $url = SUPABASE_URL . '/auth/v1/admin/users/' . $userId;
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . $serviceKey,
        'Content-Type: application/json',
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updates));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true) ?: [];
    if ($httpCode >= 400) {
        return ['error' => true, 'message' => $data['msg'] ?? $data['message'] ?? 'Failed (HTTP ' . $httpCode . ')'];
    }
    return $data;
}

/**
 * Get tenant products (for tenant admin panel)
 */
function get_tenant_products(string $tenantId, string $search = ''): array {
    $params = [
        'select' => '*,product_images(id,url,sort_order)',
        'tenant_id' => 'eq.' . $tenantId,
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
 * Get tenant stats (product count, order count)
 */
function get_tenant_stats(string $tenantId): array {
    $products = supabase_query('products', ['select' => 'id', 'tenant_id' => 'eq.' . $tenantId, 'limit' => '1000']);
    $orders = supabase_query('orders', ['select' => 'id', 'tenant_id' => 'eq.' . $tenantId, 'limit' => '1000']);
    
    return [
        'products' => is_array($products) && !isset($products['error']) ? count($products) : 0,
        'orders' => is_array($orders) && !isset($orders['error']) ? count($orders) : 0,
    ];
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


/**
 * Get list of media files from Supabase Storage bucket "media"
 */
function get_media_files(): array {
    $url = SUPABASE_URL . '/storage/v1/object/list/media';
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . get_auth_token(),
        'Content-Type: application/json',
    ];
    
    $folders = ['general', 'products', 'banners', 'categories', 'branding', 'upi', 'tabs'];
    $allFiles = [];
    
    foreach ($folders as $folder) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['prefix' => $folder . '/', 'limit' => 100]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode < 400 && $response) {
            $files = json_decode($response, true);
            if (is_array($files)) {
                foreach ($files as $f) {
                    if (!empty($f['name']) && !empty($f['id'])) {
                        $allFiles[] = ['name' => $folder . '/' . $f['name'], 'id' => $f['id']];
                    }
                }
            }
        }
    }
    
    return $allFiles;
}
