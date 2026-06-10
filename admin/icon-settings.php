<?php
/**
 * Admin Icon Settings
 * Assign uploaded images to payment page icons and header tab icons
 */

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['icon_action'] ?? '';
    
    if ($action === 'save_icons') {
        $iconKeys = [
            // Payment icons
            'pay_recommended', 'pay_credit_card', 'pay_cod', 'pay_gift_card', 'pay_upi', 'pay_emi',
            // Header tab icons
            'tab_flipkart', 'tab_minutes', 'tab_travel',
            // UI icons
            'ui_coin', 'ui_lock',
        ];
        
        $saved = 0;
        foreach ($iconKeys as $key) {
            $value = trim($_POST[$key] ?? '');
            if ($value !== '') {
                upsert_app_setting('icon_' . $key, $value);
                $saved++;
            }
            // Don't clear existing settings if field is left empty
        }
        
        flash('success', "Icon settings saved! ({$saved} icons configured)");
        redirect('/admin/icon-settings');
    }
}

// Load current icon settings
$icons = get_icon_settings();

// Get media files for the picker
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
            <h2>Icon Settings</h2>
            <p class="text-muted">Assign uploaded images to replace emoji placeholders on your store. Upload images in <a href="/admin/media" style="color:#2874f0;">Media Manager</a> first, then select them here.</p>
        </div>
    </div>
</div>

