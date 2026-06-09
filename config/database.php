<?php
/**
 * Database Configuration
 * Connects to Supabase via REST API
 */

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://tdrtypjlafbdvasdueah.supabase.co');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRkcnR5cGpsYWZiZHZhc2R1ZWFoIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzkxOTYwOTIsImV4cCI6MjA5NDc3MjA5Mn0.NzhIPN66J1FJSiAvZL4qdnn5muzVErpkvklBE2ji730');

/**
 * Make a request to Supabase REST API
 */
function supabase_query(string $table, array $params = [], string $method = 'GET', $body = null): array {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    
    if (!empty($params) && $method === 'GET') {
        $queryParts = [];
        foreach ($params as $key => $value) {
            $queryParts[] = $key . '=' . urlencode($value);
        }
        $url .= '?' . implode('&', $queryParts);
    }

    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
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
    curl_close($ch);

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
