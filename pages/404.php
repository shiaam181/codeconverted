<?php
/**
 * 404 Page Not Found
 */
$pageTitle = '404 — Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="error-page">
    <h1 class="error-code">404</h1>
    <p class="error-title">Page not found</p>
    <p class="error-desc">The page you're looking for doesn't exist or is unavailable.</p>
    <a href="/" class="btn-primary">Go home</a>
</div>
</body>
</html>
