<?php
/**
 * Admin UPI Settings
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['upi_action'] ?? '';
    
    // Save main UPI account
    if ($action === 'save_account') {
        $upiId = clean_upi_id($_POST['upi_id'] ?? '');
        $payeeName = trim($_POST['upi_payee_name'] ?? '');
        $noteTemplate = trim($_POST['upi_note_template'] ?? 'Order payment {reference}');
        
        if (!$upiId || !is_valid_upi_id($upiId)) {
            flash('error', 'Enter a valid UPI ID (e.g. name@paytm)');
            redirect('/admin/upi');
        }
        
        supabase_query('theme_config', ['id' => 'eq.1'], 'PATCH', [
            'upi_id' => $upiId,
            'upi_payee_name' => $payeeName,
            'upi_note_template' => $noteTemplate,
        ]);
        flash('success', 'UPI account saved');
        redirect('/admin/upi');
    }
    
    // CRUD for UPI apps
    if ($action === 'create_app' || $action === 'update_app') {
        $logoUrl = trim($_POST['app_logo_url'] ?? '') ?: null;
        
        // Handle file upload for logo - overrides URL if present
        if (!empty($_FILES['app_logo_file']['tmp_name']) && $_FILES['app_logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedUrl = upload_to_supabase_storage($_FILES['app_logo_file'], 'upi-logos');
            if ($uploadedUrl) {
                $logoUrl = $uploadedUrl;
            }
        }
        
        $payload = [
            'name' => trim($_POST['app_name'] ?? ''),
            'logo_url' => $logoUrl,
            'scheme' => 'upi',
            'is_active' => isset($_POST['app_is_active']),
            'sort_order' => (int) ($_POST['app_sort_order'] ?? 0),
        ];
        
        if ($action === 'update_app' && !empty($_POST['app_id'])) {
            supabase_query('upi_methods', ['id' => 'eq.' . $_POST['app_id']], 'PATCH', $payload);
        } else {
            supabase_query('upi_methods', [], 'POST', $payload);
        }
        flash('success', 'UPI app saved');
        redirect('/admin/upi');
    }
    
    if ($action === 'delete_app' && !empty($_POST['app_id'])) {
        supabase_query('upi_methods', ['id' => 'eq.' . $_POST['app_id']], 'DELETE');
        flash('success', 'UPI app removed');
        redirect('/admin/upi');
    }
}

$theme = get_theme();
$upiApps = get_upi_methods();

// Auto-seed default UPI apps if none exist
if (empty($upiApps)) {
    $defaults = [
        ['name' => 'Google Pay', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Google_Pay_Logo.svg/512px-Google_Pay_Logo.svg.png', 'scheme' => 'upi', 'is_active' => true, 'sort_order' => 1],
        ['name' => 'PhonePe', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/71/PhonePe_Logo.svg/1024px-PhonePe_Logo.svg.png', 'scheme' => 'upi', 'is_active' => true, 'sort_order' => 2],
        ['name' => 'Paytm', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Paytm_Logo_%28standalone%29.svg/1024px-Paytm_Logo_%28standalone%29.svg.png', 'scheme' => 'upi', 'is_active' => true, 'sort_order' => 3],
        ['name' => 'BHIM', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/UPI-Logo-vector.svg/1024px-UPI-Logo-vector.svg.png', 'scheme' => 'upi', 'is_active' => true, 'sort_order' => 4],
        ['name' => 'CRED', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/98/Cred_Logo.svg/512px-Cred_Logo.svg.png', 'scheme' => 'upi', 'is_active' => true, 'sort_order' => 5],
    ];
    foreach ($defaults as $app) {
        supabase_query('upi_methods', [], 'POST', $app);
    }
    $upiApps = get_upi_methods();
}

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
<div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <h2>UPI Payments</h2>
    <p class="text-muted">One UPI ID receives money from every app. Add apps to show on checkout.</p>
</div>

<!-- Main UPI Account -->
<div class="admin-card">
    <h3>Your UPI Account</h3>
    <p class="text-muted text-sm">Money from all UPI apps lands in this single UPI ID.</p>
    <form method="POST" action="/admin/upi" class="admin-form">
        <input type="hidden" name="upi_action" value="save_account">
        <div class="form-grid">
            <div class="form-group">
                <label>UPI ID *</label>
                <input type="text" name="upi_id" value="<?= e($theme['upi_id'] ?? '') ?>" class="form-input" placeholder="yourname@okaxis" required>
            </div>
            <div class="form-group">
                <label>Payee Name *</label>
                <input type="text" name="upi_payee_name" value="<?= e($theme['upi_payee_name'] ?? '') ?>" class="form-input" placeholder="Exact merchant name">
            </div>
            <div class="form-group full">
                <label>Payment Note Template</label>
                <input type="text" name="upi_note_template" value="<?= e($theme['upi_note_template'] ?? 'Order payment {reference}') ?>" class="form-input" placeholder="Order payment {reference}">
                <p class="form-hint">Must include {reference}</p>
            </div>
        </div>
        <button type="submit" class="btn-primary">Save UPI Account</button>
    </form>
</div>

<!-- UPI Apps List -->
<div class="admin-section" style="margin-top: 2rem;">
    <div class="section-header">
        <h3>UPI Apps on Checkout</h3>
        <a href="/admin/upi/new" class="btn-primary">+ New App</a>
    </div>
</div>

<?php if (empty($upiApps)): ?>
<div class="empty-state"><p>No UPI apps configured yet.</p></div>
<?php else: ?>
<div class="cards-list">
    <?php foreach ($upiApps as $app): ?>
    <div class="list-card">
        <div class="list-card-left">
            <?php if (!empty($app['logo_url'])): ?>
            <img src="<?= e($app['logo_url']) ?>" class="list-card-logo" alt="">
            <?php else: ?>
            <div class="list-card-logo placeholder">📱</div>
            <?php endif; ?>
            <div>
                <strong><?= e($app['name']) ?></strong>
                <p class="text-muted text-sm">scheme: upi · <?= $app['is_active'] ? 'Active' : 'Inactive' ?></p>
            </div>
        </div>
        <div class="list-card-actions">
            <a href="/admin/upi/edit?id=<?= e($app['id']) ?>" class="btn-sm">Edit</a>
            <form method="POST" action="/admin/upi" style="display:inline" onsubmit="return confirm('Remove?')">
                <input type="hidden" name="upi_action" value="delete_app">
                <input type="hidden" name="app_id" value="<?= e($app['id']) ?>">
                <button type="submit" class="btn-sm btn-danger">Remove</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
