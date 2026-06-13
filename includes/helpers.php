<?php
/**
 * Helper Functions
 */

/**
 * Format price in INR
 */
function format_inr(float $amount): string {
    return '₹' . number_format($amount, 0, '.', ',');
}

/**
 * Get image URL with Supabase transform
 */
function img_url(?string $url, array $opts = []): string {
    if (!$url) return '';
    if (strpos($url, '/storage/v1/object/public/') === false) return $url;
    
    $transformed = str_replace('/storage/v1/object/public/', '/storage/v1/render/image/public/', $url);
    $params = [];
    if (!empty($opts['w'])) $params['width'] = $opts['w'];
    if (!empty($opts['h'])) $params['height'] = $opts['h'];
    $params['quality'] = $opts['quality'] ?? 70;
    $params['resize'] = $opts['resize'] ?? 'contain';
    
    return $transformed . '?' . http_build_query($params);
}

/**
 * Sanitize output for HTML
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get current URL path
 */
function current_path(): string {
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Get query parameter
 */
function get_param(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * Set flash message
 */
function flash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash message
 */
function get_flash(string $type): ?string {
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

/**
 * Calculate discount percentage
 */
function calc_discount(float $price, ?float $mrp, int $discount_percent = 0): int {
    if ($discount_percent > 0) return $discount_percent;
    if ($mrp && $mrp > $price) {
        return (int) round((($mrp - $price) / $mrp) * 100);
    }
    return 0;
}

/**
 * Generate a UUID v4
 */
function generate_uuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Generate a UPI payment reference
 */
function create_upi_reference(): string {
    $date = date('Ymd');
    $time = date('His');
    $suffix = strtoupper(substr(base_convert(mt_rand(), 10, 36), 0, 5));
    return "ORDER-{$date}-{$time}-{$suffix}";
}

/**
 * Clean UPI ID
 */
function clean_upi_id(?string $value): string {
    if (!$value) return '';
    $normalized = preg_replace('/\r?\n/', '', $value);
    $normalized = preg_replace('/\s*\?\s*/', '?', $normalized);
    $normalized = preg_replace('/\s*&\s*/', '&', $normalized);
    $normalized = preg_replace('/\s*=\s*/', '=', $normalized);
    $normalized = trim($normalized);
    
    if (stripos($normalized, 'upi://') === 0 || strpos($normalized, 'pa=') !== false) {
        $parts = parse_url($normalized);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $queryParams);
            if (!empty($queryParams['pa'])) {
                return strtolower(trim(preg_replace('/\s+/', '', $queryParams['pa'])));
            }
        }
    }
    return strtolower(trim(preg_replace('/\s+/', '', $value)));
}

/**
 * Validate UPI ID format
 */
function is_valid_upi_id(string $value): bool {
    return (bool) preg_match('/^[a-z0-9._-]{2,256}@[a-z0-9._-]{2,64}$/i', $value);
}

/**
 * Format amount for UPI (2 decimal places)
 */
function format_upi_amount(float $value): string {
    return number_format(round($value * 100) / 100, 2, '.', '');
}

/**
 * Build UPI payment URL
 */
function build_upi_url(string $upiId, string $payeeName, float $amount, string $orderId): string {
    $fields = [
        'pa' => clean_upi_id($upiId),
        'pn' => substr(trim(preg_replace('/[\x00-\x1f\x7f]/', '', $payeeName)), 0, 50) ?: 'Online Store',
        'am' => format_upi_amount($amount),
        'cu' => 'INR',
        'tn' => 'Ref ' . substr($orderId, -6),
    ];
    return 'upi://pay?' . http_build_query($fields);
}

/**
 * Build UPI URL - always uses generic upi://pay? scheme (AllUPI source)
 * This lets the system's UPI intent handler pick the appropriate app
 * instead of forcing a specific branded merchant deep link.
 */
function build_app_upi_url(string $appName, string $upiId, string $payeeName, float $amount, string $orderId): string {
    $fields = [
        'pa' => clean_upi_id($upiId),
        'pn' => substr(trim(preg_replace('/[\x00-\x1f\x7f]/', '', $payeeName)), 0, 50) ?: 'Online Store',
        'am' => format_upi_amount($amount),
        'cu' => 'INR',
        'tn' => 'Ref ' . substr($orderId, -6),
    ];
    $query = http_build_query($fields);
    
    // Always use generic upi://pay? - works with all UPI apps
    return "upi://pay?{$query}";
}
