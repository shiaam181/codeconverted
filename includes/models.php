<?php
/**
 * Data Models - Supabase API queries
 */

/**
 * Get all active categories
 */
function get_categories(): array {
    $data = supabase_query('categories', [
        'is_active' => 'eq.true',
        'order' => 'sort_order.asc',
        'select' => '*',
    ]);
    if (empty($data) || isset($data['error'])) {
        // Fallback: try without admin token
        $oldToken = $_SESSION['admin_token'] ?? null;
        unset($_SESSION['admin_token']);
        $data = supabase_query('categories', [
            'is_active' => 'eq.true',
            'order' => 'sort_order.asc',
            'select' => '*',
        ]);
        if ($oldToken) $_SESSION['admin_token'] = $oldToken;
    }
    return isset($data['error']) ? [] : $data;
}

/**
 * Get a single category by slug
 */
function get_category_by_slug(string $slug): ?array {
    $data = supabase_query('categories', [
        'slug' => 'eq.' . $slug,
        'is_active' => 'eq.true',
        'select' => 'id,name,slug,image_url',
    ]);
    return (!empty($data) && !isset($data['error'])) ? $data[0] : null;
}

/**
 * Get active banners by position
 */
function get_banners(string $position = 'hero'): array {
    $data = supabase_query('banners', [
        'position' => 'eq.' . $position,
        'is_active' => 'eq.true',
        'order' => 'sort_order.asc',
        'select' => '*',
    ]);
    return isset($data['error']) ? [] : $data;
}

/**
 * Get homepage layout sections
 */
function get_homepage_layout(): array {
    $data = supabase_query('homepage_layout', [
        'is_enabled' => 'eq.true',
        'order' => 'sort_order.asc',
        'select' => '*',
    ]);
    return isset($data['error']) ? [] : $data;
}

/**
 * Get products with filters
 */
function get_products(array $options = []): array {
    $params = [
        'is_active' => 'eq.true',
        'select' => '*,product_images(url,sort_order)',
        'order' => $options['order'] ?? 'created_at.desc',
        'limit' => (string) ($options['limit'] ?? 200),
    ];
    
    // Tenant scoping
    $tenantId = $options['tenant_id'] ?? null;
    if ($tenantId) {
        // Respect show_default_products flag
        $hideDefaults = $options['hide_defaults'] ?? false;
        if ($hideDefaults) {
            $params['tenant_id'] = 'eq.' . $tenantId;
        } else {
            // Show both tenant products AND default (null tenant) products
            $params['or'] = '(tenant_id.eq.' . $tenantId . ',tenant_id.is.null)';
        }
    }
    // When no tenant: show all products (no tenant_id filter)
    
    // Featured filter
    if (!empty($options['featured'])) {
        $params['is_featured'] = 'eq.true';
    }
    
    // Category filter
    if (!empty($options['category_id'])) {
        $params['category_id'] = 'eq.' . $options['category_id'];
    }
    
    $data = supabase_query('products', $params);
    
    // Fallback if admin token is expired
    if (empty($data) || isset($data['error'])) {
        $oldToken = $_SESSION['admin_token'] ?? null;
        unset($_SESSION['admin_token']);
        $data = supabase_query('products', $params);
        if ($oldToken) $_SESSION['admin_token'] = $oldToken;
    }
    
    return isset($data['error']) ? [] : $data;
}

/**
 * Get a single product by slug
 */
function get_product_by_slug(string $slug, ?string $tenantId = null): ?array {
    $params = [
        'slug' => 'eq.' . $slug,
        'is_active' => 'eq.true',
        'select' => '*,product_images(url,sort_order),categories(name,slug),product_variants(id,color_name,color_code,price,mrp,stock,sort_order,is_default,variant_images(url,sort_order))',
    ];
    
    if ($tenantId) {
        $params['tenant_id'] = 'eq.' . $tenantId;
    } else {
        $params['tenant_id'] = 'is.null';
    }
    
    $data = supabase_query('products', $params);
    return (!empty($data) && !isset($data['error'])) ? $data[0] : null;
}

/**
 * Search products
 */
function search_products(string $query, ?string $tenantId = null, int $limit = 40): array {
    $params = [
        'is_active' => 'eq.true',
        'select' => '*,product_images(url,sort_order)',
        'limit' => (string) $limit,
        'or' => "(title.ilike.*{$query}*,brand.ilike.*{$query}*,description.ilike.*{$query}*)",
    ];
    
    if ($tenantId) {
        $params['tenant_id'] = 'eq.' . $tenantId;
    } else {
        $params['tenant_id'] = 'is.null';
    }
    
    $data = supabase_query('products', $params);
    
    // Fallback if admin token is expired
    if (empty($data) || isset($data['error'])) {
        $oldToken = $_SESSION['admin_token'] ?? null;
        unset($_SESSION['admin_token']);
        $data = supabase_query('products', $params);
        if ($oldToken) $_SESSION['admin_token'] = $oldToken;
    }
    
    return isset($data['error']) ? [] : $data;
}

/**
 * Get related products (same category)
 */
