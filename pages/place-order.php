<?php
/**
 * Place Order Handler (POST)
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $_POST['tenant_id'] ?? ($tenant['id'] ?? null);
$tenantSlug = $_POST['tenant_slug'] ?? ($tenant['slug'] ?? '');

$items = cart_get();
if (empty($items)) {
    flash('error', 'Cart is empty');
    redirect($tenant ? "/t/{$tenantSlug}/cart" : '/cart');
}

// Validate address
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$flat = trim($_POST['flat'] ?? '');
$area = trim($_POST['area'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$addressType = $_POST['address_type'] ?? 'Home';

if (!$name || !$phone || !$flat || !$area || !$city || !$state || !$postalCode) {
    flash('error', 'Please fill in all required address fields');
    redirect($tenant ? "/t/{$tenantSlug}/checkout" : '/checkout');
}

// Calculate totals
$totals = cart_totals();
$subtotal = $totals['subtotal'];
$shipping = $totals['shipping'];
$total = $totals['total'];

// Payment method
$paymentMethod = $_POST['payment_method'] ?? 'cod';
$upiApp = $_POST['upi_app'] ?? 'PhonePe';

// Generate order reference
$orderId = generate_uuid();
$reference = create_upi_reference();

// Build UPI URL if UPI payment
$paymentUrl = null;
if ($paymentMethod === 'upi') {
    $theme = get_theme();
    $tenantUpiId = $tenant ? ($tenant['upi_id'] ?? null) : null;
    $tenantPayeeName = $tenant ? ($tenant['upi_payee_name'] ?? null) : null;
    $upiId = clean_upi_id($tenantUpiId ?? $theme['upi_id'] ?? '');
    $payeeName = trim($tenantPayeeName ?? $theme['upi_payee_name'] ?? DEFAULT_SITE_NAME);
    
    if ($upiId && is_valid_upi_id($upiId)) {
        $paymentUrl = build_app_upi_url($upiApp, $upiId, $payeeName, $total, $reference);
    }
}

// Create order in database
$orderData = [
    'id' => $orderId,
    'tenant_id' => $tenantId ?: null,
    'user_id' => null,
    'customer_name' => $name,
    'customer_email' => "{$phone}@customer.local",
    'customer_phone' => $phone,
    'shipping_address' => json_encode([
        'line1' => $flat,
        'line2' => $area,
        'city' => $city,
        'state' => $state,
        'postal_code' => $postalCode,
        'address_type' => $addressType,
    ]),
    'subtotal' => $subtotal,
    'shipping_fee' => $shipping,
    'total' => $total,
    'status' => $paymentMethod === 'upi' ? 'pending_payment' : 'pending',
    'payment_method' => $paymentMethod === 'upi' ? 'upi' : null,
    'payment_app' => $paymentMethod === 'upi' ? $upiApp : null,
    'payment_reference' => $reference,
    'payment_url' => $paymentUrl,
];

$order = create_order($orderData);

if (!$order) {
    flash('error', 'Failed to place order. Please try again.');
    redirect($tenant ? "/t/{$tenantSlug}/checkout" : '/checkout');
}

// Create order items
$orderItems = [];
foreach ($items as $item) {
    $orderItems[] = [
        'order_id' => $orderId,
        'product_id' => $item['product_id'],
        'title' => $item['title'],
        'image_url' => $item['image_url'],
        'unit_price' => $item['unit_price'],
        'quantity' => $item['quantity'],
    ];
}

create_order_items($orderItems);

// Clear cart
cart_clear();

// If UPI payment, try to redirect to app
if ($paymentMethod === 'upi' && $paymentUrl) {
    $_SESSION['pending_payment'] = [
        'order_id' => $orderId,
        'reference' => $reference,
        'url' => $paymentUrl,
        'app' => $upiApp,
    ];
}

// Redirect to order page
$orderLink = $tenant ? "/t/{$tenantSlug}/order/{$orderId}" : "/order/{$orderId}";
redirect($orderLink);
