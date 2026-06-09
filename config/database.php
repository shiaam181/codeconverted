<?php
/**
 * Database Configuration
 * Connects to Supabase via REST API
 */

// Load .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!getenv($key)) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: getenv('VITE_SUPABASE_URL') ?: 'https://tdrtypjlafbdvasdueah.supabase.co');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: getenv('SUPABASE_PUBLISHABLE_KEY') ?: getenv('VITE_SUPABASE_PUBLISHABLE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRkcnR5cGpsYWZiZHZhc2R1ZWFoIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzkxOTYwOTIsImV4cCI6MjA5NDc3MjA5Mn0.NzhIPN66J1FJSiAvZL4qdnn5muzVErpkvklBE2ji730');

/**
 * Get the auth token to use for API calls.
 * Uses admin's token if logged in, otherwise uses the anon key.
 */
function get_auth_token(): string {
    if (!empty($_SESSION['admin_token'])) {
        return $_SESSION['admin_token'];
    }
    return SUPABASE_KEY;
}

/**
 * Make a request to Supabase REST API
 */
function supabase_query(string $table, array $params = [], string $method = 'GET', $body = null): array {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    
    // Add query params to URL for ALL methods (GET, PATCH, DELETE all need filters)
    if (!empty($params)) {
        $queryParts = [];
        foreach ($params as $key => $value) {
            // PostgREST filter values (eq.xxx, is.null, ilike.%xxx%) should not be encoded
            $queryParts[] = $key . '=' . $value;
        }
        $url .= '?' . implode('&', $queryParts);
    }

    $authToken = get_auth_token();

    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . $authToken,
        'Content-Type: application/json',
        'Prefer: return=representation',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['error' => true, 'message' => 'Connection error: ' . $curlError];
    }

    if ($httpCode >= 400) {
        return ['error' => true, 'message' => 'API Error: ' . $httpCode, 'data' => json_decode($response, true)];
    }

    return json_decode($response, true) ?: [];
}

/**
 * Supabase RPC call
 */
function supabase_rpc(string $functionName, array $params = []): array {
    $url = SUPABASE_URL . '/rest/v1/rpc/' . $functionName;

    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        return ['error' => true, 'message' => 'RPC Error: ' . $httpCode];
    }

    return json_decode($response, true) ?: [];
}
