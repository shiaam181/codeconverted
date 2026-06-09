<?php
/**
 * Order Confirmation / Status Page
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$order = get_order($orderId);
$items = get_order_items($orderId);
$homeLink = $tenant ? "/t/{$tenant['slug']}" : '/';

$pageTitle = 'Order — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

// Check if there's a pending UPI payment to redirect to
$pendingPayment = $_SESSION['pending_payment'] ?? null;
if ($pendingPayment && $pendingPayment['order_id'] === $orderId) {
    $upiUrl = $pendingPayment['url'];
    unset($_SESSION['pending_payment']);
}

$isPending = !$order || in_array($order['status'] ?? '', ['pending', 'pending_payment', 'payment_submitted']);

$theme = get_theme();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if ($isPending): ?>
    <meta http-equiv="refresh" content="10">
    <?php endif; ?>
</head>
<body>
<main class="order-page">
    <div class="order-container">
        <!-- Status Card -->
        <div class="order-status-card">
            <?php if ($isPending): ?>
            <div class="status-icon pending">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            </div>
            <h1 class="status-title">
                <?= ($order['status'] ?? '') === 'payment_submitted' ? 'Payment Submitted' : 'Payment Pending' ?>
            </h1>
            <p class="status-desc">
                <?= ($order['status'] ?? '') === 'payment_submitted' 
                    ? 'Waiting for the store owner to verify your payment.' 
                    : 'Waiting for your UPI payment to be received.' ?>
            </p>
            <p class="reference-id">
                Reference ID: <strong><?= e($order['payment_reference'] ?? strtoupper(substr($orderId, 0, 8))) ?></strong>
            </p>
            
            <?php if (!empty($upiUrl)): ?>
            <div class="upi-redirect">
                <a href="<?= e($upiUrl) ?>" class="btn-primary">Open UPI App to Pay</a>
                <p class="text-muted" style="margin-top: 8px; font-size: 12px;">Click above to open your UPI app and complete payment</p>
            </div>
            <?php endif; ?>
            
            <div class="order-actions">
                <?php if (($order['status'] ?? '') !== 'payment_submitted'): ?>
                <form method="POST" action="/" style="display:inline">
                    <input type="hidden" name="action" value="mark_paid">
                    <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                    <button type="submit" class="btn-primary">I Have Paid</button>
                </form>
                <?php else: ?>
                <button class="btn-disabled" disabled>Payment Submitted</button>
                <?php endif; ?>
                <a href="<?= e($homeLink) ?>" class="btn-outline">Continue Shopping</a>
            </div>
            <?php else: ?>
            <div class="status-icon success">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            </div>
            <h1 class="status-title">Payment received</h1>
            <p class="status-desc">Thank you. Your order is confirmed.</p>
            <p class="reference-id">
                Order ID: <strong><?= e(strtoupper(substr($orderId, 0, 8))) ?></strong>
            </p>
            <div class="order-actions">
                <a href="<?= e($homeLink) ?>" class="btn-primary">Continue shopping</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Details -->
        <?php if ($order): ?>
        <div class="order-details-card">
            <h2>Order details</h2>
            <div class="order-items-list">
                <?php foreach ($items as $item): ?>
                <div class="order-detail-item">
                    <?php if (!empty($item['image_url'])): ?>
                    <img src="<?= e($item['image_url']) ?>" alt="" class="order-item-thumb">
                    <?php endif; ?>
                    <div class="order-item-info">
                        <p><?= e($item['title']) ?></p>
                        <p class="text-muted">Qty <?= $item['quantity'] ?> · ₹<?= number_format($item['unit_price'], 0, '.', ',') ?> each</p>
                    </div>
                    <p class="order-item-total">₹<?= number_format($item['unit_price'] * $item['quantity'], 0, '.', ',') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-totals">
                <div class="price-line"><span>Subtotal</span><span>₹<?= number_format($order['subtotal'], 0, '.', ',') ?></span></div>
                <div class="price-line"><span>Shipping</span><span>₹<?= number_format($order['shipping_fee'], 0, '.', ',') ?></span></div>
                <div class="price-total"><span>Total</span><span>₹<?= number_format($order['total'], 0, '.', ',') ?></span></div>
            </div>

            <div class="order-address">
                <p><strong><?= e($order['customer_name']) ?></strong> · <?= e($order['customer_phone']) ?></p>
                <?php 
                $addr = is_string($order['shipping_address']) ? json_decode($order['shipping_address'], true) : $order['shipping_address'];
                if ($addr): 
                ?>
                <p><?= e(implode(', ', array_filter([
                    $addr['line1'] ?? '',
                    $addr['line2'] ?? '',
                    $addr['city'] ?? '',
                    $addr['state'] ?? '',
                    $addr['postal_code'] ?? '',
                ]))) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
