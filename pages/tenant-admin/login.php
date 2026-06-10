<?php
/**
 * Tenant Admin Login Page
 * Uses synthetic email format: username.slug@tenant.local
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$tenantSlug = $tenant['slug'] ?? '';
$tenantName = $tenant['name'] ?? 'Store';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['tenant_login'] ?? '') === '1') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$username || !$password) {
        $loginError = 'Username and password are required';
    } else {
        // Build synthetic email: username.slug@tenant.local
        $email = strtolower($username) . '.' . strtolower($tenantSlug) . '@tenant.local';
        $authResult = supabase_auth_login($email, $password);
        
        if (isset($authResult['error'])) {
            $loginError = $authResult['error']['message'] ?? 'Invalid credentials';
        } elseif (!empty($authResult['access_token'])) {
            // Verify this user owns this tenant
            $userId = $authResult['user']['id'] ?? '';
            $_SESSION['tenant_admin_token'] = $authResult['access_token'];
            $_SESSION['tenant_admin_user_id'] = $userId;
            $_SESSION['tenant_admin_slug'] = $tenantSlug;
            redirect("/t/{$tenantSlug}/admin");
        } else {
            $loginError = 'Login failed. Please check your credentials.';
        }
    }
}

// Check if already logged in
if (!empty($_SESSION['tenant_admin_token']) && ($_SESSION['tenant_admin_slug'] ?? '') === $tenantSlug) {
    redirect("/t/{$tenantSlug}/admin");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Store Admin — <?= e($tenantName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f5f5f5;min-height:100vh;display:grid;place-items:center;padding:1rem}
.login-card{background:#fff;width:100%;max-width:400px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);border:1px solid #e5e7eb;padding:2rem}
.back-link{font-size:13px;color:#666;text-decoration:none;display:block;margin-bottom:1rem}
.back-link:hover{text-decoration:underline}
h1{font-size:1.5rem;font-weight:700;margin-bottom:0.25rem}
.sub{font-size:13px;color:#666;margin-bottom:1.5rem}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:13px;font-weight:500;margin-bottom:4px;color:#333}
.form-group input{width:100%;padding:10px 14px;border:1px solid #d0d0d0;border-radius:8px;font-size:14px;outline:none;transition:border .15s}
.form-group input:focus{border-color:#2874f0}
.error-msg{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#b91c1c;margin-bottom:1rem}
.submit-btn{width:100%;padding:12px;background:#2874f0;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;transition:background .15s}
.submit-btn:hover{background:#1a5dc8}
</style>
</head>
<body>
<div class="login-card">
    <a href="/t/<?= e($tenantSlug) ?>" class="back-link">← Back to store</a>
    <h1>Store Admin</h1>
    <p class="sub">Sign in with credentials provided to you.</p>
    
    <?php if (!empty($loginError)): ?>
    <div class="error-msg"><?= e($loginError) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="tenant_login" value="1">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required autocomplete="off" value="<?= e($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="new-password">
        </div>
        <button type="submit" class="submit-btn">Sign In</button>
    </form>
</div>
</body>
</html>