function get_related_products(string $categoryId, string $excludeId, ?string $tenantId = null, int $limit = 8): array {
    $params = [
        'is_active' => 'eq.true',
        'category_id' => 'eq.' . $categoryId,
        'id' => 'neq.' . $excludeId,
        'select' => '*,product_images(url,sort_order)',
        'limit' => (string) $limit,
    ];
    
    if ($tenantId) {
        $params['tenant_id'] = 'eq.' . $tenantId;
    } else {
        $params['tenant_id'] = 'is.null';
    }
    
    $data = supabase_query('products', $params);
    return isset($data['error']) ? [] : $data;
}

/**
 * Get products by category slug with sorting
 */
function get_products_by_category(string $categorySlug, string $sort = 'popular', ?string $tenantId = null): array {
    $category = get_category_by_slug($categorySlug);
    if (!$category) return ['category' => null, 'products' => []];
    
    $orderMap = [
        'popular' => 'rating_count.desc',
        'price-asc' => 'price.asc',
        'price-desc' => 'price.desc',
        'rating' => 'rating.desc',
    ];
    
    $products = get_products([
        'category_id' => $category['id'],
        'tenant_id' => $tenantId,
        'order' => $orderMap[$sort] ?? 'rating_count.desc',
        'limit' => 60,
    ]);
    
    return ['category' => $category, 'products' => $products];
}

/**
 * Get theme configuration
 */
function get_theme(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    
    $data = supabase_query('theme_config', [
        'id' => 'eq.1',
        'select' => '*',
    ]);
    
    if (empty($data) || isset($data['error'])) {
        $cached = [
            'site_name' => DEFAULT_SITE_NAME,
            'primary_color' => DEFAULT_PRIMARY_COLOR,
            'secondary_color' => DEFAULT_SECONDARY_COLOR,
            'accent_color' => DEFAULT_ACCENT_COLOR,
            'logo_url' => null,
            'favicon_url' => null,
            'upi_id' => null,
            'upi_payee_name' => null,
            'upi_note_template' => 'Order payment {reference}',
            'font_family' => 'Inter, sans-serif',
            'border_radius' => '0.375rem',
        ];
        return $cached;
    }
    $cached = $data[0];
    return $cached;
}

/**
 * Get a tenant by slug
 */
function get_tenant_by_slug(string $slug): ?array {
    $data = supabase_query('tenants', [
        'slug' => 'eq.' . $slug,
        'select' => 'id,slug,name,upi_id,upi_payee_name,upi_note_template,logo_url,is_active,custom_domain,expires_at,show_default_products',
    ]);
    return (!empty($data) && !isset($data['error'])) ? $data[0] : null;
}

/**
 * Get UPI payment methods
 */
function get_upi_methods(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    
    $data = supabase_query('upi_methods', [
        'is_active' => 'eq.true',
        'order' => 'sort_order.asc',
        'select' => '*',
    ]);
    $cached = isset($data['error']) ? [] : $data;
    return $cached;
}

/**
 * Get payment offers
 */
function get_payment_offers(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    
    $data = supabase_query('payment_offers', [
        'is_active' => 'eq.true',
        'order' => 'sort_order.asc',
        'select' => '*',
    ]);
    $cached = isset($data['error']) ? [] : $data;
    return $cached;
}

/**
 * Create an order
 */
function create_order(array $orderData): ?array {
    $result = supabase_query('orders', [], 'POST', $orderData);
    return (!empty($result) && !isset($result['error'])) ? $result[0] : null;
}

/**
 * Create order items
 */
function create_order_items(array $items): bool {
    $result = supabase_query('order_items', [], 'POST', $items);
    return !isset($result['error']);
}

/**
 * Get an order by ID
 */
function get_order(string $id): ?array {
    $data = supabase_query('orders', [
        'id' => 'eq.' . $id,
        'select' => '*',
    ]);
    return (!empty($data) && !isset($data['error'])) ? $data[0] : null;
}

/**
 * Get order items
 */
function get_order_items(string $orderId): array {
    $data = supabase_query('order_items', [
        'order_id' => 'eq.' . $orderId,
        'select' => '*',
    ]);
    return isset($data['error']) ? [] : $data;
}

/**
 * Get app setting
 */
function get_app_setting(string $key): ?string {
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    
    $data = supabase_query('app_settings', [
        'key' => 'eq.' . $key,
        'select' => 'value',
    ]);
    $cache[$key] = (!empty($data) && !isset($data['error'])) ? ($data[0]['value'] ?? null) : null;
    return $cache[$key];
}

/**
 * Get all icon settings (payment icons + header tab icons)
 * Returns an associative array keyed by icon_key => image_url
 */
function get_icon_settings(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    
    $data = supabase_query('app_settings', [
        'key' => 'like.icon_*',
        'select' => 'key,value',
    ]);
    
    if (empty($data) || isset($data['error'])) {
        $cached = [];
        return $cached;
    }
    
    $icons = [];
    foreach ($data as $row) {
        $key = str_replace('icon_', '', $row['key']);
        if (!empty($row['value'])) {
            $icons[$key] = $row['value'];
        }
    }
    $cached = $icons;
    return $cached;
}

/**
 * Get a single icon URL by key, with optional fallback
 */
function get_icon(string $key, string $fallback = ''): string {
    $value = get_app_setting('icon_' . $key);
    return $value ?: $fallback;
}
