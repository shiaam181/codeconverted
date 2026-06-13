<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/models.php';

header('Content-Type: application/json');

// Direct query to check what comes back
$raw = supabase_query('products', [
    'is_active' => 'eq.true',
    'select' => 'id,title,tenant_id',
    'limit' => '5',
]);

echo json_encode([
    'supabase_url' => SUPABASE_URL,
    'key_prefix' => substr(SUPABASE_KEY, 0, 20) . '...',
    'product_count' => is_array($raw) && !isset($raw['error']) ? count($raw) : 0,
    'raw_response' => $raw,
], JSON_PRETTY_PRINT);
