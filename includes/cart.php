<?php
/**
 * Cart Management (Session-based)
 */

/**
 * Get cart items from session
 */
function cart_get(): array {
    return $_SESSION[CART_SESSION_KEY] ?? [];
}

/**
 * Add item to cart
 */
function cart_add(array $item, int $qty = 1): void {
    $cart = cart_get();
    $productId = $item['product_id'];
    $maxQty = (!empty($item['max_quantity']) && $item['max_quantity'] > 0) ? $item['max_quantity'] : MAX_CART_QTY;
    
    $found = false;
    foreach ($cart as &$existing) {
        if ($existing['product_id'] === $productId) {
            $existing['quantity'] = min($maxQty, $existing['quantity'] + $qty);
            $existing['max_quantity'] = $item['max_quantity'] ?? $existing['max_quantity'] ?? null;
            $found = true;
            break;
        }
    }
    unset($existing);
    
    if (!$found) {
        $item['quantity'] = min($maxQty, max(1, $qty));
        $cart[] = $item;
    }
    
    $_SESSION[CART_SESSION_KEY] = $cart;
}

/**
 * Set quantity for a cart item
 */
function cart_set_qty(string $productId, int $qty): void {
    $cart = cart_get();
    foreach ($cart as &$item) {
        if ($item['product_id'] === $productId) {
            $maxQty = (!empty($item['max_quantity']) && $item['max_quantity'] > 0) ? $item['max_quantity'] : MAX_CART_QTY;
            $item['quantity'] = min($maxQty, max(1, $qty));
            break;
        }
    }
    unset($item);
    $_SESSION[CART_SESSION_KEY] = $cart;
}

/**
 * Remove item from cart
 */
function cart_remove(string $productId): void {
    $cart = cart_get();
    $cart = array_values(array_filter($cart, function($item) use ($productId) {
        return $item['product_id'] !== $productId;
    }));
    $_SESSION[CART_SESSION_KEY] = $cart;
}

/**
 * Clear the cart
 */
function cart_clear(): void {
    $_SESSION[CART_SESSION_KEY] = [];
}

/**
 * Get cart totals
 */
function cart_totals(): array {
    $items = cart_get();
    $subtotal = 0;
    $count = 0;
    $mrpTotal = 0;
    
    foreach ($items as $item) {
        $subtotal += $item['unit_price'] * $item['quantity'];
        $count += $item['quantity'];
        $mrp = (!empty($item['mrp']) && $item['mrp'] > $item['unit_price']) ? $item['mrp'] : $item['unit_price'];
        $mrpTotal += $mrp * $item['quantity'];
    }
    
    $discount = max(0, $mrpTotal - $subtotal);
    
    // Check delivery charges: tenant setting overrides global
    $deliveryEnabled = true;
    $tenant = $_SESSION['current_tenant'] ?? null;
    if ($tenant) {
        // Tenant controls their own delivery charges
        $deliveryEnabled = ($tenant['delivery_charges_enabled'] ?? false) ? true : false;
    } else {
        // Global setting from app_settings
        $deliveryEnabled = get_app_setting('delivery_charges_enabled') === 'true';
    }
    
    $shipping = ($deliveryEnabled && $subtotal > 0 && $subtotal < FREE_DELIVERY_THRESHOLD) ? DELIVERY_CHARGE : 0;
    $total = $subtotal + $shipping;
    $savings = max(0, $mrpTotal - $total);
    
    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'count' => $count,
        'mrp_total' => $mrpTotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'total' => $total,
        'savings' => $savings,
    ];
}
