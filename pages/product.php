<?php
/**
 * Product Detail Page - Exact Flipkart Mobile UI
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantId = $tenant['id'] ?? null;
$product = get_product_by_slug($slug, $tenantId);

if (!$product) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    return;
}

$pageTitle = e($product['title']) . ' — ' . ($tenant['name'] ?? DEFAULT_SITE_NAME);

$images = $product['product_images'] ?? [];
usort($images, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));
$mainImg = $images[0]['url'] ?? null;

$variants = $product['product_variants'] ?? [];
usort($variants, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));

$price = (float) $product['price'];
$mrp = !empty($product['mrp']) ? (float) $product['mrp'] : null;
$discount = calc_discount($price, $mrp, (int) ($product['discount_percent'] ?? 0));
$upiPrice = max(0, round($price - 50));
$rating = !empty($product['rating']) ? (float) $product['rating'] : 0;
$ratingCount = (int) ($product['rating_count'] ?? 0);
$stock = (int) ($product['stock'] ?? 0);
$categoryName = $product['categories']['name'] ?? 'General';
$categorySlug = $product['categories']['slug'] ?? '';
$isFashion = preg_match('/fashion|cloth|apparel|wear|shirt|tshirt|jeans|kurta|saree|dress|footwear|shoe|men|women|kids/i', "{$categorySlug} {$categoryName}");

$related = [];
if (!empty($product['category_id'])) {
    $related = get_related_products($product['category_id'], $product['id'], $tenantId);
}

$cartLink = $tenant ? "/t/{$tenant['slug']}/cart" : '/cart';
$checkoutLink = $tenant ? "/t/{$tenant['slug']}/checkout" : '/checkout';
$searchLink = $tenant ? "/t/{$tenant['slug']}/search" : '/search';
$deliveryDate = date('D, j M', strtotime('+3 days'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title><?= $pageTitle ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f1f3f6;color:#212121;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
button{font:inherit;cursor:pointer;border:none;background:none}

/* Header */
.pd-header{position:sticky;top:0;z-index:40;background:#dbeeff;padding:10px 12px;display:flex;align-items:center;gap:10px}
.pd-back{display:flex;align-items:center}
.pd-search{flex:1;display:flex;align-items:center;gap:8px;background:#fff;border-radius:8px;border:1px solid #cfe1f7;padding:9px 14px}
.pd-search svg{flex-shrink:0;color:#2874f0}
.pd-search span{font-size:14px;color:#9aa0a6}
.pd-cart{position:relative;display:flex;align-items:center}
.pd-cart .badge{position:absolute;top:-6px;right:-8px;min-width:18px;height:18px;background:#ff5252;color:#fff;font-size:10px;font-weight:700;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px}

/* AD strip */
.ad-strip{background:#fff;padding:10px 12px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #f0f0f0}
.ad-strip img{width:36px;height:36px;object-fit:contain;border-radius:4px;border:1px solid #eee}
.ad-strip .info{flex:1;min-width:0}
.ad-strip .info p{font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ad-strip .info .price-row{font-size:11px;margin-top:1px}
.ad-strip .info .price-row .disc{color:#388e3c;font-weight:600}
.ad-strip .info .price-row .old{color:#878787;text-decoration:line-through;margin:0 4px}
.ad-strip .info .price-row .now{font-weight:600}
.ad-strip .ad-tag{font-size:10px;color:#878787;background:#f5f5f5;padding:2px 6px;border-radius:3px}

/* Image gallery */
.pd-gallery{position:relative;background:#fff}
.pd-gallery-img{width:100%;aspect-ratio:1;object-fit:contain;display:block}
.pd-gallery-placeholder{width:100%;aspect-ratio:1;display:grid;place-items:center;font-size:48px;color:#ccc;background:#fafafa}
.pd-gallery .actions{position:absolute;right:12px;top:12px;display:flex;flex-direction:column;gap:10px}
.pd-gallery .actions button{width:38px;height:38px;border-radius:50%;background:#fff;box-shadow:0 1px 6px rgba(0,0,0,.12);display:flex;align-items:center;justify-content:center}
.pd-gallery .dots{display:flex;justify-content:center;gap:6px;padding:12px 0 16px}
.pd-gallery .dot{width:6px;height:6px;border-radius:50%;background:#cfd8dc}
.pd-gallery .dot.active{background:#2874f0}

/* Rating chip */
.rating-chip{display:inline-flex;align-items:center;gap:6px;margin:0 12px 12px;padding:4px 10px;border:1px solid #e0e0e0;border-radius:4px;font-size:13px;font-weight:600}
.rating-chip .star{color:#388e3c}
.rating-chip .sep{color:#e0e0e0}
.rating-chip .count{color:#878787;font-weight:400}

/* Color selector */
.color-section{background:#fff;padding:14px 16px;border-top:6px solid #f1f3f6}
.color-section .label{font-size:14px}
.color-section .label b{font-weight:700}
.color-variants{display:flex;gap:10px;margin-top:10px;flex-wrap:wrap}
.color-variants .cv{width:56px;height:56px;border-radius:6px;border:2px solid #e0e0e0;overflow:hidden;background:#fff}
.color-variants .cv.active{border-color:#2874f0}
.color-variants .cv img{width:100%;height:100%;object-fit:contain}

/* Pack selector */
.pack-section{background:#fff;padding:14px 16px;border-top:1px solid #f0f0f0}
.pack-section .label{font-size:13px;font-weight:700}
.pack-btns{display:flex;gap:8px;margin-top:10px}
.pack-btn{width:44px;height:44px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;border:1px solid #e0e0e0;background:#fff;color:#212121}
.pack-btn.active{background:#212121;color:#fff;border-color:#212121}

/* Size selector */
.size-section{background:#fff;padding:14px 16px;border-top:1px solid #f0f0f0}
.size-section .label{font-size:14px}
.size-section .chart-link{font-size:12px;color:#2874f0;font-weight:500}
.size-btns{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.size-btn{padding:0 16px;height:40px;border-radius:6px;font-size:13px;font-weight:600;border:1px solid #e0e0e0;background:#fff}
.size-btn.active{background:#212121;color:#fff;border-color:#212121}

/* Title + Price */
.title-section{background:#fff;padding:14px 16px;border-top:6px solid #f1f3f6}
.title-section .brand{font-size:12px;color:#878787;margin-bottom:2px}
.title-section .name{font-size:14px;line-height:1.4;color:#212121}
.title-section .price-block{display:flex;align-items:baseline;gap:8px;margin-top:10px;flex-wrap:wrap}
.title-section .price-block .disc{font-size:15px;font-weight:700;color:#388e3c}
.title-section .price-block .old{font-size:14px;color:#878787;text-decoration:line-through}
.title-section .price-block .now{font-size:22px;font-weight:800;color:#212121}
.title-section .upi-line{font-size:13px;color:#2874f0;font-weight:500;margin-top:6px}
.title-section .upi-line .muted{color:#878787;font-weight:400}

/* WOW deal */
.wow-section{padding:12px 12px 0}
.wow-card{border-radius:12px;overflow:hidden;background:linear-gradient(135deg,#1a4fa3 0%,#2874f0 100%);color:#fff;padding:14px 16px;display:flex;align-items:center;justify-content:space-between}
.wow-card .left{display:flex;align-items:center;gap:10px}
.wow-card .badge{background:#fff;color:#2874f0;font-size:10px;font-weight:800;font-style:italic;padding:4px 8px;border-radius:4px;line-height:1.2}
.wow-card .price-text{font-size:16px;font-weight:700}
.wow-cta{background:#cfe1f7;color:#1a4fa3;font-size:12px;text-align:center;padding:8px;border-radius:0 0 12px 12px}

/* Delivery */
.delivery-section{background:#fff;padding:16px;border-top:6px solid #f1f3f6}
.delivery-section h3{font-size:15px;font-weight:700;margin-bottom:12px}
.delivery-box{background:#f5f5f5;border-radius:8px;padding:12px;display:flex;align-items:flex-start;gap:10px;margin-bottom:10px}
.delivery-box .date{font-size:13px;font-weight:700}
.delivery-box .countdown{font-size:11px;color:#fb641b;font-weight:600;margin-top:2px}
.delivery-features{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px;padding-top:12px;border-top:1px solid #eee}
.delivery-features .feat{text-align:center}
.delivery-features .feat svg{margin:0 auto 6px}
.delivery-features .feat p{font-size:11px;color:#212121;line-height:1.3}

/* Highlights */
.highlights-section{background:#fff;padding:16px;border-top:6px solid #f1f3f6}
.highlights-section h3{font-size:15px;font-weight:700;margin-bottom:12px}
.highlights-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 16px;font-size:12px}
.highlights-grid dt{color:#878787}
.highlights-grid dd{color:#212121;font-weight:500;margin-top:2px}

/* Description */
.desc-section{background:#fff;padding:16px;border-top:6px solid #f1f3f6}
.desc-section h3{font-size:15px;font-weight:700;margin-bottom:10px}
.desc-section p{font-size:13px;color:#444;line-height:1.6;white-space:pre-line}

/* Reviews */
.reviews-section{background:#fff;padding:16px;border-top:6px solid #f1f3f6}
.reviews-section h3{font-size:15px;font-weight:700;margin-bottom:12px}
.rev-overview{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.rev-score{font-size:22px;font-weight:700}
.rev-star{color:#388e3c;font-size:16px}
.rev-badge{background:#e8f5e9;color:#388e3c;font-size:11px;font-weight:700;padding:3px 8px;border-radius:4px}
.rev-count{font-size:11px;color:#878787;margin-bottom:14px}
.rev-bars{margin-bottom:16px}
.rev-bar{display:flex;align-items:center;gap:8px;margin-bottom:5px}
.rev-bar .label{font-size:12px;min-width:28px;text-align:right}
.rev-bar .track{flex:1;height:6px;background:#f0f0f0;border-radius:3px;overflow:hidden}
.rev-bar .fill{height:100%;border-radius:3px}
.rev-bar .fill.g{background:#388e3c}
.rev-bar .fill.y{background:#ff9800}
.rev-bar .fill.r{background:#d32f2f}
.rev-bar .num{font-size:11px;color:#878787;min-width:36px}
.rev-item{padding:12px 0;border-top:1px solid #f5f5f5}
.rev-item .head{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.rev-item .rbadge{display:inline-flex;align-items:center;gap:2px;padding:2px 6px;border-radius:3px;font-size:11px;font-weight:600;color:#fff}
.rev-item .rbadge.g{background:#388e3c}
.rev-item .rbadge.y{background:#ff9800}
.rev-item .rtitle{font-size:13px;font-weight:600}
.rev-item .rtext{font-size:13px;color:#444;line-height:1.5;margin-bottom:8px}
.rev-item .rfooter{display:flex;align-items:center;justify-content:space-between;font-size:11px;color:#878787}
.rev-item .rfooter .btns{display:flex;gap:8px}
.rev-item .rfooter .btns button{border:1px solid #e0e0e0;border-radius:4px;padding:3px 10px;font-size:12px;color:#555;background:#fff}

/* Related */
.related-section{background:#fff;padding:16px;border-top:6px solid #f1f3f6}
.related-section h3{font-size:15px;font-weight:700;margin-bottom:12px}
.related-scroll{display:flex;gap:10px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none;-ms-overflow-style:none}
.related-scroll::-webkit-scrollbar{display:none}
.related-card{min-width:140px;max-width:140px;flex-shrink:0}
.related-card img{width:100%;aspect-ratio:1;object-fit:contain;border:1px solid #f0f0f0;border-radius:6px}
.related-card .rc-title{font-size:11px;margin-top:6px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.related-card .rc-price{font-size:12px;font-weight:600;margin-top:3px}

/* Sticky footer */
.pd-footer{position:fixed;bottom:0;left:0;right:0;z-index:30;background:#fff;border-top:1px solid #eee;box-shadow:0 -2px 10px rgba(0,0,0,.08);display:flex;padding:10px 12px;gap:10px}
.pd-footer form{flex:1;display:flex}
.pd-footer .btn-cart{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:#fff;border:1.5px solid #212121;border-radius:6px;padding:12px;font-size:14px;font-weight:600;color:#212121}
.pd-footer .btn-buy{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:#ffe500;border:none;border-radius:6px;padding:12px;font-size:14px;font-weight:700;color:#212121}
.pd-footer .btn-buy:hover{background:#ffd600}

/* Spacer for fixed footer */
.footer-spacer{height:80px}
</style>
</head>
<body>

<!-- Header -->
<header class="pd-header">
    <a href="javascript:history.back()" class="pd-back"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
    <a href="<?= e($searchLink) ?>" class="pd-search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <span>Search for products</span>
    </a>
    <a href="<?= e($cartLink) ?>" class="pd-cart">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
        <?php $cc = array_sum(array_column(cart_get(), 'quantity')); if ($cc > 0): ?><span class="badge"><?= $cc ?></span><?php endif; ?>
    </a>
</header>

<!-- AD strip -->
<div class="ad-strip">
    <?php if ($mainImg): ?><img src="<?= e(img_url($mainImg, ['w' => 72, 'h' => 72])) ?>" alt=""><?php endif; ?>
    <div class="info">
        <p><?= e($product['title']) ?></p>
        <div class="price-row">
            <?php if ($discount > 0): ?><span class="disc">↓<?= $discount ?>%</span><?php endif; ?>
            <?php if ($mrp && $mrp > $price): ?><span class="old">₹<?= number_format($mrp, 0, '.', ',') ?></span><?php endif; ?>
            <span class="now">₹<?= number_format($price, 0, '.', ',') ?></span>
        </div>
    </div>
    <span class="ad-tag">AD</span>
</div>

<!-- Image Gallery -->
<section class="pd-gallery">
    <?php if ($mainImg): ?>
    <img src="<?= e(img_url($mainImg, ['w' => 900, 'h' => 900, 'quality' => 85])) ?>" alt="<?= e($product['title']) ?>" class="pd-gallery-img" id="mainImg">
    <?php else: ?>
    <div class="pd-gallery-placeholder">📦</div>
    <?php endif; ?>
    <div class="actions">
        <button type="button"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></button>
        <button type="button"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg></button>
    </div>
    <?php if (count($images) > 1): ?>
    <div class="dots">
        <?php foreach (array_slice($images, 0, 8) as $i => $img): ?>
        <span class="dot <?= $i === 0 ? 'active' : '' ?>" onclick="document.getElementById('mainImg').src='<?= e(img_url($img['url'], ['w' => 900, 'h' => 900, 'quality' => 85])) ?>';document.querySelectorAll('.dot').forEach(d=>d.classList.remove('active'));this.classList.add('active')"></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- Rating chip -->
<?php if ($rating > 0): ?>
<div style="background:#fff;padding:4px 0 8px"><div class="rating-chip"><span><?= number_format($rating, 1) ?></span><span class="star">★</span><span class="sep">|</span><span class="count"><?= number_format($ratingCount) ?></span></div></div>
<?php endif; ?>

<!-- Color Variants -->
<?php if (!empty($variants)): ?>
<section class="color-section">
    <p class="label"><b>Selected Color:</b> <?= e($variants[0]['color_name'] ?? '—') ?></p>
    <div class="color-variants">
        <?php foreach ($variants as $i => $v): $th = !empty($v['variant_images']) ? $v['variant_images'][0]['url'] : null; ?>
        <div class="cv <?= $i === 0 ? 'active' : '' ?>">
            <?php if ($th): ?><img src="<?= e(img_url($th, ['w' => 80, 'h' => 80])) ?>" alt="">
            <?php else: ?><span style="display:block;width:100%;height:100%;background:<?= e($v['color_code'] ?? '#ccc') ?>"></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Pack selector -->
<section class="pack-section">
    <p class="label">Selected Pack of: <b>1</b></p>
    <div class="pack-btns">
        <button class="pack-btn active">1</button>
        <button class="pack-btn">2</button>
        <button class="pack-btn">3</button>
    </div>
</section>

<!-- Size (fashion only) -->
<?php if ($isFashion): ?>
<section class="size-section">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <p class="label"><b>Selected Size:</b> Free Size</p>
        <span class="chart-link">Size Chart</span>
    </div>
    <div class="size-btns">
        <button class="size-btn active">Free Size</button>
        <button class="size-btn">S</button>
        <button class="size-btn">M</button>
        <button class="size-btn">L</button>
    </div>
</section>
<?php endif; ?>

<!-- Title + Price -->
<section class="title-section">
    <?php if (!empty($product['brand'])): ?><p class="brand"><?= e($product['brand']) ?></p><?php endif; ?>
    <p class="name"><?= e($product['title']) ?></p>
    <div class="price-block">
        <?php if ($discount > 0): ?><span class="disc">↓<?= $discount ?>%</span><?php endif; ?>
        <?php if ($mrp && $mrp > $price): ?><span class="old">₹<?= number_format($mrp, 0, '.', ',') ?></span><?php endif; ?>
        <span class="now">₹<?= number_format($price, 0, '.', ',') ?></span>
    </div>
    <p class="upi-line">₹<?= number_format($upiPrice, 0, '.', ',') ?> <span class="muted">with UPI offer</span> + more</p>
</section>

<!-- WOW Deal -->
<section class="wow-section">
    <div class="wow-card">
        <div class="left"><span class="badge">WOW!<br>DEAL</span><span class="price-text">Buy at ₹<?= number_format($upiPrice, 0, '.', ',') ?></span></div>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
    </div>
    <div class="wow-cta">Apply offers for maximum savings!</div>
</section>

<!-- Delivery -->
<section class="delivery-section">
    <h3>Delivery details</h3>
    <div class="delivery-box">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="m16 8 4 2v4l-4 2"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        <div><p class="date">Delivery by <?= $deliveryDate ?></p><p class="countdown">Order within the hour</p></div>
    </div>
    <div class="delivery-features">
        <div class="feat"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg><p>No returns</p></div>
        <div class="feat"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg><p>Cash on Delivery</p></div>
        <div class="feat"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg><p>Assured</p></div>
    </div>
</section>

<!-- Highlights -->
<section class="highlights-section">
    <h3>Product highlights</h3>
    <dl class="highlights-grid">
        <div><dt>Brand</dt><dd><?= e($product['brand'] ?? '—') ?></dd></div>
        <div><dt>Model</dt><dd><?= e(mb_substr($product['title'], 0, 30)) ?></dd></div>
        <div><dt>Stock</dt><dd><?= $stock > 0 ? "{$stock} units" : 'Available' ?></dd></div>
        <div><dt>Category</dt><dd><?= e($categoryName) ?></dd></div>
    </dl>
</section>

<!-- Description -->
<?php if (!empty($product['description'])): ?>
<section class="desc-section">
    <h3>All details</h3>
    <p><?= nl2br(e($product['description'])) ?></p>
</section>
<?php endif; ?>

<!-- Ratings & Reviews -->
<?php if ($rating > 0): 
    $bars = [['s'=>5,'p'=>65],['s'=>4,'p'=>20],['s'=>3,'p'=>8],['s'=>2,'p'=>4],['s'=>1,'p'=>3]];
    $reviews = [
        ['n'=>'Rahul M.','r'=>5,'t'=>'Excellent product! Great quality and fast delivery.','h'=>142],
        ['n'=>'Priya S.','r'=>4,'t'=>'Good value for money. Works well. Minor delay in delivery but product is solid.','h'=>89],
        ['n'=>'Amit K.','r'=>5,'t'=>'Amazing deal! Build quality is solid. Would buy again.','h'=>67],
    ];
?>
<section class="reviews-section">
    <h3>Ratings & Reviews</h3>
    <div class="rev-overview"><span class="rev-score"><?= number_format($rating, 1) ?></span><span class="rev-star">★</span><span class="rev-badge">Very Good</span></div>
    <p class="rev-count"><?= number_format($ratingCount) ?> Ratings & <?= number_format((int)($ratingCount*0.3)) ?> Reviews</p>
    <div class="rev-bars">
        <?php foreach ($bars as $b): ?>
        <div class="rev-bar"><span class="label"><?= $b['s'] ?> ★</span><div class="track"><div class="fill <?= $b['s']>=4?'g':($b['s']==3?'y':'r') ?>" style="width:<?= $b['p'] ?>%"></div></div><span class="num"><?= number_format((int)($ratingCount*$b['p']/100)) ?></span></div>
        <?php endforeach; ?>
    </div>
    <?php foreach ($reviews as $rev): ?>
    <div class="rev-item">
        <div class="head"><span class="rbadge g"><?= $rev['r'] ?> ★</span><span class="rtitle"><?= $rev['r']>=4?'Great product':'Average' ?></span></div>
        <p class="rtext"><?= e($rev['t']) ?></p>
        <div class="rfooter"><span><?= e($rev['n']) ?></span><div class="btns"><button>👍 <?= $rev['h'] ?></button><button>👎</button></div></div>
    </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<!-- Related -->
<?php if (!empty($related)): ?>
<section class="related-section">
    <h3>Similar Products</h3>
    <div class="related-scroll">
        <?php foreach (array_slice($related, 0, 8) as $rp): 
            $ri = ($rp['product_images'] ?? []); usort($ri, fn($a,$b)=>($a['sort_order']??0)-($b['sort_order']??0)); $rimg=$ri[0]['url']??null;
            $rlink = $tenant ? "/t/{$tenant['slug']}/product/{$rp['slug']}" : "/product/{$rp['slug']}";
        ?>
        <a href="<?= e($rlink) ?>" class="related-card">
            <?php if ($rimg): ?><img src="<?= e(img_url($rimg,['w'=>200,'h'=>200])) ?>" alt=""><?php endif; ?>
            <p class="rc-title"><?= e($rp['title']) ?></p>
            <p class="rc-price">₹<?= number_format($rp['price'],0,'.',',') ?></p>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<div class="footer-spacer"></div>

<!-- Sticky Footer: Add to Cart + Buy -->
<footer class="pd-footer">
    <form method="POST" action="/">
        <input type="hidden" name="action" value="add_to_cart">
        <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
        <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">
        <input type="hidden" name="title" value="<?= e($product['title']) ?>">
        <input type="hidden" name="image_url" value="<?= e($mainImg ?? '') ?>">
        <input type="hidden" name="unit_price" value="<?= $price ?>">
        <input type="hidden" name="mrp" value="<?= $mrp ?? '' ?>">
        <input type="hidden" name="max_quantity" value="<?= min(10, $stock ?: 10) ?>">
        <input type="hidden" name="rating" value="<?= $rating ?>">
        <input type="hidden" name="rating_count" value="<?= $ratingCount ?>">
        <input type="hidden" name="brand" value="<?= e($product['brand'] ?? '') ?>">
        <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
        <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
        <input type="hidden" name="redirect" value="<?= e($cartLink) ?>">
        <input type="hidden" name="qty" value="1">
        <button type="submit" class="btn-cart">Add to cart</button>
    </form>
    <form method="POST" action="/">
        <input type="hidden" name="action" value="add_to_cart">
        <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
        <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">
        <input type="hidden" name="title" value="<?= e($product['title']) ?>">
        <input type="hidden" name="image_url" value="<?= e($mainImg ?? '') ?>">
        <input type="hidden" name="unit_price" value="<?= $price ?>">
        <input type="hidden" name="mrp" value="<?= $mrp ?? '' ?>">
        <input type="hidden" name="max_quantity" value="<?= min(10, $stock ?: 10) ?>">
        <input type="hidden" name="tenant_id" value="<?= e($tenantId ?? '') ?>">
        <input type="hidden" name="tenant_slug" value="<?= e($tenant['slug'] ?? '') ?>">
        <input type="hidden" name="redirect" value="<?= e($checkoutLink) ?>">
        <input type="hidden" name="qty" value="1">
        <button type="submit" class="btn-buy">Buy at ₹<?= number_format($price, 0, '.', ',') ?></button>
    </form>
</footer>

</body>
</html>
