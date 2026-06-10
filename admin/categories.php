<?php
/**
 * Admin Categories Management
 * Features: New category, Bulk upload (images → categories), Edit, Delete
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cat_action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        // Handle image file upload
        $imageUrl = trim($_POST['image_url'] ?? '') ?: null;
        if (!empty($_FILES['image_file']['tmp_name'])) {
            $uploaded = upload_to_supabase_storage($_FILES['image_file'], 'categories');
            if ($uploaded) $imageUrl = $uploaded;
        }

        $payload = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? '') ?: slugify($_POST['name'] ?? ''),
            'image_url' => $imageUrl,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ];
        
        if (!$payload['name']) {
            flash('error', 'Name is required');
            redirect('/admin/categories');
        }
        
        if ($action === 'update' && !empty($_POST['cat_id'])) {
            supabase_query('categories', ['id' => 'eq.' . $_POST['cat_id']], 'PATCH', $payload);
        } else {
            supabase_query('categories', [], 'POST', $payload);
        }
        flash('success', 'Category saved');
        redirect('/admin/categories');
    }
    
    if ($action === 'delete' && !empty($_POST['cat_id'])) {
        supabase_query('categories', ['id' => 'eq.' . $_POST['cat_id']], 'DELETE');
        flash('success', 'Category deleted');
        redirect('/admin/categories');
    }

    if ($action === 'bulk_upload') {
        $count = 0;
        if (!empty($_FILES['bulk_images']['tmp_name'])) {
            $maxOrder = 0;
            $existing = get_admin_categories();
            foreach ($existing as $c) $maxOrder = max($maxOrder, $c['sort_order'] ?? 0);
            
            foreach ($_FILES['bulk_images']['tmp_name'] as $i => $tmp) {
                if (!$tmp || $_FILES['bulk_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name' => $_FILES['bulk_images']['name'][$i],
                    'tmp_name' => $tmp,
                    'type' => $_FILES['bulk_images']['type'][$i],
                ];
                $imgUrl = upload_to_supabase_storage($file, 'categories');
                if (!$imgUrl) continue;
                
                $name = pathinfo($_FILES['bulk_images']['name'][$i], PATHINFO_FILENAME);
                $name = str_replace(['-', '_'], ' ', $name);
                $name = ucfirst(trim($name)) ?: 'Category';
                $maxOrder++;
                
                supabase_query('categories', [], 'POST', [
                    'name' => $name,
                    'slug' => slugify($name) . '-' . $maxOrder,
                    'image_url' => $imgUrl,
                    'sort_order' => $maxOrder,
                    'is_active' => true,
                ]);
                $count++;
            }
        }
        flash('success', "Created {$count} categories from uploaded images");
        redirect('/admin/categories');
    }

    if ($action === 'delete_all') {
        $categories = get_admin_categories();
        foreach ($categories as $c) {
            supabase_query('categories', ['id' => 'eq.' . $c['id']], 'DELETE');
        }
        flash('success', 'All categories deleted (' . count($categories) . ')');
        redirect('/admin/categories');
    }

    if ($action === 'fix_images') {
        // Fix categories with NULL/empty image_url by assigning local SVG paths
        $categories = get_admin_categories();
        $fixed = 0;
        foreach ($categories as $cat) {
            if (!empty($cat['image_url'])) continue;
            
            $slug = $cat['slug'] ?? '';
            $localPath = '/assets/icons/categories/' . $slug . '.svg';
            $fullPath = __DIR__ . '/../assets/icons/categories/' . $slug . '.svg';
            
            if (!file_exists($fullPath)) {
                $localPath = '/assets/icons/categories/all.svg';
            }
            
            supabase_query('categories', ['id' => 'eq.' . $cat['id']], 'PATCH', ['image_url' => $localPath]);
            $fixed++;
        }
        flash('success', "Fixed {$fixed} category images");
        redirect('/admin/categories');
    }

    if ($action === 'import_zip') {
        $count = 0;
        $imgCount = 0;
        
        if (empty($_FILES['zip_file']['tmp_name']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Please upload a valid ZIP file.');
            redirect('/admin/categories');
        }
        
        $zip = new ZipArchive();
        if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
            flash('error', 'Could not open ZIP file.');
            redirect('/admin/categories');
        }
        
        // Find manifest.json in the zip
        $manifestContent = null;
        $imagePrefix = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (basename($name) === 'manifest.json') {
                $manifestContent = $zip->getFromIndex($i);
                $imagePrefix = dirname($name);
                if ($imagePrefix === '.') $imagePrefix = '';
                else $imagePrefix .= '/';
                break;
            }
        }
        
        if (!$manifestContent) {
            $zip->close();
            flash('error', 'No manifest.json found in the ZIP file.');
            redirect('/admin/categories');
        }
        
        $manifest = json_decode($manifestContent, true);
        
        if (!$manifest) {
            $zip->close();
            flash('error', 'Could not parse manifest.json: ' . json_last_error_msg());
            redirect('/admin/categories');
        }
        
        // If it's an object, look for an array inside
        if (is_array($manifest) && !isset($manifest[0])) {
            $found = false;
            foreach ($manifest as $key => $val) {
                if (is_array($val) && !empty($val) && isset($val[0])) {
                    $manifest = $val;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $converted = [];
                foreach ($manifest as $key => $val) {
                    if (is_array($val)) {
                        if (!isset($val['slug'])) $val['slug'] = $key;
                        $converted[] = $val;
                    }
                }
                if (!empty($converted)) $manifest = $converted;
            }
        }
        
        // Create local directory for extracted images
        $destDir = __DIR__ . '/../assets/icons/categories';
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);
        
        // Build an index of all files in the zip for easy lookup
        $zipFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!str_ends_with($name, '/')) { // Skip directories
                $zipFiles[strtolower(basename($name))] = $i;
                $zipFiles[strtolower($name)] = $i;
            }
        }
        
        $maxOrder = 0;
        $existing = get_admin_categories();
        foreach ($existing as $c) $maxOrder = max($maxOrder, $c['sort_order'] ?? 0);
        
        foreach ($manifest as $cat) {
            if (!is_array($cat)) continue;
            
            $name = trim($cat['name'] ?? $cat['title'] ?? $cat['label'] ?? '');
            if (!$name) continue;
            
            $slug = trim($cat['slug'] ?? $cat['handle'] ?? '') ?: slugify($name);
            $sortOrder = (int) ($cat['sort_order'] ?? $cat['sortOrder'] ?? $cat['order'] ?? $cat['position'] ?? ++$maxOrder);
            if ($sortOrder > $maxOrder) $maxOrder = $sortOrder;
            
            $isActive = true;
            if (isset($cat['is_active'])) $isActive = (bool) $cat['is_active'];
            elseif (isset($cat['isActive'])) $isActive = (bool) $cat['isActive'];
            elseif (isset($cat['active'])) $isActive = (bool) $cat['active'];
            
            // Find the image file in the zip — try ALL string values in the category entry
            $imageUrl = null;
            $imageFile = trim($cat['image_file'] ?? $cat['image'] ?? $cat['imageUrl'] ?? $cat['icon'] ?? $cat['file'] ?? $cat['filename'] ?? $cat['svg'] ?? $cat['img'] ?? $cat['iconUrl'] ?? $cat['icon_url'] ?? $cat['path'] ?? '');
            
            // If image_url looks like a local/relative path with a filename, use that as fallback
            if (!$imageFile && !empty($cat['image_url'])) {
                $maybeFile = basename($cat['image_url']);
                if (preg_match('/\.(svg|png|jpg|jpeg|webp|gif)$/i', $maybeFile)) {
                    $imageFile = $maybeFile;
                }
            }
            
            // If no known field found, try any string value that looks like a filename
            if (!$imageFile) {
                foreach ($cat as $k => $v) {
                    if (is_string($v) && preg_match('/\.(svg|png|jpg|jpeg|webp|gif)$/i', $v)) {
                        $imageFile = $v;
                        break;
                    }
                }
            }
            
            if ($imageFile) {
                // Try to find in zip index (case-insensitive)
                $searchNames = [
                    strtolower($imageFile),
                    strtolower('images/' . $imageFile),
                    strtolower($imagePrefix . 'images/' . $imageFile),
                    strtolower($imagePrefix . $imageFile),
                ];
                
                $foundIndex = null;
                foreach ($searchNames as $search) {
                    if (isset($zipFiles[$search])) {
                        $foundIndex = $zipFiles[$search];
                        break;
                    }
                }
                
                if ($foundIndex !== null) {
                    // Extract to local assets directory
                    $fileContent = $zip->getFromIndex($foundIndex);
                    $ext = pathinfo($imageFile, PATHINFO_EXTENSION) ?: 'svg';
                    $localName = $slug . '.' . $ext;
                    $localPath = $destDir . '/' . $localName;
                    
                    if (file_put_contents($localPath, $fileContent)) {
                        $imageUrl = '/assets/icons/categories/' . $localName;
                        $imgCount++;
                    }
                }
            }
            
            $payload = [
                'name' => $name,
                'slug' => $slug,
                'image_url' => $imageUrl,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
            ];
            
            supabase_query('categories', [], 'POST', $payload);
            $count++;
        }
        
        $zip->close();
        
        // Debug info
        $debugInfo = '';
        if ($imgCount === 0 && !empty($manifest)) {
            $first = $manifest[0] ?? reset($manifest);
            $debugInfo = ' | Keys: ' . implode(', ', array_keys($first ?? []));
            $debugInfo .= ' | ZIP files: ' . implode(', ', array_slice(array_keys($zipFiles), 0, 10));
        }
        
        flash('success', "Imported {$count} categories ({$imgCount} with images)" . $debugInfo);
        redirect('/admin/categories');
    }

    if ($action === 'import_json') {
        $count = 0;
        $jsonData = null;
        
        if (!empty($_FILES['json_file']['tmp_name']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
            $content = file_get_contents($_FILES['json_file']['tmp_name']);
            $jsonData = json_decode($content, true);
        }
        
        if (!$jsonData) {
            flash('error', 'Invalid JSON file. Could not parse the file.');
            redirect('/admin/categories');
        }
        
        // Handle wrapped objects like { "categories": [...] } or { "data": [...] }
        if (!isset($jsonData[0]) && is_array($jsonData)) {
            // It's an object, look for an array inside
            foreach ($jsonData as $key => $val) {
                if (is_array($val) && isset($val[0])) {
                    $jsonData = $val;
                    break;
                }
            }
        }
        
        if (!is_array($jsonData) || empty($jsonData)) {
            flash('error', 'JSON must contain an array of category objects.');
            redirect('/admin/categories');
        }
        
        $maxOrder = 0;
        $existing = get_admin_categories();
        foreach ($existing as $c) $maxOrder = max($maxOrder, $c['sort_order'] ?? 0);
        
        foreach ($jsonData as $cat) {
            if (!is_array($cat)) continue;
            
            // Try multiple field name variants for name
            $name = trim($cat['name'] ?? $cat['title'] ?? $cat['label'] ?? $cat['category_name'] ?? $cat['categoryName'] ?? '');
            if (!$name) continue;
            
            // Try multiple field name variants for slug
            $slug = trim($cat['slug'] ?? $cat['handle'] ?? $cat['url_key'] ?? $cat['urlKey'] ?? '') ?: slugify($name);
            
            // Try multiple field name variants for image
            $imageUrl = trim($cat['image_url'] ?? $cat['imageUrl'] ?? $cat['image'] ?? $cat['icon'] ?? $cat['icon_url'] ?? $cat['iconUrl'] ?? $cat['thumbnail'] ?? '') ?: null;
            
            // Sort order
            $sortOrder = (int) ($cat['sort_order'] ?? $cat['sortOrder'] ?? $cat['order'] ?? $cat['position'] ?? ++$maxOrder);
            if ($sortOrder > $maxOrder) $maxOrder = $sortOrder;
            
            // Active status
            $isActive = true;
            if (isset($cat['is_active'])) $isActive = (bool) $cat['is_active'];
            elseif (isset($cat['isActive'])) $isActive = (bool) $cat['isActive'];
            elseif (isset($cat['active'])) $isActive = (bool) $cat['active'];
            
            $payload = [
                'name' => $name,
                'slug' => $slug,
                'image_url' => $imageUrl,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
            ];
            
            supabase_query('categories', [], 'POST', $payload);
            $count++;
        }
        
        flash('success', "Imported {$count} categories from JSON");
        redirect('/admin/categories');
    }

    if ($action === 'export_json') {
        $categories = get_admin_categories();
        $export = [];
        foreach ($categories as $c) {
            $export[] = [
                'name' => $c['name'],
                'slug' => $c['slug'],
                'image_url' => $c['image_url'] ?? null,
                'sort_order' => $c['sort_order'] ?? 0,
                'is_active' => $c['is_active'] ?? true,
            ];
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="categories_export.json"');
        echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$categories = get_admin_categories();

require __DIR__ . '/layout.php';
?>

<?php if ($msg = get_flash('success')): ?>
<div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = get_flash('error')): ?>
<div class="alert alert-error"><?= e($msg) ?></div>
<?php endif; ?>

<div class="admin-section">
    <div class="section-header">
        <div>
            <h2>Categories</h2>
            <p class="text-muted">Organize your products into browsable categories.</p>
        </div>
        <div class="btn-group">
            <form method="POST" action="/admin/categories" style="display:inline" onsubmit="return confirm('Delete ALL categories? This cannot be undone.')">
                <input type="hidden" name="cat_action" value="delete_all">
                <button type="submit" class="btn-outline" style="color:#d32f2f;border-color:#d32f2f">🗑️ Delete All</button>
            </form>
            <form method="POST" action="/admin/categories" style="display:inline">
                <input type="hidden" name="cat_action" value="fix_images">
                <button type="submit" class="btn-outline">🔧 Fix Images</button>
            </form>
            <form method="POST" action="/admin/categories" enctype="multipart/form-data" style="display:inline">
                <input type="hidden" name="cat_action" value="import_zip">
                <label class="btn-outline" style="cursor:pointer">
                    📦 Import ZIP
                    <input type="file" name="zip_file" accept=".zip,application/zip" class="file-input-hidden" onchange="this.form.submit()">
                </label>
            </form>
            <form method="POST" action="/admin/categories" enctype="multipart/form-data" style="display:inline">
                <input type="hidden" name="cat_action" value="import_json">
                <label class="btn-outline" style="cursor:pointer">
                    📥 Import JSON
                    <input type="file" name="json_file" accept=".json,application/json" class="file-input-hidden" onchange="this.form.submit()">
                </label>
            </form>
            <form method="POST" action="/admin/categories" style="display:inline">
                <input type="hidden" name="cat_action" value="export_json">
                <button type="submit" class="btn-outline">📤 Export JSON</button>
            </form>
            <form method="POST" action="/admin/categories" enctype="multipart/form-data" style="display:inline">
                <input type="hidden" name="cat_action" value="bulk_upload">
                <label class="btn-outline" style="cursor:pointer">
                    ⬆️ Bulk upload
                    <input type="file" name="bulk_images[]" multiple accept="image/*,image/svg+xml,.svg" class="file-input-hidden" onchange="this.form.submit()">
                </label>
            </form>
            <a href="/admin/categories/new" class="btn-primary">+ New category</a>
        </div>
    </div>
</div>

<?php if (empty($categories)): ?>
<div class="empty-state"><p>No categories yet.</p></div>
<?php else: ?>
<div class="admin-table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Order</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><?= !empty($c['image_url']) ? '<img src="' . e($c['image_url']) . '" class="table-thumb" alt="">' : '—' ?></td>
                <td class="td-title"><?= e($c['name']) ?></td>
                <td class="text-muted"><?= e($c['slug']) ?></td>
                <td><?= $c['sort_order'] ?></td>
                <td><?= $c['is_active'] ? '✓' : '✗' ?></td>
                <td class="td-actions">
                    <a href="/admin/categories/edit?id=<?= e($c['id']) ?>" class="btn-sm">Edit</a>
                    <form method="POST" action="/admin/categories" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="cat_action" value="delete">
                        <input type="hidden" name="cat_id" value="<?= e($c['id']) ?>">
                        <button type="submit" class="btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-end.php'; ?>
