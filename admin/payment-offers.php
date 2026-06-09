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

        $payload = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'code' => trim(strtoupper($_POST['code'] ?? '')) ?: null,
            'logo_url' => $logoUrl,
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
}

$offers = get_payment_offers_admin();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <div class="section-header">
        <div>
            <h2>Payment Offers</h2>
            <p class="text-muted">Cashback offers, coupons & logos shown on the checkout payments page.</p>
        </div>
        <a href="/admin/payment-offers/new" class="btn-primary">+ New offer</a>
    </div>
</div>

<?php if (empty($offers)): ?>
<div class="empty-state"><p>No offers yet.</p></div>
<?php else: ?>
<div class="cards-list">
    <?php foreach ($offers as $o): ?>
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
