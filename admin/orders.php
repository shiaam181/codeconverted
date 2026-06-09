<?php
/**
 * Admin Orders Management
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['order_action'] ?? '';
    $orderId = $_POST['order_id'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';
    
    if ($action === 'update_status' && $orderId && $newStatus) {
        supabase_query('orders', ['id' => 'eq.' . $orderId], 'PATCH', ['status' => $newStatus]);
        flash('success', 'Order status updated');
        redirect('/admin/orders');
    }
}

$orders = get_admin_orders();
$statuses = ['pending_payment', 'payment_submitted', 'paid', 'confirmed', 'shipped', 'delivered', 'cancelled', 'payment_rejected'];

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <h2>Orders</h2>
    <p class="text-muted">Guest orders placed on the storefront. <?= count($orders) ?> orders.</p>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state"><p>No orders yet.</p></div>
<?php else: ?>
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Actions</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td class="font-mono"><?= e(strtoupper(substr($o['id'], 0, 8))) ?></td>
                <td class="td-title"><?= e($o['customer_name']) ?></td>
                <td><?= e($o['customer_phone']) ?></td>
                <td><strong>₹<?= number_format($o['total'], 0, '.', ',') ?></strong></td>
                <td class="text-muted text-sm">
                    <?php if (!empty($o['payment_reference'])): ?>
                    <span class="font-mono"><?= e($o['payment_reference']) ?></span><br>
                    <?= e($o['payment_app'] ?? 'UPI') ?>
                    <?php else: ?>
                    —
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="/admin/orders" class="inline-form">
                        <input type="hidden" name="order_action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= e($o['id']) ?>">
                        <select name="new_status" class="form-select-sm" onchange="this.form.submit()">
                            <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= ($o['status'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="td-actions">
                    <form method="POST" action="/admin/orders" style="display:inline">
                        <input type="hidden" name="order_action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= e($o['id']) ?>">
                        <input type="hidden" name="new_status" value="paid">
                        <button type="submit" class="btn-sm btn-success">Confirm</button>
                    </form>
                    <form method="POST" action="/admin/orders" style="display:inline">
                        <input type="hidden" name="order_action" value="update_status">
                        <input type="hidden" name="order_id" value="<?= e($o['id']) ?>">
                        <input type="hidden" name="new_status" value="payment_rejected">
                        <button type="submit" class="btn-sm btn-danger">Reject</button>
                    </form>
                </td>
                <td class="text-muted text-sm"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
