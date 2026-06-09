<?php
/**
 * Checkout Page - Exact Flipkart Mobile UI
 * Step 1: Address (Map + Form)
 * Step 2: Order Summary
 * Step 3: Payment (Flipkart payment page style)
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$pageTitle = 'Checkout — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

$totals = cart_totals();
$items = $totals['items'];
if (empty($items)) { redirect($tenant ? "/t/{$tenant['slug']}/cart" : '/cart'); }

$subtotal = $totals['subtotal'];
$mrpTotal = $totals['mrp_total'];
$discount = $totals['discount'];
$shipping = $totals['shipping'];
$total = $totals['total'];
$savings = $totals['savings'];

$cartLink = $tenant ? "/t/{$tenant['slug']}/cart" : '/cart';
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f1f3f6;color:#212121;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button{font:inherit;cursor:pointer;border:none;background:none}
input,select,textarea{font:inherit}

/* Header */
.co-hdr{position:sticky;top:0;z-index:50;background:#fff}
.co-hdr-top{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f0f0f0}
.co-hdr-top h1{font-size:16px;font-weight:600}
.co-hdr-top .step-info{font-size:11px;color:#878787}

/* Stepper */
.stepper{display:flex;align-items:center;padding:18px 20px;background:#fff}
.stp{display:flex;flex-direction:column;align-items:center;min-width:50px}
.stp-circle{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;border:2.5px solid #c2c2c2;color:#878787;background:#fff;transition:all .2s}
.stp-circle.active{background:#2874f0;border-color:#2874f0;color:#fff}
.stp-circle.done{background:#2874f0;border-color:#2874f0;color:#fff}
.stp-label{font-size:10px;margin-top:5px;color:#878787;white-space:nowrap}
.stp.active .stp-label{font-weight:600;color:#212121}
.stp-line{flex:1;height:2.5px;background:#e0e0e0;margin:0 6px;margin-bottom:16px;border-radius:2px}
.stp-line.done{background:#2874f0}

/* ═══ MAP VIEW ═══ */
.map-hdr{background:#fff;padding:12px 16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #f0f0f0}
.map-hdr h2{font-size:15px;font-weight:600}
.map-wrap{position:relative}
#mapEl{height:50vh;width:100%}
.map-searchbar{position:absolute;top:10px;left:10px;right:10px;z-index:500}
.map-searchbar input{width:100%;padding:11px 16px 11px 40px;border-radius:24px;border:none;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.12);font-size:14px;outline:none}
.map-searchbar svg{position:absolute;left:14px;top:12px;color:#878787}
.map-searchbar .results{background:#fff;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.12);margin-top:6px;max-height:200px;overflow:auto;display:none}
.map-searchbar .results.show{display:block}
.map-searchbar .results button{width:100%;text-align:left;padding:10px 14px;border:none;background:none;border-bottom:1px solid #f7f7f7;font-size:13px}
.map-searchbar .results button:hover{background:#f5f8ff}
.map-searchbar .results button .sub{font-size:11px;color:#878787;margin-top:2px;display:block}
.map-locate{position:absolute;bottom:14px;left:50%;transform:translateX(-50%);z-index:500;background:#fff;padding:9px 18px;border-radius:24px;box-shadow:0 2px 10px rgba(0,0,0,.15);display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#2874f0;border:none}
.map-bottom{background:#fff;padding:16px}
.map-bottom .deliver-label{font-size:15px;font-weight:600;margin-bottom:10px}
.map-bottom .area-card{background:#f7f7f7;border-radius:8px;padding:12px 14px;display:flex;gap:10px;align-items:flex-start;margin-bottom:14px}
.map-bottom .area-card .pin{font-size:18px;margin-top:2px}
.map-bottom .area-card .text p{font-size:14px;font-weight:600;color:#212121}
.map-bottom .area-card .text .sub{font-size:12px;color:#878787;margin-top:2px}
.map-bottom .area-card .change-btn{margin-left:auto;font-size:12px;color:#2874f0;font-weight:500;border:1px solid #2874f0;padding:5px 14px;border-radius:5px;background:#fff;flex-shrink:0}
.map-bottom .continue-btn{width:100%;padding:15px;background:#2874f0;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700}
.map-bottom .continue-btn:disabled{opacity:.5}

/* Location sheet */
.loc-sheet{position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.5);display:none;align-items:flex-end;justify-content:center}
.loc-sheet.show{display:flex}
.loc-sheet-inner{background:#fff;width:100%;max-width:480px;border-radius:18px 18px 0 0;padding:28px 22px 32px}
.loc-sheet-inner .title{font-size:17px;font-weight:700;margin-bottom:4px}
.loc-sheet-inner .sub{font-size:13px;color:#878787;margin-bottom:20px}
.loc-sheet-inner .btn-away{width:100%;padding:15px;background:#2874f0;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;margin-bottom:12px}
.loc-sheet-inner .btn-use{width:100%;padding:15px;background:#fff;color:#2874f0;border:2px solid #2874f0;border-radius:8px;font-size:15px;font-weight:700;display:flex;align-items:center;justify-content:center;gap:8px}

/* ═══ FORM VIEW ═══ */
.form-view{background:#fff;padding:20px 16px;display:none}
.form-view.show{display:block}
.form-view .form-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.form-view .form-title h2{font-size:15px;font-weight:600}
.form-view .info-bar{background:#fff5e6;border:1px solid #ffd9a8;border-radius:8px;padding:10px 14px;display:flex;gap:8px;margin-bottom:16px;font-size:12px;color:#b25e09;align-items:flex-start}
.form-view .flat-field{border:2px solid #2874f0;border-radius:8px;padding:8px 14px;margin-bottom:14px}
.form-view .flat-field label{font-size:11px;color:#2874f0;font-weight:600;display:block}
.form-view .flat-field input{width:100%;border:none;outline:none;font-size:14px;padding:4px 0}
.form-view .area-ro{background:#f7f7f7;border-radius:8px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;justify-content:space-between;align-items:flex-start}
.form-view .area-ro .info .lbl{font-size:11px;color:#878787}
.form-view .area-ro .info .val{font-size:13px;color:#212121;margin-top:2px}
.form-view .area-ro .info .bold{font-size:13px;font-weight:600;color:#212121;margin-top:1px}
.form-view .area-ro .ch-btn{font-size:12px;color:#2874f0;font-weight:500;border:1px solid #2874f0;padding:5px 14px;border-radius:5px;background:#fff;flex-shrink:0}
.fi{margin-bottom:14px}
.fi input{width:100%;padding:13px 14px;border:1px solid #d0d0d0;border-radius:8px;font-size:14px;outline:none;transition:border .15s}
.fi input:focus{border-color:#2874f0}
.fi input::placeholder{color:#aaa}
.type-row{margin-bottom:18px}
.type-row p{font-size:12px;margin-bottom:8px}
.type-btns{display:flex;gap:10px}
.type-btn{padding:9px 18px;border-radius:6px;border:1px solid #c2c2c2;font-size:13px;font-weight:500;display:flex;align-items:center;gap:6px;background:#fff}
.type-btn.sel{border-color:#2874f0;color:#2874f0;background:#e3f2fd}
.save-btn{width:100%;padding:15px;background:#2874f0;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700}

/* ═══ SUMMARY VIEW ═══ */
.summary-view{display:none;padding:10px 12px;padding-bottom:100px}
.summary-view.show{display:block}
.s-card{background:#fff;border-radius:8px;padding:16px;margin-bottom:8px}
.s-deliver .lbl{font-size:13px;color:#878787}
.s-deliver .name-row{display:flex;align-items:center;gap:8px;margin-top:4px}
.s-deliver .name-row .nm{font-size:14px;font-weight:600}
.s-deliver .name-row .badge{font-size:10px;font-weight:600;background:#f0f0f0;color:#878787;padding:2px 6px;border-radius:3px}
.s-deliver .addr{font-size:13px;color:#212121;line-height:1.4;margin-top:4px}
.s-deliver .phone{font-size:13px;margin-top:4px}
.s-deliver .ch-btn{float:right;font-size:13px;font-weight:500;color:#2874f0;border:1px solid #2874f0;padding:5px 16px;border-radius:5px;background:#fff}
.s-item{border-bottom:1px solid #f0f0f0;padding:14px 0}
.s-item:last-child{border-bottom:none}
.s-item .deal{font-size:12px;color:#388e3c;font-weight:500;margin-bottom:8px}
.s-item .row{display:flex;gap:12px}
.s-item .img-col{width:80px;flex-shrink:0;display:flex;flex-direction:column;align-items:center;gap:8px}
.s-item .img-col img{width:80px;height:80px;object-fit:contain}
.s-item .img-col .qty{border:1px solid #c2c2c2;border-radius:4px;padding:3px 8px;font-size:12px}
.s-item .info-col{flex:1}
.s-item .info-col .title{font-size:14px;line-height:1.4}
.s-item .info-col .prices{display:flex;align-items:baseline;gap:8px;margin-top:6px;flex-wrap:wrap}
.s-item .info-col .prices .d{font-size:13px;font-weight:600;color:#388e3c}
.s-item .info-col .prices .o{font-size:12px;color:#878787;text-decoration:line-through}
.s-item .info-col .prices .n{font-size:15px;font-weight:700}
.donate-card .dt{font-size:14px;font-weight:500}
.donate-card .ds{font-size:12px;color:#878787}
.donate-card .btns{display:flex;gap:8px;margin-top:10px}
.donate-card .btns button{padding:7px 18px;border:1px solid #c2c2c2;border-radius:24px;background:#fff;font-size:13px}
.price-card .row{display:flex;justify-content:space-between;font-size:13px;margin-bottom:10px}
.price-card .total{display:flex;justify-content:space-between;font-size:15px;font-weight:700;border-top:1px solid #eee;padding-top:10px;margin-top:4px}
.price-card .save-bar{background:#e8f5e9;color:#1b5e20;font-size:13px;text-align:center;padding:10px;border-radius:6px;margin-top:12px}

/* ═══ PAYMENT VIEW ═══ */
.payment-view{display:none;padding:10px 12px;padding-bottom:100px}
.payment-view.show{display:block}
.pay-hdr{background:#fff;border-radius:8px;padding:14px 16px;margin-bottom:8px;display:flex;align-items:center;justify-content:space-between}
.pay-hdr .left{display:flex;align-items:center;gap:8px;font-size:11px;color:#878787}
.pay-hdr .left span{font-size:14px;font-weight:700;color:#212121}
.pay-hdr .right{font-size:18px;font-weight:800;color:#212121}
.pay-cashback{background:#fff;border-radius:8px;padding:12px 16px;margin-bottom:8px;display:flex;align-items:center;gap:10px}
.pay-cashback .tag{background:#388e3c;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:3px}
.pay-cashback .text{font-size:13px;color:#212121}
.pay-section{background:#fff;border-radius:8px;margin-bottom:8px;overflow:hidden}
.pay-row{display:flex;align-items:center;padding:16px;border-bottom:1px solid #f5f5f5;gap:12px;cursor:pointer}
.pay-row:last-child{border-bottom:none}
.pay-row .icon{width:28px;height:28px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#555}
.pay-row .info{flex:1}
.pay-row .info .main{font-size:14px;font-weight:500}
.pay-row .info .sub{font-size:11px;color:#878787;margin-top:1px}
.pay-row .info .offer{font-size:11px;color:#388e3c;font-weight:500;margin-top:2px}
.pay-row .arrow{color:#c2c2c2}
.pay-row .unavail{font-size:11px;color:#878787}
.pay-place-btn{width:100%;padding:15px;background:#fb641b;color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:700;margin-top:12px}

/* Sticky footer */
.co-footer{position:fixed;bottom:0;left:0;right:0;z-index:30;background:#fff;border-top:1px solid #eee;box-shadow:0 -2px 10px rgba(0,0,0,.08);padding:12px 16px;display:none;align-items:center;justify-content:space-between}
.co-footer.show{display:flex}
.co-footer .price-col .mrp{font-size:12px;color:#878787;text-decoration:line-through}
.co-footer .price-col .total{font-size:20px;font-weight:800}
.co-footer .price-col .link{font-size:11px;color:#2874f0}
.co-footer .ctn-btn{background:#fb641b;color:#fff;border:none;padding:14px 32px;border-radius:6px;font-size:15px;font-weight:700}
</style>
</head>
<body>

<!-- Header -->
<header class="co-hdr">
    <div class="co-hdr-top">
        <a href="<?= e($cartLink) ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
        <h1>Checkout</h1>
        <span class="step-info">🔒 100% Secure</span>
    </div>
    <div class="stepper" id="stepperEl">
        <div class="stp active" id="s1"><div class="stp-circle active">1</div><span class="stp-label">Address</span></div>
        <div class="stp-line" id="l1"></div>
        <div class="stp" id="s2"><div class="stp-circle">2</div><span class="stp-label">Order Summary</span></div>
        <div class="stp-line" id="l2"></div>
        <div class="stp" id="s3"><div class="stp-circle">3</div><span class="stp-label">Payment</span></div>
    </div>
</header>

<!-- ═══ MAP VIEW ═══ -->
<div id="vMap">
    <div class="map-hdr"><button onclick="history.back()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button><h2>Add new address</h2></div>
    <div class="map-wrap">
        <div class="map-searchbar">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#878787" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" id="searchIn" placeholder="Search by area, name, street." oninput="doSearch(this.value)">
            <div class="results" id="searchRes"></div>
        </div>
        <div id="mapEl"></div>
        <button class="map-locate" onclick="useLoc()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M22 12h-4M6 12H2M12 6V2M12 22v-4"/></svg> Use my current location</button>
    </div>
    <div class="map-bottom">
        <p class="deliver-label">Deliver To</p>
        <div class="area-card">
            <span class="pin">📍</span>
            <div class="text"><p id="areaName">Pick a location on the map</p><p class="sub" id="areaDetail"></p></div>
            <button class="change-btn" onclick="go('map')">Change</button>
        </div>
        <button class="continue-btn" id="mapCtnBtn" disabled onclick="go('form')">Add address Details</button>
    </div>
</div>

<!-- ═══ FORM VIEW ═══ -->
<div class="form-view" id="vForm">
    <form method="POST" action="/" id="coForm">
        <input type="hidden" name="action" value="place_order">
        <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
        <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
        <input type="hidden" name="lat" id="fLat"><input type="hidden" name="lng" id="fLng">
        <input type="hidden" name="area" id="fArea"><input type="hidden" name="city" id="fCity">
        <input type="hidden" name="state" id="fState"><input type="hidden" name="postal_code" id="fPostal">
        <input type="hidden" name="address_type" id="fType" value="Home">
        <input type="hidden" name="payment_method" id="fPay" value="upi">
        <input type="hidden" name="upi_app" id="fApp" value="PhonePe">

        <div class="form-title"><h2>Deliver To</h2><button type="button" onclick="go('map')" style="font-size:20px;color:#878787">✕</button></div>
        <div class="info-bar"><span>ℹ️</span><span>Ensure your address details are accurate for a smooth delivery experience</span></div>
        
        <div class="flat-field"><label>Flat/House/building name *</label><input type="text" id="fFlat" name="flat" required></div>
        
        <div class="area-ro">
            <div class="info"><p class="lbl">Area / Sector / Locality</p><p class="val" id="roArea"></p><p class="bold" id="roCityState"></p></div>
            <button type="button" class="ch-btn" onclick="go('map')">Change</button>
        </div>
        
        <div class="fi"><input type="text" name="name" id="fName" placeholder="Enter your full name *" required></div>
        <div class="fi"><input type="tel" name="phone" id="fPhone" placeholder="10-digit mobile number *" required pattern="[0-9]{10}"></div>
        <div class="fi"><input type="tel" name="alt_phone" placeholder="Alternate phone number (Optional)"></div>
        
        <div class="type-row">
            <p>Type of address</p>
            <div class="type-btns">
                <button type="button" class="type-btn sel" onclick="setType(this,'Home')">🏠 Home</button>
                <button type="button" class="type-btn" onclick="setType(this,'Work')">🏢 Work</button>
            </div>
        </div>
        
        <button type="button" class="save-btn" onclick="saveAddr()">Save address</button>
    </form>
</div>

<!-- ═══ SUMMARY VIEW ═══ -->
<div class="summary-view" id="vSummary">
    <div class="s-card s-deliver">
        <button type="button" class="ch-btn" onclick="go('form')">Change</button>
        <p class="lbl">Deliver to:</p>
        <div class="name-row"><span class="nm" id="sName"></span><span class="badge" id="sType">HOME</span></div>
        <p class="addr" id="sAddr"></p>
        <p class="phone" id="sPhone"></p>
    </div>
    <div class="s-card">
        <?php foreach ($items as $item): 
            $lm = (!empty($item['mrp']) && $item['mrp'] > $item['unit_price']) ? $item['mrp']*$item['quantity'] : $item['unit_price']*$item['quantity'];
            $lt = $item['unit_price']*$item['quantity'];
            $pct = $lm > $lt ? (int)round((($lm-$lt)/$lm)*100) : 0;
        ?>
        <div class="s-item">
            <p class="deal">Early Bird Deal</p>
            <div class="row">
                <div class="img-col">
                    <?php if(!empty($item['image_url'])): ?><img src="<?= e(img_url($item['image_url'],['w'=>200,'h'=>200])) ?>" alt=""><?php endif; ?>
                    <span class="qty">Qty: <?= $item['quantity'] ?></span>
                </div>
                <div class="info-col">
                    <p class="title"><?= e($item['title']) ?></p>
                    <div class="prices">
                        <?php if($pct>0): ?><span class="d">↓<?= $pct ?>%</span><?php endif; ?>
                        <?php if($lm>$lt): ?><span class="o">₹<?= number_format($lm,0,'.',',') ?></span><?php endif; ?>
                        <span class="n">₹<?= number_format($lt,0,'.',',') ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="s-card donate-card">
        <p class="dt">Donate to <?= e($siteName) ?> Foundation</p>
        <p class="ds">Support transformative social work</p>
        <div class="btns"><button type="button">₹10</button><button type="button">₹20</button><button type="button">₹50</button><button type="button">₹100</button></div>
    </div>
    <div class="s-card price-card">
        <div class="row"><span>MRP</span><span>₹<?= number_format($mrpTotal,0,'.',',') ?></span></div>
        <?php if($discount>0): ?><div class="row"><span>Discounts ▾</span><span style="color:#388e3c">₹<?= number_format($discount,0,'.',',') ?></span></div><?php endif; ?>
        <div class="row"><span>Delivery</span><span><?= $shipping===0?'<span style="color:#388e3c;font-weight:500">FREE</span>':'₹'.$shipping ?></span></div>
        <div class="total"><span>Total Amount</span><span>₹<?= number_format($total,0,'.',',') ?></span></div>
        <?php if($savings>0): ?><div class="save-bar">You will save ₹<?= number_format($savings,0,'.',',') ?> on this order</div><?php endif; ?>
    </div>
</div>

<!-- ═══ PAYMENT VIEW ═══ -->
<div class="payment-view" id="vPayment">
    <div class="pay-hdr">
        <div class="left"><span>Step 3 of 3</span><br>Payments</div>
        <div class="right">₹<?= number_format($total,0,'.',',') ?></div>
    </div>
    <div class="pay-cashback"><span class="tag">5% Cashback</span><span class="text">Claim now with payment offers</span></div>
    
    <div class="pay-section">
        <div class="pay-row" onclick="document.getElementById('fPay').value='upi'">
            <div class="icon">💳</div>
            <div class="info"><p class="main">Recommended for You</p></div>
            <span class="arrow">›</span>
        </div>
    </div>
    <div class="pay-section">
        <div class="pay-row">
            <div class="icon">💳</div>
            <div class="info"><p class="main">Credit / Debit / ATM Card</p><p class="sub">Add and secure cards as per RBI guidelines</p><p class="offer">Get upto 5% cashback • 2 offers available</p></div>
            <span class="arrow">›</span>
        </div>
    </div>
    <div class="pay-section">
        <div class="pay-row" onclick="document.getElementById('fPay').value='cod'">
            <div class="icon">📦</div>
            <div class="info"><p class="main">Cash on Delivery</p></div>
            <span class="arrow">›</span>
        </div>
    </div>
    <div class="pay-section">
        <div class="pay-row">
            <div class="icon">🎁</div>
            <div class="info"><p class="main">Have a Flipkart Gift Card?</p></div>
            <span style="font-size:13px;color:#2874f0;font-weight:500">Add</span>
        </div>
    </div>
    <div class="pay-section">
        <div class="pay-row"><div class="icon">📱</div><div class="info"><p class="main">UPI</p></div><span class="unavail">Unavailable ⓘ</span></div>
        <div class="pay-row"><div class="icon">📅</div><div class="info"><p class="main">EMI</p></div><span class="unavail">Unavailable ⓘ</span></div>
    </div>
    
    <p style="text-align:center;font-size:13px;color:#388e3c;margin-top:20px;font-weight:500">35 Crore happy customers and counting! 😊</p>
    
    <button type="button" class="pay-place-btn" onclick="document.getElementById('coForm').submit()">Place Order · ₹<?= number_format($total,0,'.',',') ?></button>
</div>

<!-- Sticky footer (for summary step) -->
<footer class="co-footer" id="coFooter">
    <div class="price-col">
        <?php if($mrpTotal>$total): ?><p class="mrp">₹<?= number_format($mrpTotal,0,'.',',') ?></p><?php endif; ?>
        <p class="total">₹<?= number_format($total,0,'.',',') ?></p>
        <p class="link">View price details</p>
    </div>
    <button type="button" class="ctn-btn" onclick="go('payment')">Continue</button>
</footer>

<!-- Location sheet -->
<div class="loc-sheet" id="locSheet">
    <div class="loc-sheet-inner">
        <p class="title">Where do you want us to deliver the order?</p>
        <p class="sub">This will help with the right map location</p>
        <button class="btn-away" onclick="closeSheet()">Away from my location</button>
        <button class="btn-use" onclick="useLoc();closeSheet()"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M22 12h-4M6 12H2M12 6V2M12 22v-4"/></svg> Use current location</button>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var map,marker,area={area:'',city:'',state:'',postal:''};

function initMap(){
    map=L.map('mapEl',{zoomControl:false}).setView([12.9716,77.5946],14);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',{maxZoom:19}).addTo(map);
    var icon=L.divIcon({className:'',html:'<div style="display:flex;flex-direction:column;align-items:center"><div style="background:#212121;color:#fff;font-size:11px;font-weight:600;padding:4px 10px;border-radius:6px;white-space:nowrap;margin-bottom:4px">Place pin on the exact location</div><svg width="30" height="38" viewBox="0 0 24 30" fill="#212121"><path d="M12 0C5.4 0 0 5.4 0 12c0 8 12 18 12 18s12-10 12-18C24 5.4 18.6 0 12 0z"/><circle cx="12" cy="12" r="4" fill="#fff"/></svg></div>',iconSize:[30,70],iconAnchor:[15,65]});
    marker=L.marker([12.9716,77.5946],{icon:icon,draggable:true}).addTo(map);
    map.on('click',function(e){marker.setLatLng(e.latlng);pin(e.latlng.lat,e.latlng.lng)});
    marker.on('dragend',function(){var p=marker.getLatLng();pin(p.lat,p.lng)});
    document.getElementById('locSheet').classList.add('show');
}

function pin(lat,lng){
    document.getElementById('fLat').value=lat;document.getElementById('fLng').value=lng;
    document.getElementById('mapCtnBtn').disabled=false;
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&zoom=18&addressdetails=1')
    .then(r=>r.json()).then(d=>{
        var a=d.address||{};
        area.area=[a.suburb,a.neighbourhood,a.road].filter(Boolean).slice(0,2).join(', ')||d.display_name.split(',')[0]||'';
        area.city=a.city||a.town||a.village||a.county||'';
        area.state=a.state||'';
        area.postal=a.postcode||'';
        document.getElementById('areaName').textContent=area.area||'Selected';
        document.getElementById('areaDetail').textContent=[area.city,area.state,area.postal].filter(Boolean).join(', ');
    }).catch(()=>{});
}

function useLoc(){
    if(!navigator.geolocation)return alert('Location not available');
    navigator.geolocation.getCurrentPosition(p=>{
        map.setView([p.coords.latitude,p.coords.longitude],16);
        marker.setLatLng([p.coords.latitude,p.coords.longitude]);
        pin(p.coords.latitude,p.coords.longitude);
    },()=>alert("Couldn't get location"));
}

function closeSheet(){document.getElementById('locSheet').classList.remove('show')}

function doSearch(q){
    var r=document.getElementById('searchRes');
    if(q.length<3){r.classList.remove('show');return}
    fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(q)+'&limit=5&countrycodes=in')
    .then(x=>x.json()).then(d=>{
        if(!d.length){r.classList.remove('show');return}
        r.innerHTML='';r.classList.add('show');
        d.forEach(i=>{var b=document.createElement('button');b.innerHTML='<span>'+i.display_name.split(',')[0]+'</span><span class="sub">'+i.display_name+'</span>';
        b.onclick=()=>{map.setView([+i.lat,+i.lon],16);marker.setLatLng([+i.lat,+i.lon]);pin(+i.lat,+i.lon);document.getElementById('searchIn').value=i.display_name.split(',')[0];r.classList.remove('show')};r.appendChild(b)});
    });
}

function go(v){
    ['vMap','vForm','vSummary','vPayment'].forEach(id=>{var el=document.getElementById(id);if(el){el.style.display='none';el.classList.remove('show')}});
    document.getElementById('coFooter').classList.remove('show');

    if(v==='map'){document.getElementById('vMap').style.display='';step(1);setTimeout(()=>map.invalidateSize(),100)}
    if(v==='form'){var f=document.getElementById('vForm');f.style.display='';f.classList.add('show');step(1);
        document.getElementById('fArea').value=area.area||'';document.getElementById('fCity').value=area.city||'';
        document.getElementById('fState').value=area.state||'';document.getElementById('fPostal').value=area.postal||'';
        document.getElementById('roArea').textContent=area.area||'Selected location';
        document.getElementById('roCityState').textContent=[area.city,area.state,area.postal].filter(Boolean).join(', ')||'Map location selected';
    }
    if(v==='summary'){var s=document.getElementById('vSummary');s.style.display='';s.classList.add('show');document.getElementById('coFooter').classList.add('show');step(2)}
    if(v==='payment'){var p=document.getElementById('vPayment');p.style.display='';p.classList.add('show');step(3)}
    window.scrollTo(0,0);
}

function step(n){
    var c=document.querySelectorAll('.stp-circle'),l=document.querySelectorAll('.stp-line'),s=document.querySelectorAll('.stp');
    c.forEach((el,i)=>{el.classList.toggle('active',i<n);if(i<n-1)el.innerHTML='✓'});
    l.forEach((el,i)=>el.classList.toggle('done',i<n-1));
    s.forEach((el,i)=>el.classList.toggle('active',i===n-1));
}

function setType(btn,t){document.querySelectorAll('.type-btn').forEach(b=>b.classList.remove('sel'));btn.classList.add('sel');document.getElementById('fType').value=t}

function saveAddr(){
    var n=document.getElementById('fName').value,p=document.getElementById('fPhone').value,f=document.getElementById('fFlat').value;
    if(!n||!p||!f){alert('Fill name, phone, and flat/house');return}
    document.getElementById('sName').textContent=n;
    document.getElementById('sType').textContent=document.getElementById('fType').value.toUpperCase();
    document.getElementById('sAddr').textContent=[f,area.area,area.city,area.state,area.postal].filter(Boolean).join(', ');
    document.getElementById('sPhone').textContent=p;
    go('summary');
}

document.addEventListener('DOMContentLoaded',()=>{go('map');initMap()});
</script>
</body>
</html>
