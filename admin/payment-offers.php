<?php
/**
 * Admin Payment Offers
 * Features: Create, Edit, Delete offers with logo upload
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['offer_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $logoUrl = trim($_POST['logo_url'] ?? '') ?: null;
        if (!empty($_FILES['logo_file']['tmp_name'])) {
            $uploaded = upload_to_supabase_storage($_FILES['logo_file'], 'payment-offers');
            if ($uploaded) $logoUrl = $uploaded;
        }

        $logoUrl2 = trim($_POST['logo_url_2'] ?? '') ?: null;
        if (!empty($_FILES['logo_file_2']['tmp_name'])) {
            $uploaded2 = upload_to_supabase_storage($_FILES['logo_file_2'], 'payment-offers');
            if ($uploaded2) $logoUrl2 = $uploaded2;
        }

        $payload = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'code' => trim(strtoupper($_POST['code'] ?? '')) ?: null,
            'logo_url' => $logoUrl,
            'logo_url_2' => $logoUrl2,
            'brand' => $_POST['brand'] ?? 'all',
            'is_active' => isset($_POST['is_active']),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];
        
        if ($action === 'update' && !empty($_POST['offer_id'])) {
            supabase_query('payment_offers', ['id' => 'eq.' . $_POST['offer_id']], 'PATCH', $payload);
        } else {
            supabase_query('payment_offers', [], 'POST', $payload);
        }
        flash('success', 'Offer saved');
        redirect('/admin/payment-offers');
    }
    
    if ($action === 'toggle' && !empty($_POST['offer_id'])) {
        $current = $_POST['current_active'] === '1';
        supabase_query('payment_offers', ['id' => 'eq.' . $_POST['offer_id']], 'PATCH', ['is_active' => !$current]);
        redirect('/admin/payment-offers');
    }

    if ($action === 'delete' && !empty($_POST['offer_id'])) {
        supabase_query('payment_offers', ['id' => 'eq.' . $_POST['offer_id']], 'DELETE');
        flash('success', 'Offer removed');
        redirect('/admin/payment-offers');
    }

    // Save cashback banner settings
    if ($action === 'save_banner') {
        $bannerTitle = trim($_POST['banner_title'] ?? '') ?: '5% Cashback';
        $bannerDesc = trim($_POST['banner_description'] ?? '') ?: 'Claim now with payment offers';
        
        $logo1 = trim($_POST['banner_logo_1'] ?? '') ?: null;
        if (!empty($_FILES['banner_logo_file_1']['tmp_name'])) {
            $up1 = upload_to_supabase_storage($_FILES['banner_logo_file_1'], 'payment-offers');
            if ($up1) $logo1 = $up1;
        }
        $logo2 = trim($_POST['banner_logo_2'] ?? '') ?: null;
        if (!empty($_FILES['banner_logo_file_2']['tmp_name'])) {
            $up2 = upload_to_supabase_storage($_FILES['banner_logo_file_2'], 'payment-offers');
            if ($up2) $logo2 = $up2;
        }

        // Check if a cashback_banner offer already exists
        $existing = supabase_query('payment_offers', ['brand' => 'eq.cashback_banner', 'select' => 'id']);
        $bannerPayload = [
            'title' => $bannerTitle,
            'description' => $bannerDesc,
            'logo_url' => $logo1,
            'logo_url_2' => $logo2,
            'brand' => 'cashback_banner',
            'is_active' => true,
            'sort_order' => 0,
        ];

        if (!empty($existing) && !isset($existing['error']) && !empty($existing[0]['id'])) {
            supabase_query('payment_offers', ['id' => 'eq.' . $existing[0]['id']], 'PATCH', $bannerPayload);
        } else {
            supabase_query('payment_offers', [], 'POST', $bannerPayload);
        }
        flash('success', 'Cashback banner saved');
        redirect('/admin/payment-offers');
    }
}

$offers = get_payment_offers_admin();

// Get existing cashback banner
$cashbackBanner = null;
foreach ($offers as $o) {
    if (($o['brand'] ?? '') === 'cashback_banner') {
        $cashbackBanner = $o;
        break;
    }
}

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<!-- Cashback Banner Settings -->
<div class="admin-section">
    <div class="section-header">
        <div>
            <h2>💚 Cashback Banner</h2>
            <p class="text-muted">The green banner shown at the top of the payment page with shimmer effect.</p>
        </div>
    </div>
</div>

<form method="POST" action="/admin/payment-offers" enctype="multipart/form-data" class="admin-form" style="margin-bottom:32px">
    <input type="hidden" name="offer_action" value="save_banner">
    <div class="form-grid">
        <div class="form-group">
            <label>Banner Title</label>
            <input type="text" name="banner_title" value="<?= e($cashbackBanner['title'] ?? '5% Cashback') ?>" class="form-input" placeholder="5% Cashback">
        </div>
        <div class="form-group">
            <label>Banner Description</label>
            <input type="text" name="banner_description" value="<?= e($cashbackBanner['description'] ?? 'Claim now with payment offers') ?>" class="form-input" placeholder="Claim now with payment offers">
        </div>
        <div class="form-group">
            <label>Logo 1 (left icon)</label>
            <div style="display:flex;align-items:center;gap:10px">
                <?php if (!empty($cashbackBanner['logo_url'])): ?>
                <img src="<?= e($cashbackBanner['logo_url']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:contain;border:1px solid #eee;padding:2px" alt="">
                <?php endif; ?>
                <input type="file" name="banner_logo_file_1" accept="image/*" style="font-size:12px">
            </div>
            <input type="text" name="banner_logo_1" value="<?= e($cashbackBanner['logo_url'] ?? '') ?>" class="form-input" placeholder="Or paste image URL" style="margin-top:6px">
        </div>
        <div class="form-group">
            <label>Logo 2 (right icon)</label>
            <div style="display:flex;align-items:center;gap:10px">
                <?php if (!empty($cashbackBanner['logo_url_2'])): ?>
                <img src="<?= e($cashbackBanner['logo_url_2']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:contain;border:1px solid #eee;padding:2px" alt="">
                <?php endif; ?>
                <input type="file" name="banner_logo_file_2" accept="image/*" style="font-size:12px">
            </div>
            <input type="text" name="banner_logo_2" value="<?= e($cashbackBanner['logo_url_2'] ?? '') ?>" class="form-input" placeholder="Or paste image URL" style="margin-top:6px">
        </div>
    </div>
    <!-- Preview -->
    <div style="margin:16px 0;padding:14px 16px;background:#e8f5e9;border-radius:10px;display:flex;align-items:center;gap:12px;position:relative;overflow:hidden">
        <div style="flex:1">
            <strong style="color:#1b5e20;font-size:15px"><?= e($cashbackBanner['title'] ?? '5% Cashback') ?></strong>
            <p style="color:#555;font-size:13px;margin-top:2px"><?= e($cashbackBanner['description'] ?? 'Claim now with payment offers') ?></p>
        </div>
        <?php if (!empty($cashbackBanner['logo_url']) || !empty($cashbackBanner['logo_url_2'])): ?>
        <div style="display:flex;gap:6px">
            <?php if (!empty($cashbackBanner['logo_url'])): ?><img src="<?= e($cashbackBanner['logo_url']) ?>" style="width:30px;height:30px;border-radius:50%;background:#fff;padding:2px;box-shadow:0 1px 3px rgba(0,0,0,.1)"><?php endif; ?>
            <?php if (!empty($cashbackBanner['logo_url_2'])): ?><img src="<?= e($cashbackBanner['logo_url_2']) ?>" style="width:30px;height:30px;border-radius:50%;background:#fff;padding:2px;box-shadow:0 1px 3px rgba(0,0,0,.1)"><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn-primary">Save Banner</button>
    </div>
</form>

<hr style="border:none;border-top:1px solid #eee;margin:24px 0">

<div class="admin-section">
    <div class="section-header">
        <div>
            <h2>Payment Offers</h2>
            <p class="text-muted">Cashback offers, coupons & logos shown on the checkout payments page.</p>
        </div>
        <a href="/admin/payment-offers/new" class="btn-primary">+ New offer</a>
    </div>
</div>

<?php if (empty($offers) || count(array_filter($offers, fn($o) => ($o['brand'] ?? '') !== 'cashback_banner')) === 0): ?>
<div class="empty-state"><p>No offers yet.</p></div>
<?php else: ?>
<div class="cards-list">
    <?php foreach ($offers as $o): if (($o['brand'] ?? '') === 'cashback_banner') continue; ?>
    <div class="list-card">
        <div class="list-card-left">
            <?php if (!empty($o['logo_url'])): ?>
            <img src="<?= e($o['logo_url']) ?>" class="list-card-logo" alt="">
            <?php else: ?>
            <div class="list-card-logo placeholder">🏷️</div>
            <?php endif; ?>
            <div>
                <strong><?= e($o['title']) ?></strong>
                <?php if (!empty($o['description'])): ?>
                <p class="text-muted text-sm"><?= e($o['description']) ?></p>
                <?php endif; ?>
                <p class="text-muted text-sm">
                    <?= e(ucfirst($o['brand'] ?? 'all')) ?>
                    <?= !empty($o['code']) ? ' · code: <span class="font-mono">' . e($o['code']) . '</span>' : '' ?>
                </p>
            </div>
        </div>
        <div class="list-card-actions">
            <form method="POST" action="/admin/payment-offers" style="display:inline">
                <input type="hidden" name="offer_action" value="toggle">
                <input type="hidden" name="offer_id" value="<?= e($o['id']) ?>">
                <input type="hidden" name="current_active" value="<?= $o['is_active'] ? '1' : '0' ?>">
                <button type="submit" class="toggle-pill <?= $o['is_active'] ? 'active' : '' ?>">
                    <?= $o['is_active'] ? '● Active' : '○ Off' ?>
                </button>
            </form>
            <a href="/admin/payment-offers/edit?id=<?= e($o['id']) ?>" class="btn-sm">Edit</a>
            <form method="POST" action="/admin/payment-offers" style="display:inline" onsubmit="return confirm('Delete?')">
                <input type="hidden" name="offer_action" value="delete">
                <input type="hidden" name="offer_id" value="<?= e($o['id']) ?>">
                <button type="submit" class="btn-sm btn-danger">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
