<?php
/**
 * Tenant Admin UPI Configuration Page
 * Allows tenant to set their own UPI ID, payee name, and note template
 * All payments to this store will go to their UPI
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantSlug = $tenant['slug'] ?? '';
$tenantId = $tenant['id'] ?? '';
$tenantName = $tenant['name'] ?? 'Store';

// Auth guard
if (empty($_SESSION['tenant_admin_token']) || ($_SESSION['tenant_admin_slug'] ?? '') !== $tenantSlug) {
    redirect("/t/{$tenantSlug}/admin/login");
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['upi_action'] ?? '';
    
    if ($action === 'save_upi') {
        $upiId = clean_upi_id($_POST['upi_id'] ?? '');
        $payeeName = trim(preg_replace('/[\x00-\x1f\x7f]/', '', $_POST['upi_payee_name'] ?? ''));
        $noteTemplate = trim(preg_replace('/[\x00-\x1f\x7f]/', '', $_POST['upi_note_template'] ?? ''));
        
        if (!$upiId) {
            flash('error', 'UPI ID is required');
            redirect("/t/{$tenantSlug}/admin/upi");
        }
        if (!is_valid_upi_id($upiId)) {
            flash('error', 'Enter a valid UPI ID (e.g., name@paytm)');
            redirect("/t/{$tenantSlug}/admin/upi");
        }
        if (!$payeeName) {
            flash('error', 'Payee name is required');
            redirect("/t/{$tenantSlug}/admin/upi");
        }
        if (!$noteTemplate || strpos($noteTemplate, '{reference}') === false) {
            flash('error', 'Payment note must include {reference}');
            redirect("/t/{$tenantSlug}/admin/upi");
        }
        
        supabase_query('tenants', ['id' => 'eq.' . $tenantId], 'PATCH', [
            'upi_id' => $upiId,
            'upi_payee_name' => $payeeName,
            'upi_note_template' => $noteTemplate,
        ]);
        
        flash('success', 'UPI settings saved. All payments will now go to your UPI ID.');
        redirect("/t/{$tenantSlug}/admin/upi");
    }
    
    // Handle logout from this page too
    if (($_POST['tenant_action'] ?? '') === 'logout') {
        unset($_SESSION['tenant_admin_token'], $_SESSION['tenant_admin_user_id'], $_SESSION['tenant_admin_slug']);
        redirect("/t/{$tenantSlug}/admin/login");
    }
}

// Refresh tenant data
$tenant = get_tenant_by_slug($tenantSlug);
$_SESSION['current_tenant'] = $tenant;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>UPI Payment — <?= e($tenantName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f8fafc;min-height:100vh}
.ta-header{position:sticky;top:0;z-index:30;height:56px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #e5e7eb;background:#fff;padding:0 16px}
.ta-header h1{font-size:15px;font-weight:600;flex:1}
.ta-nav{display:flex;gap:8px;overflow-x:auto;padding:12px 16px;background:#fff;border-bottom:1px solid #f0f0f0}
.ta-nav a{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;text-decoration:none;color:#333;white-space:nowrap;transition:background .15s}
.ta-nav a.active{background:#2874f0;color:#fff}
.ta-nav a:hover:not(.active){background:#f0f0f0}
.ta-main{padding:16px;max-width:600px}
.ta-main h2{font-size:1.25rem;font-weight:700;margin-bottom:4px}
.ta-main .sub{font-size:13px;color:#666;margin-bottom:1.5rem}
.upi-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1.5rem}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:12px;font-weight:500;margin-bottom:4px;color:#333}
.form-group input{width:100%;padding:10px 14px;border:1px solid #d0d0d0;border-radius:8px;font-size:14px;outline:none;transition:border .15s}
.form-group input:focus{border-color:#2874f0}
.form-group .hint{font-size:11px;color:#666;margin-top:4px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn-primary{padding:10px 20px;background:#2874f0;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}
.btn-primary:hover{background:#1a5dc8}
.alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:1rem}
.current-info{background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 14px;font-size:13px;margin-bottom:1rem}
.current-info strong{color:#0369a1}
</style>
</head>
<body>
<header class="ta-header">
    <a href="/t/<?= e($tenantSlug) ?>" style="font-size:12px;color:#666;text-decoration:none">🏪 View store</a>
    <h1>UPI Payment</h1>
    <form method="POST" style="display:inline">
        <input type="hidden" name="tenant_action" value="logout">
        <button type="submit" style="font-size:12px;color:#666;border:none;background:none;cursor:pointer">🚪 Sign out</button>
    </form>
</header>

<nav class="ta-nav">
    <a href="/t/<?= e($tenantSlug) ?>/admin">Dashboard</a>
    <a href="/t/<?= e($tenantSlug) ?>/admin/products">Products</a>
    <a href="/t/<?= e($tenantSlug) ?>/admin/upi" class="active">UPI Payment</a>
    <a href="/t/<?= e($tenantSlug) ?>" target="_blank">Preview Store</a>
</nav>

<main class="ta-main">
    <?php if ($msg = get_flash('success')): ?>
    <div class="alert-success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = get_flash('error')): ?>
    <div class="alert-error"><?= e($msg) ?></div>
    <?php endif; ?>

    <h2>UPI Payment</h2>
    <p class="sub">One UPI ID receives money from every UPI app on your store. Payments from customers go directly to YOUR UPI — not the main website.</p>
    
    <?php if (!empty($tenant['upi_id'])): ?>
    <div class="current-info">
        Currently receiving payments at: <strong><?= e($tenant['upi_id']) ?></strong>
        <?php if (!empty($tenant['upi_payee_name'])): ?>
        (<?= e($tenant['upi_payee_name']) ?>)
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="upi-card">
        <form method="POST">
            <input type="hidden" name="upi_action" value="save_upi">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>UPI ID *</label>
                    <input type="text" name="upi_id" value="<?= e($tenant['upi_id'] ?? '') ?>" placeholder="yourname@okaxis" required>
                    <p class="hint">You can also paste a full UPI link (upi://pay?pa=...)</p>
                </div>
                <div class="form-group">
                    <label>Payee Name *</label>
                    <input type="text" name="upi_payee_name" value="<?= e($tenant['upi_payee_name'] ?? '') ?>" placeholder="Exact Paytm merchant name">
                    <p class="hint">Use the exact name shown in Paytm for Business.</p>
                </div>
            </div>
            
            <div class="form-group">
                <label>Payment note template *</label>
                <input type="text" name="upi_note_template" value="<?= e($tenant['upi_note_template'] ?? 'Order payment {reference}') ?>" placeholder="Order payment {reference}" required>
                <p class="hint">Must include {reference}; checkout replaces it with a unique transaction reference.</p>
            </div>
            
            <button type="submit" class="btn-primary">Save UPI</button>
        </form>
    </div>
</main>
</body>
</html>
