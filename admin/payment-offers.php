<?php
/**
 * Admin Payment Offers
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['offer_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $payload = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'code' => trim($_POST['code'] ?? '') ?: null,
            'logo_url' => trim($_POST['logo_url'] ?? '') ?: null,
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
            <p class="text-muted">Cashback offers, coupons & logos shown on checkout.</p>
        </div>
        <a href="/admin/payment-offers/new" class="btn-primary">+ New Offer</a>
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
                <p class="text-muted text-sm"><?= e($o['description'] ?? '') ?></p>
                <p class="text-muted text-sm"><?= e($o['brand']) ?> <?= $o['code'] ? '· code: ' . e($o['code']) : '' ?></p>
            </div>
        </div>
        <div class="list-card-actions">
            <span class="badge"><?= $o['is_active'] ? 'Active' : 'Inactive' ?></span>
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
