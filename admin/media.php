<?php
/**
 * Admin Media Manager
 * Upload images, view all uploaded files, copy URLs
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['media_action'] ?? '';
    
    if ($action === 'upload') {
        $folder = trim($_POST['folder'] ?? 'general');
        $uploaded = 0;
        
        if (!empty($_FILES['media_files']['tmp_name'])) {
            foreach ($_FILES['media_files']['tmp_name'] as $i => $tmp) {
                if (!$tmp || $_FILES['media_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'name' => $_FILES['media_files']['name'][$i],
                    'tmp_name' => $tmp,
                    'type' => $_FILES['media_files']['type'][$i],
                ];
                $url = upload_to_supabase_storage($file, $folder);
                if ($url) $uploaded++;
            }
        }
        
        if ($uploaded > 0) {
            flash('success', "{$uploaded} file(s) uploaded successfully to /{$folder}/");
        } else {
            flash('error', 'No files were uploaded. Check file types and try again.');
        }
        redirect('/admin/media');
    }
}

// Get list of uploaded files from Supabase Storage
$mediaFiles = get_media_files();

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
            <h2>Media Manager</h2>
            <p class="text-muted">Upload images and copy URLs to use anywhere in your store.</p>
        </div>
    </div>
</div>

<!-- Upload Form -->
<div class="admin-card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 12px;">Upload Images</h3>
    <form method="POST" action="/admin/media" enctype="multipart/form-data">
        <input type="hidden" name="media_action" value="upload">
        
        <div style="margin-bottom: 12px;">
            <label style="font-size:13px;font-weight:500;display:block;margin-bottom:4px;">Folder</label>
            <select name="folder" class="form-select" style="max-width:200px;">
                <option value="general">General</option>
                <option value="products">Products</option>
                <option value="banners">Banners</option>
                <option value="categories">Categories</option>
                <option value="branding">Branding (Logo/Favicon)</option>
                <option value="upi">UPI Apps</option>
                <option value="tabs">Tab Icons</option>
            </select>
        </div>
        
        <div class="upload-zone" style="margin-bottom: 16px;">
            <div class="upload-area" onclick="document.getElementById('mediaFiles').click()" id="mediaUploadArea">
                <div class="upload-placeholder">
                    <span class="upload-icon">⬆️</span>
                    <p><a href="javascript:void(0)" class="text-primary">Click to upload</a> or drag and drop images</p>
                    <p style="font-size:11px;color:#aaa;margin-top:4px">PNG, JPG, SVG, WebP — Multiple files allowed</p>
                </div>
            </div>
            <input type="file" name="media_files[]" id="mediaFiles" multiple accept="image/*,image/svg+xml,.svg" class="file-input-hidden">
        </div>
        
        <div id="mediaPreviewList" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;"></div>
        
        <button type="submit" class="btn-primary">⬆️ Upload Files</button>
    </form>
</div>

<!-- Uploaded Files Grid -->
<div class="admin-section">
    <h3>Uploaded Files</h3>
    <p class="text-muted text-sm">Click on any image to copy its URL.</p>
</div>

<?php if (empty($mediaFiles)): ?>
<div class="empty-state"><p>No uploaded files yet. Upload your first image above.</p></div>
<?php else: ?>
<div class="media-grid">
    <?php foreach ($mediaFiles as $file): 
        $publicUrl = SUPABASE_URL . '/storage/v1/object/public/media/' . $file['name'];
    ?>
    <div class="media-card" onclick="copyUrl('<?= e($publicUrl) ?>', this)">
        <div class="media-thumb">
            <img src="<?= e($publicUrl) ?>" alt="<?= e(basename($file['name'])) ?>" loading="lazy">
        </div>
        <div class="media-info">
            <p class="media-name"><?= e(basename($file['name'])) ?></p>
            <p class="media-meta"><?= e(dirname($file['name'])) ?></p>
        </div>
        <span class="copy-badge">📋 Copy URL</span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-top:12px}
.media-card{background:#fff;border:1px solid #e8e8e8;border-radius:8px;overflow:hidden;cursor:pointer;transition:box-shadow .15s,transform .1s;position:relative}
.media-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.1);transform:translateY(-1px)}
.media-card:active{transform:scale(.98)}
.media-thumb{aspect-ratio:1;display:grid;place-items:center;background:#fafafa;padding:8px;overflow:hidden}
.media-thumb img{max-width:100%;max-height:100%;object-fit:contain}
.media-info{padding:8px 10px}
.media-name{font-size:11px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.media-meta{font-size:10px;color:#878787;margin-top:2px}
.copy-badge{position:absolute;top:6px;right:6px;background:rgba(0,0,0,.7);color:#fff;font-size:10px;padding:3px 8px;border-radius:4px;opacity:0;transition:opacity .15s}
.media-card:hover .copy-badge{opacity:1}
.copied-toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#212121;color:#fff;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:500;z-index:9999;animation:fadeInUp .3s ease}
@keyframes fadeInUp{from{opacity:0;transform:translate(-50%,10px)}to{opacity:1;transform:translate(-50%,0)}}
</style>

<script>
// Drag and drop
var area = document.getElementById('mediaUploadArea');
var input = document.getElementById('mediaFiles');
var previewList = document.getElementById('mediaPreviewList');

area.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
area.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('drag-over'); });
area.addEventListener('drop', function(e) {
    e.preventDefault(); this.classList.remove('drag-over');
    input.files = e.dataTransfer.files;
    showPreviews(e.dataTransfer.files);
});
input.addEventListener('change', function() { showPreviews(this.files); });

function showPreviews(files) {
    previewList.innerHTML = '';
    for (var i = 0; i < files.length; i++) {
        (function(file) {
            var reader = new FileReader();
            reader.onload = function(ev) {
                var div = document.createElement('div');
                div.style.cssText = 'width:80px;height:80px;border-radius:6px;overflow:hidden;border:1px solid #eee';
                div.innerHTML = '<img src="' + ev.target.result + '" style="width:100%;height:100%;object-fit:cover">';
                previewList.appendChild(div);
            };
            reader.readAsDataURL(file);
        })(files[i]);
    }
}

function copyUrl(url, el) {
    navigator.clipboard.writeText(url).then(function() {
        var toast = document.createElement('div');
        toast.className = 'copied-toast';
        toast.textContent = '✓ URL copied!';
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 2000);
    });
}
</script>

<?php require __DIR__ . '/layout-end.php'; ?>
