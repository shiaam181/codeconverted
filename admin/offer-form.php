<?php
/**
 * Admin Payment Offer Create/Edit
 */
$offerId = get_param('id');
$offer = null;
$isEdit = false;
if ($offerId) {
    $data = supabase_query('payment_offers', ['id' => 'eq.' . $offerId, 'select' => '*']);
    $offer = $data[0] ?? null;
    $isEdit = !!$offer;
}
$brands = ['all' => 'All Offers', 'sbi' => 'ShopMart SBI Credit Card', 'axis' => 'ShopMart Axis Bank', 'upi' => 'UPI'];

require __DIR__ . '/layout.php';
?>

<div class="admin-section">
    <div class="section-header">
        <h2><?= $isEdit ? 'Edit Offer' : 'New Offer' ?></h2>
        <a href="/admin/payment-offers" class="btn-outline">← Back</a>
    </div>
</div>

<form method="POST" action="/admin/payment-offers" class="admin-form">
    <input type="hidden" name="offer_action" value="<?= $isEdit ? 'update' : 'create' ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="offer_id" value="<?= e($offer['id']) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group full">
            <label>Title *</label>
            <input type="text" name="title" required value="<?= e($offer['title'] ?? '') ?>" class="form-input" placeholder="Get ₹50 cashback">
        </div>
        <div class="form-group full">
            <label>Description</label>
            <textarea name="description" rows="3" class="form-textarea"><?= e($offer['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Coupon Code</label>
            <input type="text" name="code" value="<?= e($offer['code'] ?? '') ?>" class="form-input" placeholder="FLAT50">
        </div>
        <div class="form-group">
            <label>Brand/Tab</label>
            <select name="brand" class="form-select">
                <?php foreach ($brands as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($offer['brand'] ?? 'all') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group full">
            <label>Logo URL</label>
            <input type="url" name="logo_url" value="<?= e($offer['logo_url'] ?? '') ?>" class="form-input" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" value="<?= $offer['sort_order'] ?? 0 ?>" class="form-input">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" <?= ($offer['is_active'] ?? true) ? 'checked' : '' ?>>
                Active
            </label>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="/admin/payment-offers" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Update' : 'Create' ?></button>
    </div>
</form>

<?php require __DIR__ . '/layout-end.php'; ?>
