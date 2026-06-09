<?php
/**
 * Checkout Page - Exact Flipkart/React style
 * Flow: Step 1 (Address: List → Map → Form) → Step 2 (Order Summary) → Step 3 (Payment)
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$pageTitle = 'Checkout — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

$totals = cart_totals();
$items = $totals['items'];
if (empty($items)) {
    redirect($tenant ? "/t/{$tenant['slug']}/cart" : '/cart');
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
$upiId = clean_upi_id($tenant['upi_id'] ?? $theme['upi_id'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
    .checkout-page{min-height:100vh;background:#f1f3f6}
    .co-header{position:sticky;top:0;z-index:30;background:#fff;border-bottom:1px solid #eee;box-shadow:0 1px 3px rgba(0,0,0,.04)}
    .co-header-inner{max-width:800px;margin:0 auto;padding:12px;display:flex;align-items:center;justify-content:space-between}
    .co-header h1{font-size:16px;font-weight:600}
    .stepper{max-width:800px;margin:0 auto;padding:12px;display:flex;align-items:center}
    .step{display:flex;flex-direction:column;align-items:center}
    .step-circle{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:500;border:2px solid #c2c2c2;color:#878787;background:#fff}
    .step-circle.active{background:#2874f0;border-color:#2874f0;color:#fff}
    .step-label{font-size:11px;margin-top:4px;color:#878787}
    .step.active .step-label{font-weight:600;color:#212121}
    .step-line{flex:1;height:2px;background:#e0e0e0;margin:0 8px;margin-bottom:18px}
    .step-line.done{background:#2874f0}
    
    /* Map view */
    .map-wrapper{position:relative}
    .map-search{position:absolute;top:12px;left:12px;right:12px;z-index:500}
    .map-search-input{width:100%;background:#fff;border-radius:24px;box-shadow:0 2px 8px rgba(0,0,0,.12);padding:10px 16px;display:flex;align-items:center;gap:8px}
    .map-search-input input{flex:1;border:none;outline:none;font-size:14px}
    .map-search-results{background:#fff;margin-top:4px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.12);max-height:240px;overflow:auto}
    .map-search-results button{width:100%;text-align:left;padding:8px 12px;border:none;background:none;border-bottom:1px solid #f5f5f5;cursor:pointer;font-size:13px}
    .map-search-results button:hover{background:#f7f7f7}
    .map-search-results button p.sub{font-size:11px;color:#878787;margin-top:2px}
    #mapContainer{height:55vh;width:100%}
    .map-locate-btn{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);z-index:500;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.12);border-radius:24px;padding:8px 16px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#2874f0;border:none;cursor:pointer}
    
    /* Location prompt (bottom sheet) */
    .location-sheet{position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,.4);display:flex;align-items:flex-end}
    .location-sheet-inner{background:#fff;width:100%;border-radius:16px 16px 0 0;padding:24px 20px;padding-bottom:max(24px,env(safe-area-inset-bottom))}
    .location-sheet-inner p.title{font-size:16px;font-weight:600;margin-bottom:4px}
    .location-sheet-inner p.sub{font-size:13px;color:#878787;margin-bottom:16px}
    .location-sheet-inner .btn{width:100%;padding:14px;border-radius:6px;font-size:15px;font-weight:600;border:none;cursor:pointer;margin-bottom:10px}
    .location-sheet-inner .btn-blue{background:#2874f0;color:#fff}
    .location-sheet-inner .btn-outline{background:#fff;color:#2874f0;border:1.5px solid #2874f0}
    
    /* Deliver-to bottom card (on map) */
    .map-bottom-card{background:#fff;padding:16px}
    .map-bottom-card .area-box{background:#f7f7f7;border-radius:6px;padding:12px;display:flex;align-items:flex-start;gap:10px;margin:12px 0}
    .map-bottom-card .area-box .area-text p{font-size:14px;font-weight:600;color:#212121}
    .map-bottom-card .area-box .area-text .sub{font-size:12px;color:#878787;margin-top:2px}
    
    /* Address form */
    .addr-form{background:#fff;padding:16px}
    .addr-form .flat-input{border:2px solid #2874f0;border-radius:6px;padding:8px 12px;margin-bottom:12px}
    .addr-form .flat-input label{font-size:11px;color:#2874f0;font-weight:500}
    .addr-form .flat-input input{width:100%;border:none;outline:none;font-size:14px;padding:4px 0}
    .addr-form .area-readonly{background:#f7f7f7;border-radius:6px;padding:12px;margin-bottom:12px;display:flex;align-items:flex-start;gap:10px;justify-content:space-between}
    .addr-form .area-readonly .info p.label{font-size:11px;color:#878787}
    .addr-form .area-readonly .info p.value{font-size:13px;color:#212121;margin-top:2px}
    .addr-form .area-readonly .info p.bold{font-size:13px;font-weight:600;color:#212121}
    .addr-form .input-row{margin-bottom:12px}
    .addr-form .input-row input{width:100%;padding:12px;border:1px solid #d0d0d0;border-radius:6px;font-size:14px;outline:none}
    .addr-form .input-row input:focus{border-color:#2874f0}
    .addr-form .type-row{margin-bottom:16px}
    .addr-form .type-row p{font-size:12px;color:#212121;margin-bottom:8px}
    .addr-form .type-btns{display:flex;gap:8px}
    .addr-form .type-btn{padding:8px 16px;border-radius:4px;border:1px solid #c2c2c2;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:6px;background:#fff;color:#212121}
    .addr-form .type-btn.active{border-color:#2874f0;color:#2874f0;background:#e3f2fd}
    .addr-form .info-banner{background:#fff5e6;border:1px solid #ffd9a8;border-radius:6px;padding:10px 12px;display:flex;gap:8px;margin-bottom:12px;font-size:12px;color:#b25e09}
    
    /* Order summary step */
    .order-section{background:#fff;border-radius:6px;margin-bottom:8px;padding:16px}
    .deliver-card{margin-bottom:8px}
    .deliver-card .label{font-size:13px;color:#878787}
    .deliver-card .name-row{display:flex;align-items:center;gap:8px;margin-top:4px}
    .deliver-card .name-row .name{font-size:14px;font-weight:600}
    .deliver-card .name-row .badge{font-size:10px;font-weight:600;background:#f0f0f0;color:#878787;padding:2px 6px;border-radius:3px}
    .deliver-card .addr-text{font-size:13px;color:#212121;margin-top:4px;line-height:1.4}
    .deliver-card .phone-text{font-size:13px;color:#212121;margin-top:4px}
    .item-card{border-bottom:1px solid #f0f0f0;padding:16px 0}
    .item-card:last-child{border-bottom:none}
    .item-card .deal-tag{font-size:12px;color:#388e3c;font-weight:500;margin-bottom:8px}
    .item-card .item-row{display:flex;gap:12px}
    .item-card .item-img{width:80px;flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:8px}
    .item-card .item-img img{width:80px;height:80px;object-fit:contain;background:#fff}
    .item-card .qty-box{border:1px solid #c2c2c2;border-radius:4px;padding:4px 8px;font-size:12px;text-align:center}
    .item-card .item-info{flex:1}
    .item-card .item-title{font-size:14px;line-height:1.4;color:#212121}
    .item-card .item-prices{display:flex;align-items:baseline;gap:8px;margin-top:6px;flex-wrap:wrap}
    .donate-section{padding:16px 0}
    .donate-section .donate-title{font-size:14px;font-weight:500}
    .donate-section .donate-sub{font-size:12px;color:#878787}
    .donate-btns{display:flex;gap:8px;margin-top:10px}
    .donate-btns button{padding:6px 16px;border:1px solid #c2c2c2;border-radius:24px;background:#fff;font-size:13px;cursor:pointer}
    .donate-btns button:hover{border-color:#2874f0}
    .price-section .row{display:flex;justify-content:space-between;font-size:13px;margin-bottom:10px}
    .price-section .total-row{display:flex;justify-content:space-between;font-size:14px;font-weight:600;border-top:1px solid #eee;padding-top:10px;margin-top:4px}
    .savings-bar{background:#e8f5e9;color:#1b5e20;font-size:13px;text-align:center;padding:8px;border-radius:4px;margin-top:12px}
    
    /* Sticky footer */
    .co-footer{position:fixed;bottom:0;left:0;right:0;z-index:20;background:#fff;border-top:1px solid #eee;box-shadow:0 -2px 8px rgba(0,0,0,.06);padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:12px}
    .co-footer .price-col .mrp{font-size:13px;color:#878787;text-decoration:line-through}
    .co-footer .price-col .total{font-size:18px;font-weight:600}
    .co-footer .price-col .link{font-size:12px;color:#2874f0}
    .co-footer .continue-btn{background:#fb641b;color:#fff;border:none;padding:12px 28px;border-radius:4px;font-size:15px;font-weight:600;cursor:pointer}
    .co-footer .continue-btn:hover{background:#f55a0e}
    </style>
</head>
<body>
<div class="checkout-page">
    <header class="co-header">
        <div class="co-header-inner">
            <a href="<?= e($cartLink) ?>"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg></a>
            <h1>Checkout</h1>
            <div style="width:20px"></div>
        </div>
        <div class="stepper" id="stepperUI">
            <div class="step active"><div class="step-circle active">1</div><span class="step-label">Address</span></div>
            <div class="step-line"></div>
            <div class="step"><div class="step-circle">2</div><span class="step-label">Order Summary</span></div>
            <div class="step-line"></div>
            <div class="step"><div class="step-circle">3</div><span class="step-label">Payment</span></div>
        </div>
    </header>

    <!-- ═══ STEP 1A: MAP VIEW ═══ -->
    <div id="viewMap" style="display:none">
        <div class="co-header-inner" style="background:#fff;padding:12px">
            <button onclick="showView('list')" style="background:none;border:none;cursor:pointer"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg></button>
            <span style="font-size:16px;font-weight:600">Add new address</span>
            <div style="width:20px"></div>
        </div>
        <div class="map-wrapper">
            <div class="map-search">
                <div class="map-search-input">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#878787" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" id="mapSearchInput" placeholder="Search by area, name, street." oninput="doMapSearch(this.value)">
                </div>
                <div class="map-search-results" id="mapSearchResults" style="display:none"></div>
            </div>
            <div id="mapContainer"></div>
            <button type="button" class="map-locate-btn" onclick="useCurrentLocation()">📍 Use my current location</button>
        </div>
        <div class="map-bottom-card">
            <div class="area-box">
                <span style="font-size:18px;margin-top:2px">📍</span>
                <div class="area-text">
                    <p id="mapAreaName">Pick a location on the map</p>
                    <p class="sub" id="mapAreaDetail"></p>
                </div>
                <button onclick="showView('map')" style="font-size:12px;color:#2874f0;font-weight:500;border:1px solid #2874f0;padding:4px 12px;border-radius:4px;background:#fff;cursor:pointer">Change</button>
            </div>
            <button type="button" onclick="showView('form')" id="addDetailsBtn" class="continue-btn" style="width:100%;padding:14px;background:#2874f0;color:#fff;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer" disabled>Add address Details</button>
        </div>
    </div>

    <!-- ═══ STEP 1B: ADDRESS FORM ═══ -->
    <div id="viewForm" style="display:none">
        <form method="POST" action="/" id="checkoutForm">
            <input type="hidden" name="action" value="place_order">
            <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
            <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
            <input type="hidden" name="lat" id="f_lat" value="">
            <input type="hidden" name="lng" id="f_lng" value="">
            
            <div class="addr-form">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                    <p style="font-size:15px;font-weight:600">Deliver To</p>
                    <button type="button" onclick="showView('map')" style="background:none;border:none;font-size:18px;cursor:pointer">✕</button>
                </div>
                
                <div class="info-banner">
                    <span>ℹ️</span>
                    <span>Ensure your address details are accurate for a smooth delivery experience</span>
                </div>
                
                <div class="flat-input">
                    <label>Flat/House/building name *</label>
                    <input type="text" id="f_flat" name="flat" required>
                </div>
                
                <div class="area-readonly">
                    <div class="info">
                        <p class="label">Area / Sector / Locality</p>
                        <p class="value" id="formArea"></p>
                        <p class="bold" id="formCityState"></p>
                    </div>
                    <button type="button" onclick="showView('map')" style="font-size:12px;color:#2874f0;font-weight:500;border:1px solid #2874f0;padding:4px 12px;border-radius:4px;background:#fff;cursor:pointer">Change</button>
                </div>
                
                <input type="hidden" name="area" id="f_area">
                <input type="hidden" name="city" id="f_city">
                <input type="hidden" name="state" id="f_state">
                <input type="hidden" name="postal_code" id="f_postal">
                
                <div class="input-row"><input type="text" name="name" id="f_name" placeholder="Enter your full name *" required></div>
                <div class="input-row"><input type="tel" name="phone" id="f_phone" placeholder="10-digit mobile number *" required pattern="[0-9]{10}"></div>
                <div class="input-row"><input type="tel" name="alt_phone" placeholder="Alternate phone number (Optional)"></div>
                
                <div class="type-row">
                    <p>Type of address</p>
                    <div class="type-btns">
                        <button type="button" class="type-btn active" onclick="setAddrType(this,'Home')">🏠 Home</button>
                        <button type="button" class="type-btn" onclick="setAddrType(this,'Work')">🏢 Work</button>
                    </div>
                    <input type="hidden" name="address_type" id="f_type" value="Home">
                </div>
                
                <!-- Payment will be added to this form in step 3 -->
                <input type="hidden" name="payment_method" id="f_payment" value="upi">
                <input type="hidden" name="upi_app" id="f_upi_app" value="PhonePe">
                
                <button type="button" onclick="saveAddress()" style="width:100%;padding:14px;background:#2874f0;color:#fff;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer">Save address</button>
            </div>
        </form>
    </div>

    <!-- ═══ STEP 2: ORDER SUMMARY ═══ -->
    <div id="viewSummary" style="display:none;padding:12px;max-width:800px;margin:0 auto;padding-bottom:100px">
        <div class="order-section deliver-card">
            <div style="display:flex;justify-content:space-between"><span class="label">Deliver to:</span><button type="button" onclick="showView('form')" style="font-size:13px;font-weight:500;color:#2874f0;border:1px solid #2874f0;padding:4px 16px;border-radius:4px;background:#fff;cursor:pointer">Change</button></div>
            <div class="name-row"><span class="name" id="sumName"></span><span class="badge" id="sumType">HOME</span></div>
            <p class="addr-text" id="sumAddr"></p>
            <p class="phone-text" id="sumPhone"></p>
        </div>
        
        <div class="order-section">
            <?php foreach ($items as $item): 
                $lineMrp = (!empty($item['mrp']) && $item['mrp'] > $item['unit_price']) ? $item['mrp'] * $item['quantity'] : $item['unit_price'] * $item['quantity'];
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $pct = $lineMrp > $lineTotal ? (int) round((($lineMrp - $lineTotal) / $lineMrp) * 100) : 0;
            ?>
            <div class="item-card">
                <p class="deal-tag">Early Bird Deal</p>
                <div class="item-row">
                    <div class="item-img">
                        <?php if (!empty($item['image_url'])): ?>
                        <img src="<?= e(img_url($item['image_url'], ['w' => 200, 'h' => 200])) ?>" alt="">
                        <?php endif; ?>
                        <div class="qty-box">Qty: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="item-info">
                        <p class="item-title"><?= e($item['title']) ?></p>
                        <div class="item-prices">
                            <?php if ($pct > 0): ?><span style="font-size:13px;color:#388e3c;font-weight:600">↓<?= $pct ?>%</span><?php endif; ?>
                            <?php if ($lineMrp > $lineTotal): ?><span style="font-size:12px;color:#878787;text-decoration:line-through">₹<?= number_format($lineMrp, 0, '.', ',') ?></span><?php endif; ?>
                            <span style="font-size:15px;font-weight:600">₹<?= number_format($lineTotal, 0, '.', ',') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="order-section donate-section">
            <p class="donate-title">Donate to <?= e($siteName) ?> Foundation</p>
            <p class="donate-sub">Support transformative social work</p>
            <div class="donate-btns"><button type="button">₹10</button><button type="button">₹20</button><button type="button">₹50</button><button type="button">₹100</button></div>
        </div>
        
        <div class="order-section price-section">
            <div class="row"><span>MRP</span><span>₹<?= number_format($mrpTotal, 0, '.', ',') ?></span></div>
            <?php if ($discount > 0): ?><div class="row"><span>Discounts ▾</span><span style="color:#388e3c">₹<?= number_format($discount, 0, '.', ',') ?></span></div><?php endif; ?>
            <div class="row"><span>Delivery</span><span><?= $shipping === 0 ? '<span style="color:#388e3c;font-weight:500">FREE</span>' : '₹' . $shipping ?></span></div>
            <div class="total-row"><span>Total Amount</span><span>₹<?= number_format($total, 0, '.', ',') ?></span></div>
            <?php if ($savings > 0): ?><div class="savings-bar">You will save ₹<?= number_format($savings, 0, '.', ',') ?> on this order</div><?php endif; ?>
        </div>
    </div>

    <!-- ═══ STEP 3: PAYMENT ═══ -->
    <div id="viewPayment" style="display:none;padding:12px;max-width:800px;margin:0 auto;padding-bottom:100px">
        <div class="order-section">
            <h2 style="font-size:15px;font-weight:600;margin-bottom:12px">Payment Method</h2>
            <div class="payment-options">
                <label class="payment-option"><input type="radio" name="pm" value="upi" checked onchange="document.getElementById('f_payment').value='upi'"><div class="payment-option-content"><strong>UPI</strong><span class="text-muted">PhonePe, GPay, Paytm, BHIM</span></div></label>
                <?php if ($upiId): ?>
                <div style="padding:8px 0 8px 28px">
                    <div class="upi-app-grid">
                        <?php foreach (['PhonePe', 'Google Pay', 'Paytm', 'BHIM'] as $app): ?>
                        <label class="upi-app-btn"><input type="radio" name="ua" value="<?= e($app) ?>" <?= $app === 'PhonePe' ? 'checked' : '' ?> onchange="document.getElementById('f_upi_app').value=this.value"><span><?= e($app) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <label class="payment-option"><input type="radio" name="pm" value="cod" onchange="document.getElementById('f_payment').value='cod'"><div class="payment-option-content"><strong>Cash on Delivery</strong><span class="text-muted">Pay when you receive</span></div></label>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('checkoutForm').submit()" style="width:100%;padding:14px;background:#fb641b;color:#fff;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer;margin-top:12px">Place Order · ₹<?= number_format($total, 0, '.', ',') ?></button>
    </div>

    <!-- Sticky footer (step 2) -->
    <footer class="co-footer" id="coFooter" style="display:none">
        <div class="price-col">
            <?php if ($mrpTotal > $total): ?><p class="mrp">₹<?= number_format($mrpTotal, 0, '.', ',') ?></p><?php endif; ?>
            <p class="total">₹<?= number_format($total, 0, '.', ',') ?></p>
            <p class="link">View price details</p>
        </div>
        <button type="button" class="continue-btn" onclick="goToPayment()">Continue</button>
    </footer>

    <!-- Location prompt sheet -->
    <div class="location-sheet" id="locationSheet" style="display:none">
        <div class="location-sheet-inner">
            <p class="title">Where do you want us to deliver the order?</p>
            <p class="sub">This will help with the right map location</p>
            <button type="button" class="btn btn-blue" onclick="closeSheet()">Away from my location</button>
            <button type="button" class="btn btn-outline" onclick="useCurrentLocation();closeSheet()">📍 Use current location</button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var map, marker, pinLat=0, pinLng=0, areaData={area:'',city:'',state:'',postal:''};

function initMap(){
    map=L.map('mapContainer',{zoomControl:false}).setView([12.9716,77.5946],14);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19,attribution:'Esri'}).addTo(map);
    var icon=L.divIcon({className:'',html:'<div style="position:relative;display:flex;flex-direction:column;align-items:center"><div style="background:#212121;color:white;font-size:11px;font-weight:600;padding:4px 10px;border-radius:6px;white-space:nowrap;margin-bottom:4px">Place pin on the exact location</div><svg width="34" height="42" viewBox="0 0 24 30" fill="#212121"><path d="M12 0C5.4 0 0 5.4 0 12c0 8 12 18 12 18s12-10 12-18c0-6.6-5.4-12-12-12z"/><circle cx="12" cy="12" r="4" fill="white"/></svg></div>',iconSize:[34,80],iconAnchor:[17,70]});
    marker=L.marker([12.9716,77.5946],{icon:icon,draggable:true}).addTo(map);
    map.on('click',function(e){marker.setLatLng(e.latlng);setPin(e.latlng.lat,e.latlng.lng)});
    marker.on('dragend',function(){var p=marker.getLatLng();setPin(p.lat,p.lng)});
    document.getElementById('locationSheet').style.display='flex';
}

function setPin(lat,lng){
    pinLat=lat;pinLng=lng;
    document.getElementById('f_lat').value=lat;
    document.getElementById('f_lng').value=lng;
    reverseGeocode(lat,lng);
    document.getElementById('addDetailsBtn').disabled=false;
}

function reverseGeocode(lat,lng){
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&zoom=18&addressdetails=1')
    .then(function(r){return r.json()}).then(function(data){
        var a=data.address||{};
        areaData.area=[a.suburb,a.neighbourhood,a.road].filter(Boolean).slice(0,2).join(', ')||data.display_name.split(',')[0]||'';
        areaData.city=a.city||a.town||a.village||a.county||'';
        areaData.state=a.state||'';
        areaData.postal=a.postcode||'';
        document.getElementById('mapAreaName').textContent=areaData.area||'Location selected';
        document.getElementById('mapAreaDetail').textContent=[areaData.city,areaData.state,areaData.postal].filter(Boolean).join(', ');
    }).catch(function(){});
}

function useCurrentLocation(){
    if(!navigator.geolocation){
        document.getElementById('mapSearchInput').focus();
        return;
    }
    navigator.geolocation.getCurrentPosition(function(p){
        var lat=p.coords.latitude,lng=p.coords.longitude;
        map.setView([lat,lng],16);marker.setLatLng([lat,lng]);setPin(lat,lng);
    },function(){
        document.getElementById('mapSearchInput').placeholder='Search your location manually';
        document.getElementById('mapSearchInput').focus();
    });
}

function closeSheet(){document.getElementById('locationSheet').style.display='none'}

function doMapSearch(q){
    var res=document.getElementById('mapSearchResults');
    if(q.length<3){res.style.display='none';return}
    fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(q)+'&limit=5&addressdetails=1&countrycodes=in')
    .then(function(r){return r.json()}).then(function(data){
        if(!data.length){res.style.display='none';return}
        res.innerHTML='';res.style.display='block';
        data.forEach(function(r){
            var btn=document.createElement('button');
            btn.innerHTML='<p>'+r.display_name.split(',')[0]+'</p><p class="sub">'+r.display_name+'</p>';
            btn.onclick=function(){
                map.setView([parseFloat(r.lat),parseFloat(r.lon)],16);
                marker.setLatLng([parseFloat(r.lat),parseFloat(r.lon)]);
                setPin(parseFloat(r.lat),parseFloat(r.lon));
                document.getElementById('mapSearchInput').value=r.display_name.split(',')[0];
                res.style.display='none';
            };
            res.appendChild(btn);
        });
    });
}

function showView(v){
    ['viewMap','viewForm','viewSummary','viewPayment'].forEach(function(id){document.getElementById(id).style.display='none'});
    document.getElementById('coFooter').style.display='none';
    if(v==='map'){document.getElementById('viewMap').style.display='';setTimeout(function(){map.invalidateSize()},100)}
    if(v==='form'){
        document.getElementById('viewForm').style.display='';
        document.getElementById('f_area').value=areaData.area;
        document.getElementById('f_city').value=areaData.city;
        document.getElementById('f_state').value=areaData.state;
        document.getElementById('f_postal').value=areaData.postal;
        document.getElementById('formArea').textContent=areaData.area;
        document.getElementById('formCityState').textContent=[areaData.city,areaData.state,areaData.postal].filter(Boolean).join(', ');
    }
    if(v==='summary'){document.getElementById('viewSummary').style.display='';document.getElementById('coFooter').style.display='flex';updateStepper(2)}
    if(v==='payment'){document.getElementById('viewPayment').style.display='';updateStepper(3)}
    if(v==='map'||v==='form')updateStepper(1);
    window.scrollTo(0,0);
}

function saveAddress(){
    var name=document.getElementById('f_name').value,phone=document.getElementById('f_phone').value,flat=document.getElementById('f_flat').value;
    if(!name||!phone||!flat){alert('Please fill name, phone, and flat/house');return}
    document.getElementById('sumName').textContent=name;
    document.getElementById('sumType').textContent=document.getElementById('f_type').value.toUpperCase();
    document.getElementById('sumAddr').textContent=[flat,areaData.area,areaData.city,areaData.state,areaData.postal].filter(Boolean).join(', ');
    document.getElementById('sumPhone').textContent=phone;
    showView('summary');
}

function goToPayment(){showView('payment')}

function setAddrType(btn,type){
    document.querySelectorAll('.type-btn').forEach(function(b){b.classList.remove('active')});
    btn.classList.add('active');
    document.getElementById('f_type').value=type;
}

function updateStepper(step){
    var circles=document.querySelectorAll('.step-circle');
    var lines=document.querySelectorAll('.step-line');
    circles.forEach(function(c,i){c.classList.toggle('active',i<step);if(i<step-1)c.innerHTML='✓'});
    lines.forEach(function(l,i){l.classList.toggle('done',i<step-1)});
}

document.addEventListener('DOMContentLoaded',function(){showView('map');initMap()});
</script>
</body>
</html>