<form method="POST" action="/admin/icon-settings" id="iconForm">
    <input type="hidden" name="icon_action" value="save_icons">

    <!-- Payment Page Icons -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h3 style="margin-bottom: 4px;">Payment Page Icons</h3>
        <p class="text-muted text-sm" style="margin-bottom: 20px;">These icons appear on the checkout payment step next to each payment method.</p>
        
        <div class="icon-grid">
            <?php
            $paymentIcons = [
                'pay_recommended' => ['label' => 'Recommended', 'emoji' => '💳', 'desc' => 'Shown next to "Recommended for You"'],
                'pay_credit_card' => ['label' => 'Credit/Debit Card', 'emoji' => '💳', 'desc' => 'Shown next to "Credit / Debit / ATM Card"'],
                'pay_cod' => ['label' => 'Cash on Delivery', 'emoji' => '📦', 'desc' => 'Shown next to "Cash on Delivery"'],
                'pay_gift_card' => ['label' => 'Gift Card', 'emoji' => '🎁', 'desc' => 'Shown next to "Have a Gift Card?"'],
                'pay_upi' => ['label' => 'UPI', 'emoji' => '📱', 'desc' => 'Shown next to "UPI" payment option'],
                'pay_emi' => ['label' => 'EMI', 'emoji' => '📅', 'desc' => 'Shown next to "EMI" payment option'],
            ];
            foreach ($paymentIcons as $key => $info):
                $currentUrl = $icons[$key] ?? '';
            ?>
            <div class="icon-item">
                <div class="icon-preview-wrap">
                    <?php if ($currentUrl): ?>
                    <img src="<?= e($currentUrl) ?>" class="icon-preview-img" id="preview_<?= $key ?>">
                    <?php else: ?>
                    <span class="icon-preview-emoji" id="preview_<?= $key ?>"><?= $info['emoji'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="icon-details">
                    <p class="icon-label"><?= e($info['label']) ?></p>
                    <p class="icon-desc"><?= e($info['desc']) ?></p>
                    <div class="icon-input-row">
                        <input type="text" name="<?= $key ?>" value="<?= e($currentUrl) ?>" placeholder="Paste image URL or select from media" class="icon-url-input" id="input_<?= $key ?>" oninput="updatePreview('<?= $key ?>')">
                        <button type="button" class="btn-pick" onclick="openPicker('<?= $key ?>')">Browse</button>
                        <?php if ($currentUrl): ?>
                        <button type="button" class="btn-clear" onclick="clearIcon('<?= $key ?>')">✕</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Header Tab Icons -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h3 style="margin-bottom: 4px;">Header Tab Icons</h3>
        <p class="text-muted text-sm" style="margin-bottom: 20px;">These icons appear in the top navigation tabs (Flipkart, Minutes, Travel).</p>
        
        <div class="icon-grid">
            <?php
            $tabIcons = [
                'tab_flipkart' => ['label' => 'Flipkart Tab', 'emoji' => '🛒', 'desc' => 'Icon for the main "Flipkart" tab'],
                'tab_minutes' => ['label' => 'Minutes Tab', 'emoji' => '⚡', 'desc' => 'Icon for the "Minutes" quick delivery tab'],
                'tab_travel' => ['label' => 'Travel Tab', 'emoji' => '✈️', 'desc' => 'Icon for the "Travel" tab'],
            ];
            foreach ($tabIcons as $key => $info):
                $currentUrl = $icons[$key] ?? '';
            ?>
            <div class="icon-item">
                <div class="icon-preview-wrap">
                    <?php if ($currentUrl): ?>
                    <img src="<?= e($currentUrl) ?>" class="icon-preview-img" id="preview_<?= $key ?>">
                    <?php else: ?>
                    <span class="icon-preview-emoji" id="preview_<?= $key ?>"><?= $info['emoji'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="icon-details">
                    <p class="icon-label"><?= e($info['label']) ?></p>
                    <p class="icon-desc"><?= e($info['desc']) ?></p>
                    <div class="icon-input-row">
                        <input type="text" name="<?= $key ?>" value="<?= e($currentUrl) ?>" placeholder="Paste image URL or select from media" class="icon-url-input" id="input_<?= $key ?>" oninput="updatePreview('<?= $key ?>')">
                        <button type="button" class="btn-pick" onclick="openPicker('<?= $key ?>')">Browse</button>
                        <?php if ($currentUrl): ?>
                        <button type="button" class="btn-clear" onclick="clearIcon('<?= $key ?>')">✕</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- UI Icons -->
    <div class="admin-card" style="margin-bottom: 24px;">
        <h3 style="margin-bottom: 4px;">UI Icons</h3>
        <p class="text-muted text-sm" style="margin-bottom: 20px;">Custom icons used across the store (header coin, checkout lock, etc.).</p>
        
        <div class="icon-grid">
            <?php
            $uiIcons = [
                'ui_coin' => ['label' => 'SuperCoin', 'emoji' => '₹', 'desc' => 'Coin icon shown in the header (next to balance)'],
                'ui_lock' => ['label' => 'Secure Lock', 'emoji' => '🔒', 'desc' => 'Lock icon shown on checkout page "100% Secure"'],
            ];
            foreach ($uiIcons as $key => $info):
                $currentUrl = $icons[$key] ?? '';
            ?>
            <div class="icon-item">
                <div class="icon-preview-wrap">
                    <?php if ($currentUrl): ?>
                    <img src="<?= e($currentUrl) ?>" class="icon-preview-img" id="preview_<?= $key ?>">
                    <?php else: ?>
                    <span class="icon-preview-emoji" id="preview_<?= $key ?>"><?= $info['emoji'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="icon-details">
                    <p class="icon-label"><?= e($info['label']) ?></p>
                    <p class="icon-desc"><?= e($info['desc']) ?></p>
                    <div class="icon-input-row">
                        <input type="text" name="<?= $key ?>" value="<?= e($currentUrl) ?>" placeholder="Paste image URL or select from media" class="icon-url-input" id="input_<?= $key ?>" oninput="updatePreview('<?= $key ?>')">
                        <button type="button" class="btn-pick" onclick="openPicker('<?= $key ?>')">Browse</button>
                        <?php if ($currentUrl): ?>
                        <button type="button" class="btn-clear" onclick="clearIcon('<?= $key ?>')">✕</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <button type="submit" class="btn-primary" style="padding: 12px 32px; font-size: 15px;">💾 Save All Icon Settings</button>
</form>

<!-- Media Picker Modal -->
<div class="icon-picker-modal" id="pickerModal">
    <div class="picker-overlay" onclick="closePicker()"></div>
    <div class="picker-content">
        <div class="picker-header">
            <h3>Select Image</h3>
            <button type="button" onclick="closePicker()" class="picker-close">✕</button>
        </div>
        <div class="picker-body">
            <?php if (empty($mediaFiles)): ?>
            <p class="text-muted" style="text-align:center;padding:40px 20px;">No images uploaded yet.<br><a href="/admin/media" style="color:#2874f0">Go to Media Manager</a> to upload images first.</p>
            <?php else: ?>
            <div class="picker-grid">
                <?php foreach ($mediaFiles as $file): 
                    $publicUrl = SUPABASE_URL . '/storage/v1/object/public/media/' . $file['name'];
                ?>
                <div class="picker-item" onclick="selectImage('<?= e($publicUrl) ?>')">
                    <img src="<?= e($publicUrl) ?>" alt="<?= e(basename($file['name'])) ?>" loading="lazy">
                    <p><?= e(basename($file['name'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.icon-grid{display:flex;flex-direction:column;gap:16px}
.icon-item{display:flex;align-items:flex-start;gap:16px;padding:16px;background:#fafafa;border-radius:10px;border:1px solid #eee}
.icon-preview-wrap{width:56px;height:56px;border-radius:10px;background:#fff;border:2px solid #e8e8e8;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
.icon-preview-img{width:100%;height:100%;object-fit:contain;padding:4px}
.icon-preview-emoji{font-size:28px}
.icon-details{flex:1;min-width:0}
.icon-label{font-size:14px;font-weight:600;color:#212121;margin-bottom:2px}
.icon-desc{font-size:12px;color:#878787;margin-bottom:10px}
.icon-input-row{display:flex;gap:8px;align-items:center}
.icon-url-input{flex:1;padding:9px 12px;border:1px solid #d0d0d0;border-radius:6px;font-size:13px;outline:none;min-width:0}
.icon-url-input:focus{border-color:#2874f0}
.btn-pick{padding:9px 14px;background:#2874f0;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:500;cursor:pointer;white-space:nowrap}
.btn-pick:hover{background:#1a5dc8}
.btn-clear{width:32px;height:32px;background:#ff4444;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.btn-clear:hover{background:#cc0000}

/* Picker Modal */
.icon-picker-modal{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center}
.icon-picker-modal.open{display:flex}
.picker-overlay{position:absolute;inset:0;background:rgba(0,0,0,.5)}
.picker-content{position:relative;background:#fff;border-radius:12px;width:90%;max-width:600px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden}
.picker-header{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #eee}
.picker-header h3{font-size:16px;font-weight:600}
.picker-close{width:32px;height:32px;background:#f0f0f0;border:none;border-radius:6px;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.picker-body{overflow-y:auto;padding:16px 20px}
.picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:12px}
.picker-item{cursor:pointer;border:2px solid #eee;border-radius:8px;overflow:hidden;transition:border-color .15s,transform .1s}
.picker-item:hover{border-color:#2874f0;transform:scale(1.02)}
.picker-item img{width:100%;aspect-ratio:1;object-fit:contain;background:#fafafa;padding:6px}
.picker-item p{font-size:10px;padding:4px 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:center;color:#666}
</style>

<script>
var activePickerKey = null;

function openPicker(key) {
    activePickerKey = key;
    document.getElementById('pickerModal').classList.add('open');
}

function closePicker() {
    document.getElementById('pickerModal').classList.remove('open');
    activePickerKey = null;
}

function selectImage(url) {
    if (!activePickerKey) return;
    document.getElementById('input_' + activePickerKey).value = url;
    updatePreview(activePickerKey);
    closePicker();
}

function updatePreview(key) {
    var input = document.getElementById('input_' + key);
    var preview = document.getElementById('preview_' + key);
    var url = input.value.trim();
    
    if (url) {
        if (preview.tagName === 'IMG') {
            preview.src = url;
        } else {
            // Replace emoji span with img
            var img = document.createElement('img');
            img.src = url;
            img.className = 'icon-preview-img';
            img.id = 'preview_' + key;
            preview.parentNode.replaceChild(img, preview);
        }
    }
}

function clearIcon(key) {
    document.getElementById('input_' + key).value = '';
    var preview = document.getElementById('preview_' + key);
    if (preview.tagName === 'IMG') {
        var span = document.createElement('span');
        span.className = 'icon-preview-emoji';
        span.id = 'preview_' + key;
        span.textContent = '—';
        preview.parentNode.replaceChild(span, preview);
    }
}
</script>

<?php require __DIR__ . '/layout-end.php'; ?>
