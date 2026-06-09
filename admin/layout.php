<?php
/**
 * Admin Layout Template (sidebar + header)
 */

$adminNav = [
    ['url' => '/admin', 'label' => 'Dashboard', 'icon' => 'dashboard', 'exact' => true],
    ['url' => '/admin/tenants', 'label' => 'Customer Stores', 'icon' => 'users'],
    ['url' => '/admin/products', 'label' => 'Products', 'icon' => 'package'],
    ['url' => '/admin/categories', 'label' => 'Categories', 'icon' => 'folder'],
    ['url' => '/admin/banners', 'label' => 'Banners', 'icon' => 'image'],
    ['url' => '/admin/homepage-layout', 'label' => 'Homepage Layout', 'icon' => 'layout'],
    ['url' => '/admin/payment-offers', 'label' => 'Payment Offers', 'icon' => 'tag'],
    ['url' => '/admin/upi', 'label' => 'UPI Apps', 'icon' => 'smartphone'],
    ['url' => '/admin/theme', 'label' => 'Theme & Branding', 'icon' => 'palette'],
    ['url' => '/admin/orders', 'label' => 'Orders', 'icon' => 'cart'],
];

$currentAdminPath = current_path();
$currentTitle = 'Admin';
foreach ($adminNav as $nav) {
    if ($nav['exact'] ?? false) {
        if ($currentAdminPath === $nav['url']) $currentTitle = $nav['label'];
    } else {
        if ($currentAdminPath === $nav['url'] || strpos($currentAdminPath, $nav['url'] . '/') === 0) {
            $currentTitle = $nav['label'];
        }
    }
}

$theme = get_theme();
$siteName = $theme['site_name'] ?? DEFAULT_SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($currentTitle) ?> — <?= e($siteName) ?> Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-wrapper">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="/" class="sidebar-store-link">🏪 View store</a>
            <h2 class="sidebar-title"><?= e($siteName) ?> Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($adminNav as $nav): 
                $isActive = ($nav['exact'] ?? false) 
                    ? ($currentAdminPath === $nav['url'])
                    : ($currentAdminPath === $nav['url'] || strpos($currentAdminPath, $nav['url'] . '/') === 0);
            ?>
            <a href="<?= e($nav['url']) ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                <span class="sidebar-icon"><?= admin_icon($nav['icon']) ?></span>
                <span><?= e($nav['label']) ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <form method="POST" action="/admin/logout">
                <input type="hidden" name="admin_action" value="logout">
                <button type="submit" class="sidebar-link logout-btn">
                    <span class="sidebar-icon"><?= admin_icon('logout') ?></span>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content area -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="menu-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                ☰
            </button>
            <h1 class="topbar-title"><?= e($currentTitle) ?></h1>
            <span class="topbar-user"><?= e($_SESSION['admin_username'] ?? 'Admin') ?></span>
        </header>
        <main class="admin-content">
