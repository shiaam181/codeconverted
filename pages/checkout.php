<?php
/**
 * Checkout Page
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$pageTitle = 'Checkout — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

$totals = cart_totals();
$items = $totals['items'];

if (empty($items)) {
    $cartLink = $tenant ? "/t/{$tenant['slug']}/cart" : '/cart';
    redirect($cartLink);
}

$subtotal = $totals['subtotal'];
$mrpTotal = $totals['mrp_total'];
$discount = $totals['discount'];
$shipping = $totals['shipping'];
$total = $totals['total'];
$savings = $totals['savings'];

$cartLink = $tenant ? "/t/{$tenant['slug']}/cart" : '/cart';
$homeLink = $tenant ? "/t/{$tenant['slug']}" : '/';

$theme = get_theme();
$upiMethods = get_upi_methods();

// Get UPI settings
$upiId = clean_upi_id($tenant['upi_id'] ?? $theme['upi_id'] ?? null);
$payeeName = trim($tenant['upi_payee_name'] ?? $theme['upi_payee_name'] ?? DEFAULT_SITE_NAME);
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
<div class="checkout-page">
    <!-- Header -->
    <header class="checkout-header">
        <div class="checkout-header-inner">
            <a href="<?= e($cartLink) ?>" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
            </a>
            <h1>Checkout</h1>
            <div style="width:20px"></div>
        </div>
        <!-- Stepper -->
        <div class="stepper">
            <div class="step active">
                <div class="step-circle active">1</div>
                <span class="step-label">Address</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-circle">2</div>
                <span class="step-label">Summary</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-circle">3</div>
                <span class="step-label">Payment</span>
            </div>
        </div>
    </header>

    <main class="checkout-main">
        <!-- Address Form -->
        <form method="POST" action="/" id="checkoutForm" class="checkout-form">
            <input type="hidden" name="action" value="place_order">
            <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
            <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
            
            <section class="checkout-section">
                <h2 class="section-heading">Delivery Address</h2>
                
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required placeholder="Enter full name" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required placeholder="10-digit mobile number" class="form-input" pattern="[0-9]{10}">
                </div>
                
                <div class="form-group">
                    <label for="flat">Flat / House No / Building *</label>
                    <input type="text" id="flat" name="flat" required placeholder="Flat, House no., Building" class="form-input">
                </div>
                
                <div class="form-group">
                    <label for="area">Area / Street / Sector *</label>
                    <input type="text" id="area" name="area" required placeholder="Area, Street, Sector, Village" class="form-input">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required placeholder="City" class="form-input">
                    </div>
                    <div class="form-group">
                        <label for="state">State *</label>
                        <input type="text" id="state" name="state" required placeholder="State" class="form-input">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="postal_code">PIN Code *</label>
                    <input type="text" id="postal_code" name="postal_code" required placeholder="6-digit PIN code" class="form-input" pattern="[0-9]{6}">
                </div>

                <div class="form-group">
                    <label>Address Type</label>
                    <div class="radio-group">
                        <label class="radio-label"><input type="radio" name="address_type" value="Home" checked> Home</label>
                        <label class="radio-label"><input type="radio" name="address_type" value="Work"> Work</label>
                    </div>
                </div>
            </section>

            <!-- Order Summary -->
            <section class="checkout-section">
                <h2 class="section-heading">Order Summary</h2>
                <div class="order-items-list">
                    <?php foreach ($items as $item): 
                        $lineMrp = (!empty($item['mrp']) && $item['mrp'] > $item['unit_price']) ? $item['mrp'] * $item['quantity'] : $item['unit_price'] * $item['quantity'];
                        $lineTotal = $item['unit_price'] * $item['quantity'];
                    ?>
                    <div class="order-item">
                        <?php if (!empty($item['image_url'])): ?>
                        <img src="<?= e(img_url($item['image_url'], ['w' => 200, 'h' => 200])) ?>" alt="" class="order-item-img">
                        <?php endif; ?>
                        <div class="order-item-info">
                            <p class="order-item-title"><?= e($item['title']) ?></p>
                            <p class="order-item-meta">Qty: <?= $item['quantity'] ?> · ₹<?= number_format($item['unit_price'], 0, '.', ',') ?> each</p>
                        </div>
                        <p class="order-item-total">₹<?= number_format($lineTotal, 0, '.', ',') ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Price Details -->
            <section class="checkout-section">
                <h2 class="section-heading">Price Details</h2>
                <div class="price-details">
                    <div class="price-line"><span>MRP</span><span>₹<?= number_format($mrpTotal, 0, '.', ',') ?></span></div>
                    <?php if ($discount > 0): ?>
                    <div class="price-line"><span>Discounts</span><span class="text-success">- ₹<?= number_format($discount, 0, '.', ',') ?></span></div>
                    <?php endif; ?>
                    <div class="price-line"><span>Delivery</span><span><?= $shipping === 0 ? '<span class="text-success">FREE</span>' : '₹' . $shipping ?></span></div>
                    <div class="price-total"><span>Total Amount</span><span>₹<?= number_format($total, 0, '.', ',') ?></span></div>
                </div>
                <?php if ($savings > 0): ?>
                <div class="savings-banner">
                    You will save ₹<?= number_format($savings, 0, '.', ',') ?> on this order
                </div>
                <?php endif; ?>
            </section>

            <!-- Payment Method -->
            <section class="checkout-section">
                <h2 class="section-heading">Payment Method</h2>
                
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="upi" checked>
                        <div class="payment-option-content">
                            <strong>UPI</strong>
                            <span class="text-muted">PhonePe, GPay, Paytm, BHIM</span>
                        </div>
                    </label>
                    
                    <?php if ($upiId): ?>
                    <div class="upi-apps" id="upiApps">
                        <p class="upi-label">Select UPI App:</p>
                        <div class="upi-app-grid">
                            <?php 
                            $apps = ['PhonePe', 'Google Pay', 'Paytm', 'BHIM'];
                            foreach ($apps as $app): 
                            ?>
                            <label class="upi-app-btn">
                                <input type="radio" name="upi_app" value="<?= e($app) ?>" <?= $app === 'PhonePe' ? 'checked' : '' ?>>
                                <span><?= e($app) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod">
                        <div class="payment-option-content">
                            <strong>Cash on Delivery</strong>
                            <span class="text-muted">Pay when you receive</span>
                        </div>
                    </label>
                </div>
            </section>

            <!-- Place Order -->
            <footer class="checkout-footer">
                <div class="checkout-footer-price">
                    <?php if ($mrpTotal > $total): ?>
                    <p class="footer-mrp">₹<?= number_format($mrpTotal, 0, '.', ',') ?></p>
                    <?php endif; ?>
                    <p class="footer-total">₹<?= number_format($total, 0, '.', ',') ?></p>
                </div>
                <button type="submit" class="btn-place-order">Place Order</button>
            </footer>
        </form>
    </main>
</div>
</body>
</html>
