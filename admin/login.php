<?php
/**
 * Admin Login Page
 */

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['admin_action'] ?? '') === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (strlen($username) < 3 || strlen($password) < 6) {
        $loginError = 'Username must be 3+ chars, password 6+ chars.';
    } else {
        $email = strtolower($username) . '@admin.local';
        $result = supabase_auth_login($email, $password);
        
        if (isset($result['error'])) {
            $loginError = $result['error']['message'] ?? 'Login failed. Check credentials.';
        } else {
            $token = $result['access_token'] ?? '';
            $userId = $result['user']['id'] ?? '';
            if (!$token || !$userId) {
                $loginError = 'Login failed: no token received. Check Supabase configuration.';
            } else {
                $_SESSION['admin_token'] = $token;
                $_SESSION['admin_user_id'] = $userId;
                $_SESSION['admin_username'] = $username;
                redirect('/admin');
            }
        }
    }
}

// Handle signup POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['admin_action'] ?? '') === 'signup') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (strlen($username) < 3 || strlen($password) < 6) {
        $loginError = 'Username must be 3+ chars, password 6+ chars.';
    } else {
        $email = strtolower($username) . '@admin.local';
        $result = supabase_auth_signup($email, $password);
        
        if (isset($result['error'])) {
            $loginError = $result['error']['message'] ?? 'Signup failed.';
        } else {
            // Auto-login after signup
            $loginResult = supabase_auth_login($email, $password);
            if (!isset($loginResult['error']) && !empty($loginResult['access_token'])) {
                $_SESSION['admin_token'] = $loginResult['access_token'];
                $_SESSION['admin_user_id'] = $loginResult['user']['id'] ?? '';
                $_SESSION['admin_username'] = $username;
                // Try to claim first admin
                supabase_rpc_with_token('claim_first_admin', [], $_SESSION['admin_token']);
                redirect('/admin');
            } else {
                $loginError = 'Account created. Please sign in.';
            }
        }
    }
}

$mode = $_GET['mode'] ?? 'signin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — <?= e(DEFAULT_SITE_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="admin-login-page">
    <div class="admin-login-card">
        <a href="/" class="back-link">← Back to store</a>
        <h1>Admin Panel</h1>
        <p class="login-subtitle">
            <?= $mode === 'signin' ? 'Sign in to manage your store' : 'Create the first admin account' ?>
        </p>
        
        <?php if (!empty($loginError)): ?>
        <div class="alert alert-error"><?= e($loginError) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="/admin/login" class="login-form">
            <input type="hidden" name="admin_action" value="<?= $mode === 'signin' ? 'login' : 'signup' ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="32" 
                       placeholder="e.g. admin" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6" 
                       placeholder="Min 6 characters" autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-primary btn-full">
                <?= $mode === 'signin' ? 'Sign In' : 'Create Account' ?>
            </button>
        </form>
        
        <p class="login-toggle">
            <?php if ($mode === 'signin'): ?>
            First time setup? <a href="/admin/login?mode=signup">Create admin account</a>
            <?php else: ?>
            Already have an account? <a href="/admin/login?mode=signin">Sign in</a>
            <?php endif; ?>
        </p>
        <p class="login-hint">The first user to sign up automatically becomes the admin.</p>
    </div>
</div>
</body>
</html>
