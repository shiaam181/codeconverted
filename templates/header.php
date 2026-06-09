<?php
/**
 * Header Template
 * Flipkart-style mobile header with tabs, location bar, and search
 */

$theme = get_theme();
$siteName = $theme['site_name'] ?? DEFAULT_SITE_NAME;
$tenant = $_SESSION['current_tenant'] ?? null;
$cartItems = cart_get();
$cartCount = array_sum(array_column($cartItems, 'quantity'));
$cartLink = $tenant ? "/t/{$tenant['slug']}/cart" : '/cart';
$searchLink = $tenant ? "/t/{$tenant['slug']}/search" : '/search';

// Load header tab icons
$headerIcons = get_icon_settings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title><?= e($pageTitle ?? $siteName . ' — Online Shopping') ?></title>
    <meta name="description" content="Shop online for mobiles, fashion, electronics, appliances, home & more. Best prices, fast delivery, easy returns.">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($theme['favicon_url'])): ?>
    <link rel="icon" href="<?= e($theme['favicon_url']) ?>">
    <?php endif; ?>
</head>
<body>
<header class="sticky-header" style="padding-top: env(safe-area-inset-top);">
    <!-- Top tabs row -->
    <div class="header-tabs">
        <div class="header-tabs-inner">
            <button class="tab-btn active" data-tab="flipkart">
                <?php if (!empty($headerIcons['tab_flipkart'])): ?>
                <img src="<?= e($headerIcons['tab_flipkart']) ?>" class="tab-icon-img" alt="Flipkart">
                <?php endif; ?>
                <span class="tab-label">Flipkart</span>
            </button>
            <button class="tab-btn" data-tab="minutes">
                <?php if (!empty($headerIcons['tab_minutes'])): ?>
                <img src="<?= e($headerIcons['tab_minutes']) ?>" class="tab-icon-img" alt="Minutes">
                <?php endif; ?>
                <span class="tab-label">Minutes</span>
            </button>
            <button class="tab-btn" data-tab="travel">
                <?php if (!empty($headerIcons['tab_travel'])): ?>
                <img src="<?= e($headerIcons['tab_travel']) ?>" class="tab-icon-img" alt="Travel">
                <?php endif; ?>
                <span class="tab-label">Travel</span>
            </button>
            <a href="<?= e($cartLink) ?>" class="cart-icon" aria-label="Cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Location bar -->
        <div class="location-bar">
            <div class="location-left">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span class="location-text">Location not set</span>
                <span class="location-link">Select delivery location ›</span>
            </div>
            <div class="supercoin">
                <span class="coin-icon">₹</span>
                0
            </div>
        </div>
    </div>

    <!-- Search bar -->
    <div class="search-bar-wrapper">
        <a href="<?= e($searchLink) ?>" class="search-bar">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2874f0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <span class="search-placeholder">Search for products, brands and more</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#878787" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
        </a>
    </div>
</header>
