<?php
/**
 * Checkout Page - Flipkart Style
 * Step 1: Address (with map)
 * Step 2: Order Summary (items + price details + donate)
 * Step 3: Payment
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
$siteName = $theme['site_name'] ?? DEFAULT_SITE_NAME;
$upiMethods = get_upi_methods();
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
    <!-- Leaflet CSS for map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
            <div class="step" id="step1indicator">
                <div class="step-circle active">1</div>
                <span class="step-label">Address</span>
            </div>
            <div class="step-line" id="line1"></div>
            <div class="step" id="step2indicator">
                <div class="step-circle">2</div>
                <span class="step-label">Order Summary</span>
            </div>
            <div class="step-line" id="line2"></div>
            <div class="step" id="step3indicator">
                <div class="step-circle">3</div>
                <span class="step-label">Payment</span>
            </div>
        </div>
    </header>

    <main class="checkout-main">
        <form method="POST" action="/" id="checkoutForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="place_order">
            <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
            <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
            
            <!-- STEP 1: Address with Map -->
            <div id="step1" class="checkout-step">
                <section class="checkout-section">
                    <h2 class="section-heading">← Add new address</h2>
                    
                    <!-- Map -->
                    <div id="map" style="width:100%; height:300px; border-radius:8px; margin-bottom:12px; background:#e0e0e0;"></div>
                    
                    <div class="map-actions">
                        <button type="button" class="btn-outline btn-full" onclick="useCurrentLocation()">📍 Use my current location</button>
                    </div>

                    <!-- Location prompt modal -->
                    <div id="locationPrompt" class="location-prompt" style="display:none;">
                        <div class="location-prompt-inner">
                            <p class="location-prompt-title">Where do you want us to deliver the order?</p>
                            <p class="text-muted text-sm">This will help with the right map location</p>
                            <button type="button" class="btn-primary btn-full" onclick="closeLocationPrompt()">Away from my location</button>
                            <button type="button" class="btn-outline btn-full" onclick="useCurrentLocation(); closeLocationPrompt();">📍 Use current location</button>
                        </div>
                    </div>
                </section>

                <section class="checkout-section">
                    <h2 class="section-heading">Delivery Address</h2>
                    
                    <input type="hidden" name="lat" id="lat" value="">
                    <input type="hidden" name="lng" id="lng" value="">
                    
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

                    <button type="button" class="btn-primary btn-full" onclick="goToStep(2)">Continue</button>
                </section>
            </div>

            <!-- STEP 2: Order Summary -->
            <div id="step2" class="checkout-step" style="display:none">
                <!-- Deliver to card -->
                <section class="checkout-section">
                    <div class="deliver-to-card">
                        <p class="text-muted text-sm">Deliver to:</p>
                        <button type="button" class="btn-outline-small" onclick="goToStep(1)" style="float:right">Change</button>
                        <p id="deliverName" class="deliver-name"></p>
                        <p id="deliverAddress" class="deliver-address"></p>
                        <p id="deliverPhone" class="deliver-phone"></p>
                    </div>
                </section>

                <!-- Items -->
                <section class="checkout-section">
                    <?php foreach ($items as $item): 
                        $lineMrp = (!empty($item['mrp']) && $item['mrp'] > $item['unit_price']) ? $item['mrp'] * $item['quantity'] : $item['unit_price'] * $item['quantity'];
                        $lineTotal = $item['unit_price'] * $item['quantity'];
                        $pct = $lineMrp > $lineTotal ? (int) round((($lineMrp - $lineTotal) / $lineMrp) * 100) : 0;
                    ?>
                    <div class="order-item-detail">
                        <p class="deal-label">Early Bird Deal</p>
                        <div class="order-item-row">
                            <div class="order-item-img-col">
                                <?php if (!empty($item['image_url'])): ?>
                                <img src="<?= e(img_url($item['image_url'], ['w' => 200, 'h' => 200])) ?>" alt="" class="order-item-thumb">
                                <?php endif; ?>
                                <div class="qty-badge">Qty: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="order-item-info-col">
                                <p class="order-item-title"><?= e($item['title']) ?></p>
                                <div class="order-item-price-row">
                                    <?php if ($pct > 0): ?>
                                    <span class="text-success font-bold">↓<?= $pct ?>%</span>
                                    <?php endif; ?>
                                    <?php if ($lineMrp > $lineTotal): ?>
                                    <span class="price-strike">₹<?= number_format($lineMrp, 0, '.', ',') ?></span>
                                    <?php endif; ?>
                                    <span class="price-bold">₹<?= number_format($lineTotal, 0, '.', ',') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </section>

                <!-- Donate -->
                <section class="checkout-section">
                    <p class="donate-title">Donate to <?= e($siteName) ?> Foundation</p>
                    <p class="text-muted text-sm">Support transformative social work</p>
                    <div class="donate-buttons">
                        <button type="button" class="donate-btn">₹10</button>
                        <button type="button" class="donate-btn">₹20</button>
                        <button type="button" class="donate-btn">₹50</button>
                        <button type="button" class="donate-btn">₹100</button>
                    </div>
                </section>

                <!-- Price Details -->
                <section class="checkout-section">
                    <div class="price-details">
                        <div class="price-line"><span>MRP</span><span>₹<?= number_format($mrpTotal, 0, '.', ',') ?></span></div>
                        <?php if ($discount > 0): ?>
                        <div class="price-line"><span>Discounts ▾</span><span class="text-success">₹<?= number_format($discount, 0, '.', ',') ?></span></div>
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
            </div>

            <!-- STEP 3: Payment -->
            <div id="step3" class="checkout-step" style="display:none">
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
                                <?php foreach (['PhonePe', 'Google Pay', 'Paytm', 'BHIM'] as $app): ?>
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

                <button type="submit" class="btn-place-order btn-full">Place Order · ₹<?= number_format($total, 0, '.', ',') ?></button>
            </div>
        </form>
    </main>

    <!-- Sticky footer for step 2 -->
    <footer class="checkout-footer" id="step2Footer" style="display:none">
        <div class="checkout-footer-price">
            <?php if ($mrpTotal > $total): ?>
            <p class="footer-mrp">₹<?= number_format($mrpTotal, 0, '.', ',') ?></p>
            <?php endif; ?>
            <p class="footer-total">₹<?= number_format($total, 0, '.', ',') ?></p>
            <button type="button" class="text-link text-sm">View price details</button>
        </div>
        <button type="button" class="btn-place-order" onclick="goToStep(3)">Continue</button>
    </footer>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var map, marker;
var currentStep = 1;

// Initialize map
function initMap() {
    map = L.map('map').setView([12.9716, 77.5946], 14);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19
    }).addTo(map);
    
    marker = L.marker([12.9716, 77.5946], { draggable: true }).addTo(map);
    
    // Click on map to move marker
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng.lat, e.latlng.lng);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
    
    // Drag marker
    marker.on('dragend', function(e) {
        var pos = marker.getLatLng();
        updateCoords(pos.lat, pos.lng);
        reverseGeocode(pos.lat, pos.lng);
    });

    // Show location prompt
    document.getElementById('locationPrompt').style.display = 'flex';
}

function updateCoords(lat, lng) {
    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lng;
}

function useCurrentLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude;
            var lng = pos.coords.longitude;
            map.setView([lat, lng], 16);
            marker.setLatLng([lat, lng]);
            updateCoords(lat, lng);
            reverseGeocode(lat, lng);
        }, function() {
            alert("Couldn't get your location. Please allow location access.");
        });
    }
}

function closeLocationPrompt() {
    document.getElementById('locationPrompt').style.display = 'none';
}

function reverseGeocode(lat, lng) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1')
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var a = data.address || {};
        var area = [a.suburb, a.neighbourhood, a.road].filter(Boolean).slice(0, 2).join(', ') || data.display_name.split(',')[0] || '';
        var city = a.city || a.town || a.village || a.county || '';
        var state = a.state || '';
        var pin = a.postcode || '';
        
        document.getElementById('area').value = area;
        document.getElementById('city').value = city;
        document.getElementById('state').value = state;
        document.getElementById('postal_code').value = pin;
    }).catch(function() {});
}

function goToStep(step) {
    currentStep = step;
    document.getElementById('step1').style.display = step === 1 ? '' : 'none';
    document.getElementById('step2').style.display = step === 2 ? '' : 'none';
    document.getElementById('step3').style.display = step === 3 ? '' : 'none';
    document.getElementById('step2Footer').style.display = step === 2 ? '' : 'none';

    // Update stepper UI
    var circles = document.querySelectorAll('.step-circle');
    circles.forEach(function(c, i) {
        c.classList.toggle('active', i < step);
        c.classList.toggle('done', i < step - 1);
        if (i < step - 1) c.innerHTML = '✓';
    });
    var lines = document.querySelectorAll('.step-line');
    lines.forEach(function(l, i) { l.classList.toggle('active', i < step - 1); });

    // Update deliver-to card for step 2
    if (step === 2) {
        document.getElementById('deliverName').textContent = document.getElementById('name').value;
        document.getElementById('deliverAddress').textContent = [
            document.getElementById('flat').value,
            document.getElementById('area').value,
            document.getElementById('city').value,
            document.getElementById('state').value,
            document.getElementById('postal_code').value
        ].filter(Boolean).join(', ');
        document.getElementById('deliverPhone').textContent = document.getElementById('phone').value;
    }

    window.scrollTo(0, 0);
}

document.addEventListener('DOMContentLoaded', initMap);
</script>
</body>
</html>
