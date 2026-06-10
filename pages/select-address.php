<?php
/**
 * Select Delivery Address Page
 * Shows saved addresses, allows selecting/adding new ones
 */

$tenant = $_SESSION['current_tenant'] ?? null;
$homeLink = $tenant ? "/t/{$tenant['slug']}" : '/';
$theme = get_theme();
$siteName = $theme['site_name'] ?? DEFAULT_SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title>Select Delivery Address — <?= e($siteName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f1f3f6;color:#212121;-webkit-font-smoothing:antialiased;-webkit-overflow-scrolling:touch;overflow-x:hidden}
html{overflow-x:hidden}
a{text-decoration:none;color:inherit}
button,input{font:inherit;border:none;outline:none}
button{cursor:pointer;background:none}

.page-header{position:sticky;top:0;z-index:50;background:#fff;padding:14px 16px;display:flex;align-items:center;gap:12px;border-bottom:1px solid #eee;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.page-header h1{font-size:16px;font-weight:600;flex:1}
.page-header .close-btn{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:18px}

.search-box{padding:12px 16px;background:#fff;border-bottom:1px solid #eee}
.search-box input{width:100%;padding:11px 16px 11px 40px;border:1px solid #e0e0e0;border-radius:8px;font-size:14px;background:#f8f8f8}
.search-box input:focus{border-color:#2874f0;background:#fff}
.search-box{position:relative}
.search-box svg{position:absolute;left:28px;top:50%;transform:translateY(-50%);color:#878787}

.detect-btn{display:flex;align-items:center;gap:10px;width:100%;padding:14px 16px;background:#fff;border-bottom:1px dashed #e0e0e0;font-size:14px;font-weight:500;color:#2874f0}
.detect-btn svg{flex-shrink:0}

.saved-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:#fff;border-bottom:1px solid #eee}
.saved-header h3{font-size:13px;font-weight:600;color:#212121}
.saved-header .add-btn{font-size:13px;font-weight:600;color:#2874f0}

.address-list{background:#fff}
.address-item{display:flex;gap:12px;padding:14px 16px;border-bottom:1px solid #f5f5f5;cursor:pointer;transition:background .1s}
.address-item:hover{background:#f5f9ff}
.address-item.selected{background:#e8f0fe}
.addr-icon{width:24px;height:24px;flex-shrink:0;margin-top:2px;color:#555}
.addr-content{flex:1;min-width:0}
.addr-name{font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
.addr-name .selected-badge{font-size:10px;background:#e8f0fe;color:#2874f0;border:1px solid #2874f0;border-radius:3px;padding:1px 6px;font-weight:600}
.addr-detail{font-size:12px;color:#878787;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.addr-more{width:24px;height:24px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#878787;font-size:16px}

.empty-state{text-align:center;padding:60px 20px;background:#fff}
.empty-state p{font-size:14px;color:#878787;margin-bottom:16px}
.empty-state .add-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 24px;background:#2874f0;color:#fff;border-radius:6px;font-size:14px;font-weight:600}

/* Add address form */
.add-form{display:none;background:#fff;padding:20px 16px;border-bottom:6px solid #f1f3f6}
.add-form.show{display:block}
.add-form h3{font-size:15px;font-weight:600;margin-bottom:16px}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;font-weight:500;color:#212121;margin-bottom:4px}
.form-group input{width:100%;padding:10px 12px;border:1px solid #d0d0d0;border-radius:6px;font-size:14px}
.form-group input:focus{border-color:#2874f0}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.save-btn{width:100%;padding:14px;background:#2874f0;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;margin-top:8px}
.cancel-btn{width:100%;padding:12px;background:#fff;color:#212121;border:1px solid #d0d0d0;border-radius:8px;font-size:14px;margin-top:8px}
</style>
</head>
<body>

<header class="page-header">
    <a href="<?= e($homeLink) ?>" style="padding:4px"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#212121" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
    <h1>Select delivery address</h1>
    <a href="<?= e($homeLink) ?>" class="close-btn">✕</a>
</header>

<div class="search-box">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#878787" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="text" id="searchInput" placeholder="Search by area, street name, pin code" oninput="filterAddresses(this.value)">
</div>

<button class="detect-btn" onclick="detectMyLocation()">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M22 12h-4M6 12H2M12 6V2M12 22v-4"/></svg>
    Use my current location
</button>

<div class="saved-header">
    <h3>Saved addresses</h3>
    <button class="add-btn" onclick="toggleAddForm()">+ Add New</button>
</div>

<!-- Add New Address Form -->
<div class="add-form" id="addForm">
    <h3>Add new address</h3>
    <div class="form-group">
        <label>Full Name *</label>
        <input type="text" id="newName" placeholder="Enter full name">
    </div>
    <div class="form-group">
        <label>Phone *</label>
        <input type="tel" id="newPhone" placeholder="10-digit number" pattern="[0-9]{10}">
    </div>
    <div class="form-group">
        <label>Flat / House No / Building *</label>
        <input type="text" id="newFlat" placeholder="Flat, House no., Building">
    </div>
    <div class="form-group">
        <label>Area / Street *</label>
        <input type="text" id="newArea" placeholder="Area, Street, Sector">
    </div>
    <div class="form-row">
        <div class="form-group">
            <label>City *</label>
            <input type="text" id="newCity" placeholder="City">
        </div>
        <div class="form-group">
            <label>PIN Code *</label>
            <input type="text" id="newPincode" placeholder="6-digit PIN" pattern="[0-9]{6}">
        </div>
    </div>
    <div class="form-group">
        <label>State</label>
        <input type="text" id="newState" placeholder="State">
    </div>
    <button class="save-btn" onclick="saveNewAddress()">Save Address</button>
    <button class="cancel-btn" onclick="toggleAddForm()">Cancel</button>
</div>

<!-- Address List -->
<div class="address-list" id="addressList"></div>

<div class="empty-state" id="emptyState" style="display:none">
    <p>No saved addresses yet</p>
    <button class="add-btn" onclick="toggleAddForm()">+ Add Address</button>
</div>

<script>
var addresses = JSON.parse(localStorage.getItem('saved_addresses') || '[]');
var selectedIdx = parseInt(localStorage.getItem('selected_address_idx') || '0');

function render(filter) {
    var list = document.getElementById('addressList');
    var empty = document.getElementById('emptyState');
    
    var filtered = addresses;
    if (filter) {
        var q = filter.toLowerCase();
        filtered = addresses.filter(function(a) {
            return (a.name + ' ' + a.flat + ' ' + a.area + ' ' + a.city + ' ' + a.pincode + ' ' + a.state).toLowerCase().indexOf(q) !== -1;
        });
    }
    
    if (filtered.length === 0) {
        list.innerHTML = '';
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';
    
    var html = '';
    filtered.forEach(function(a, i) {
        var realIdx = addresses.indexOf(a);
        var isSelected = realIdx === selectedIdx;
        var typeIcon = a.type === 'Work' ? 
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/></svg>' :
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
        
        var detail = [a.flat, a.area, a.city, a.state, a.pincode].filter(Boolean).join(', ');
        
        html += '<div class="address-item ' + (isSelected ? 'selected' : '') + '" onclick="selectAddress(' + realIdx + ')">';
        html += '<span class="addr-icon">' + typeIcon + '</span>';
        html += '<div class="addr-content">';
        html += '<p class="addr-name">' + (a.name || 'Address') + (isSelected ? ' <span class="selected-badge">Selected</span>' : '') + '</p>';
        html += '<p class="addr-detail">' + detail + '</p>';
        html += '</div>';
        html += '<span class="addr-more" onclick="event.stopPropagation();deleteAddress(' + realIdx + ')">✕</span>';
        html += '</div>';
    });
    list.innerHTML = html;
}

function selectAddress(idx) {
    selectedIdx = idx;
    localStorage.setItem('selected_address_idx', idx);
    var a = addresses[idx];
    if (a) {
        var loc = { area: a.area || a.flat, city: a.city, state: a.state, pincode: a.pincode, name: a.name };
        localStorage.setItem('delivery_location', JSON.stringify(loc));
    }
    // Go back to previous page
    if (document.referrer && document.referrer.indexOf(location.host) !== -1) {
        history.back();
    } else {
        window.location.href = '<?= e($homeLink) ?>';
    }
}

function deleteAddress(idx) {
    if (!confirm('Remove this address?')) return;
    addresses.splice(idx, 1);
    localStorage.setItem('saved_addresses', JSON.stringify(addresses));
    if (selectedIdx >= addresses.length) selectedIdx = 0;
    localStorage.setItem('selected_address_idx', selectedIdx);
    render();
}

function toggleAddForm() {
    var form = document.getElementById('addForm');
    form.classList.toggle('show');
}

function saveNewAddress() {
    var name = document.getElementById('newName').value.trim();
    var phone = document.getElementById('newPhone').value.trim();
    var flat = document.getElementById('newFlat').value.trim();
    var area = document.getElementById('newArea').value.trim();
    var city = document.getElementById('newCity').value.trim();
    var pincode = document.getElementById('newPincode').value.trim();
    var state = document.getElementById('newState').value.trim();
    
    if (!name || !flat || !area || !city) {
        alert('Please fill in required fields');
        return;
    }
    
    addresses.push({ name: name, phone: phone, flat: flat, area: area, city: city, pincode: pincode, state: state, type: 'Home' });
    localStorage.setItem('saved_addresses', JSON.stringify(addresses));
    
    // Auto-select the new one
    selectedIdx = addresses.length - 1;
    localStorage.setItem('selected_address_idx', selectedIdx);
    
    // Save as delivery location
    var loc = { area: area, city: city, state: state, pincode: pincode, name: name };
    localStorage.setItem('delivery_location', JSON.stringify(loc));
    
    // Clear form
    document.getElementById('newName').value = '';
    document.getElementById('newPhone').value = '';
    document.getElementById('newFlat').value = '';
    document.getElementById('newArea').value = '';
    document.getElementById('newCity').value = '';
    document.getElementById('newPincode').value = '';
    document.getElementById('newState').value = '';
    toggleAddForm();
    render();
}

function detectMyLocation() {
    var isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
    
    if (isSecure && navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            reverseGeocode(pos.coords.latitude, pos.coords.longitude);
        }, function() {
            // GPS failed — use IP-based location
            ipBasedLocation();
        }, {timeout: 5000, enableHighAccuracy: true});
    } else {
        // Non-HTTPS or no geolocation — go straight to IP detection
        ipBasedLocation();
    }
}

function ipBasedLocation() {
    // Use free IP geolocation API as fallback
    fetch('https://ipapi.co/json/')
    .then(function(r){ return r.json(); })
    .then(function(data) {
        document.getElementById('newArea').value = data.city || '';
        document.getElementById('newCity').value = data.city || '';
        document.getElementById('newState').value = data.region || '';
        document.getElementById('newPincode').value = data.postal || '';
        document.getElementById('addForm').classList.add('show');
    }).catch(function(){
        alert('Could not detect location. Please enter manually.');
        document.getElementById('addForm').classList.add('show');
    });
}

function reverseGeocode(lat, lng) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1')
    .then(function(r){ return r.json(); })
    .then(function(data) {
        var a = data.address || {};
        document.getElementById('newArea').value = [a.suburb, a.neighbourhood, a.road].filter(Boolean).slice(0,2).join(', ');
        document.getElementById('newCity').value = a.city || a.town || a.village || '';
        document.getElementById('newState').value = a.state || '';
        document.getElementById('newPincode').value = a.postcode || '';
        document.getElementById('addForm').classList.add('show');
    }).catch(function(){ ipBasedLocation(); });
}

function filterAddresses(q) { render(q); }

render();
</script>
</body>
</html>
