<?php
/**
 * Cart Page
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$pageTitle = 'Your Cart — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

$totals = cart_totals();
$items = $totals['items'];
$itemCount = $totals['count'];
$subtotal = $totals['subtotal'];
$mrpTotal = $totals['mrp_total'];
$discount = $totals['discount'];
$shipping = $totals['shipping'];
$total = $totals['total'];
$savings = $totals['savings'];

$homeLink = $tenant ? "/t/{$tenant['slug']}" : '/';
$checkoutLink = $tenant ? "/t/{$tenant['slug']}/checkout" : '/checkout';
$deliveryDate = date('D, j M', strtotime('+3 days'));

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
</head>
<body>
<main class="cart-page">
    <!-- Top bar -->
    <div class="page-topbar">
        <a href="<?= e($homeLink) ?>" class="back-btn" aria-label="Back">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
        <h1 class="page-title">Order Summary (<?= $itemCount ?>)</h1>
    </div>

    <?php if (empty($items)): ?>
    <!-- Empty cart -->
    <section class="empty-cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" x2="21" y1="6" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <p class="empty-title">Your cart is empty</p>
        <p class="empty-subtitle">Add items to get started</p>
        <a href="<?= e($homeLink) ?>" class="btn-primary">Continue shopping</a>
    </section>
    <?php else: ?>
    
    <div class="cart-layout">
        <section class="cart-items">
            <!-- Delivery info -->
            <div class="cart-card">
                <div class="deliver-row">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <p><strong>Deliver to</strong> — Add your address at checkout</p>
                    <a href="<?= e($checkoutLink) ?>" class="btn-outline-small">Change</a>
                </div>
            </div>

            <?php if ($savings > 0): ?>
            <div class="cart-card savings-card">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
                You will save ₹<?= number_format($savings, 0, '.', ',') ?> on this order
            </div>
            <?php endif; ?>

            <!-- Cart items -->
            <?php foreach ($items as $item): 
                $itemMrp = (!empty($item['mrp']) && $item['mrp'] > $item['unit_price']) ? $item['mrp'] : $item['unit_price'];
                $itemDiscount = max(0, $itemMrp - $item['unit_price']);
                $pct = $itemMrp > 0 ? round(($itemDiscount / $itemMrp) * 100) : 0;
                $productLink = $tenant 
                    ? "/t/{$tenant['slug']}/product/{$item['slug']}" 
                    : "/product/{$item['slug']}";
                $maxQty = (!empty($item['max_quantity']) && $item['max_quantity'] > 0) ? $item['max_quantity'] : 10;
            ?>
            <article class="cart-item-card">
                <div class="cart-item-body">
                    <div class="cart-item-image">
                        <a href="<?= e($productLink) ?>">
                            <?php if (!empty($item['image_url'])): ?>
                            <img src="<?= e(img_url($item['image_url'], ['w' => 240, 'h' => 240])) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                            <?php else: ?>
                            <div class="placeholder-img">📦</div>
                            <?php endif; ?>
                        </a>
                        <form method="POST" action="/" class="qty-form">
                            <input type="hidden" name="action" value="update_qty">
                            <input type="hidden" name="product_id" value="<?= e($item['product_id']) ?>">
                            <input type="hidden" name="redirect" value="<?= e(current_path()) ?>">
                            <select name="qty" onchange="this.form.submit()" class="qty-select">
                                <?php for ($n = 1; $n <= $maxQty; $n++): ?>
                                <option value="<?= $n ?>" <?= $item['quantity'] == $n ? 'selected' : '' ?>>Qty: <?= $n ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                    <div class="cart-item-details">
                        <div class="deal-badge">Early Bird Deal</div>
                        <a href="<?= e($productLink) ?>" class="cart-item-title"><?= e($item['title']) ?></a>
                        <?php if (!empty($item['rating']) && $item['rating'] > 0): ?>
                        <div class="item-rating">
                            <span class="rating-badge"><?= number_format($item['rating'], 1) ?> ★</span>
                            <?php if (!empty($item['rating_count'])): ?>
                            <span class="rating-count">(<?= number_format($item['rating_count']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="item-price-row">
                            <?php if ($pct > 0): ?>
                            <span class="item-discount">↓<?= $pct ?>%</span>
                            <?php endif; ?>
                            <?php if ($itemDiscount > 0): ?>
                            <span class="item-mrp">₹<?= number_format($itemMrp, 0, '.', ',') ?></span>
                            <?php endif; ?>
                            <span class="item-price">₹<?= number_format($item['unit_price'], 0, '.', ',') ?></span>
                        </div>
                        <p class="delivery-info">
                            <strong>EXPRESS</strong> Delivery by <?= $deliveryDate ?>
                        </p>
                    </div>
                </div>
                <div class="cart-item-actions">
                    <button class="item-action">Save for later</button>
                    <form method="POST" action="/" style="display:inline">
                        <input type="hidden" name="action" value="remove_from_cart">
                        <input type="hidden" name="product_id" value="<?= e($item['product_id']) ?>">
                        <input type="hidden" name="redirect" value="<?= e(current_path()) ?>">
                        <button type="submit" class="item-action danger">Remove</button>
                    </form>
                    <a href="<?= e($checkoutLink) ?>" class="item-action">Buy this now</a>
                </div>
            </article>
            <?php endforeach; ?>
        </section>

        <!-- Price details sidebar -->
        <aside class="price-sidebar">
            <div class="price-header">
                <h2>Price details</h2>
            </div>
            <div class="price-body">
                <div class="price-line">
                    <span>Price (<?= $itemCount ?> items)</span>
                    <span>₹<?= number_format($mrpTotal, 0, '.', ',') ?></span>
                </div>
                <?php if ($discount > 0): ?>
                <div class="price-line">
                    <span>Discount</span>
                    <span class="text-success">− ₹<?= number_format($discount, 0, '.', ',') ?></span>
                </div>
                <?php endif; ?>
                <div class="price-line">
                    <span>Delivery charges</span>
                    <span>
                        <?php if ($shipping === 0): ?>
                        <span class="text-success">FREE</span>
                        <?php else: ?>
                        ₹<?= $shipping ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="price-total">
                    <span>Total amount</span>
                    <span>₹<?= number_format($total, 0, '.', ',') ?></span>
                </div>
                <?php if ($subtotal < FREE_DELIVERY_THRESHOLD && $subtotal > 0): ?>
                <p class="free-delivery-hint">
                    Add ₹<?= number_format(FREE_DELIVERY_THRESHOLD - $subtotal, 0, '.', ',') ?> more for free delivery
                </p>
                <?php endif; ?>
            </div>
            <?php if ($savings > 0): ?>
            <div class="price-savings">
                You will save ₹<?= number_format($savings, 0, '.', ',') ?> on this order
            </div>
            <?php endif; ?>
            <div class="price-secure">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                Safe and secure checkout
            </div>
        </aside>
    </div>

    <!-- Mobile sticky footer -->
    <footer class="cart-sticky-footer">
        <div class="footer-price">
            <?php if ($mrpTotal > $total): ?>
            <p class="footer-mrp">₹<?= number_format($mrpTotal, 0, '.', ',') ?></p>
            <?php endif; ?>
            <p class="footer-total">₹<?= number_format($total, 0, '.', ',') ?></p>
        </div>
        <a href="<?= e($checkoutLink) ?>" class="btn-place-order">PLACE ORDER</a>
    </footer>

    <?php endif; ?>
</main>
</body>
</html>
