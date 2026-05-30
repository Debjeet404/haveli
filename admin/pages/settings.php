<?php
$adminTitle = 'Website Settings';
require_once '../includes/header.php';
$pdo = getDB();

$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group = sanitize($_POST['group'] ?? 'general');
    $saved = 0;
    foreach ($_POST as $key => $value) {
        if (in_array($key, ['action','group','csrf'])) continue;
        $key = sanitize($key);
        if (is_array($value)) continue;
        $value = trim($value);
        $check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key=?");
        $check->execute([$key]);
        if ($check->fetchColumn()) {
            $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")->execute([$value,$key]);
        } else {
            $pdo->prepare("INSERT INTO settings (setting_key,setting_value,setting_group) VALUES (?,?,?)")->execute([$key,$value,$group]);
        }
        $saved++;
    }
    // Handle logo upload
    foreach (['logo','favicon'] as $f) {
        if (!empty($_FILES[$f]['name'])) {
            $up = uploadFile($_FILES[$f], 'logos');
            if (isset($up['success'])) {
                $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")->execute([$up['path'], $f.'_path']);
            }
        }
    }
    $msg = "Settings saved ($saved fields updated)."; $msgType = 'success';
}

// Load all settings into array
$allSettings = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $r) $allSettings[$r['setting_key']] = $r['setting_value'];
function s($key, $default='') {
    global $allSettings;
    return $allSettings[$key] ?? $default;
}

$activeTab = sanitize($_GET['tab'] ?? 'general');
?>

<div class="page-header">
  <div>
    <div class="page-header-title">⚙️ Website Settings</div>
    <div class="page-header-sub">Control every aspect of your website from here</div>
  </div>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?>"><?= $msgType==='success'?'✅':'❌' ?> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Tab Navigation -->
<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:20px">
  <?php
  $tabs = [
    ['general','⚙️ General'],['homepage','🏠 Homepage'],['orders','🛒 Orders'],
    ['social','📱 Social Media'],['theme','🎨 Theme'],['seo','🔍 SEO'],
  ];
  foreach ($tabs as [$t,$l]):
  ?>
  <a href="?tab=<?= $t ?>" class="btn btn-sm <?= $activeTab===$t?'btn-primary':'btn-ghost' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="group" value="<?= $activeTab ?>">

  <!-- GENERAL TAB -->
  <?php if ($activeTab === 'general'): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">⚙️ General Settings</span></div>
    <div class="card-body">
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Website Name</label>
          <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars(s('site_name','Haveli')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Tagline</label>
          <input type="text" name="site_tagline" class="form-control" value="<?= htmlspecialchars(s('site_tagline')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Contact Email</label>
          <input type="email" name="site_email" class="form-control" value="<?= htmlspecialchars(s('site_email')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Primary Phone</label>
          <input type="text" name="site_phone" class="form-control" value="<?= htmlspecialchars(s('site_phone')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Secondary Phone</label>
          <input type="text" name="site_phone2" class="form-control" value="<?= htmlspecialchars(s('site_phone2')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Currency Symbol</label>
          <input type="text" name="site_currency" class="form-control" value="<?= htmlspecialchars(s('site_currency','₨')) ?>" placeholder="₨ $ £ €">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Full Address</label>
          <input type="text" name="site_address" class="form-control" value="<?= htmlspecialchars(s('site_address')) ?>">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Restaurant Description</label>
          <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars(s('site_description')) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Restaurant Hours</label>
          <input type="text" name="restaurant_hours" class="form-control" value="<?= htmlspecialchars(s('restaurant_hours')) ?>" placeholder="Mon-Sun: 12PM - 12AM">
        </div>
        <div class="form-group">
          <label class="form-label">Google Maps URL</label>
          <input type="url" name="maps_url" class="form-control" value="<?= htmlspecialchars(s('maps_url')) ?>" placeholder="https://maps.google.com/...">
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:8px">
        <div>
          <label class="form-label">Logo Image</label>
          <div class="upload-box" onclick="this.querySelector('input').click()">
            <input type="file" name="logo" accept="image/*">
            <?php if (s('logo_path')): ?>
            <img src="<?= BASE_URL.'/'.s('logo_path') ?>" class="upload-preview" alt="Logo">
            <?php else: ?><p style="color:var(--text-3);font-size:.82rem">🖼️ Upload Logo</p><?php endif; ?>
          </div>
        </div>
        <div>
          <label class="form-label">Favicon</label>
          <div class="upload-box" onclick="this.querySelector('input').click()">
            <input type="file" name="favicon" accept="image/*">
            <?php if (s('favicon_path')): ?>
            <img src="<?= BASE_URL.'/'.s('favicon_path') ?>" class="upload-preview" style="width:32px;height:32px" alt="Favicon">
            <?php else: ?><p style="color:var(--text-3);font-size:.82rem">🔖 Upload Favicon</p><?php endif; ?>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
        <div class="form-group">
          <label class="form-label">Announcement Text</label>
          <input type="text" name="announcement_text" class="form-control" value="<?= htmlspecialchars(s('announcement_text')) ?>" placeholder="🎉 Special offer text...">
        </div>
        <div class="form-group">
          <label class="form-label">Announcement Active</label>
          <select name="announcement_active" class="form-control">
            <option value="1" <?= s('announcement_active')=='1'?'selected':'' ?>>Yes - Show</option>
            <option value="0" <?= s('announcement_active')!='1'?'selected':'' ?>>No - Hide</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Popup Offer Title</label>
          <input type="text" name="popup_title" class="form-control" value="<?= htmlspecialchars(s('popup_title')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Popup Offer Text</label>
          <input type="text" name="popup_text" class="form-control" value="<?= htmlspecialchars(s('popup_text')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Show Popup</label>
          <select name="popup_active" class="form-control">
            <option value="1" <?= s('popup_active')=='1'?'selected':'' ?>>Yes</option>
            <option value="0" <?= s('popup_active')!='1'?'selected':'' ?>>No</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Default Theme</label>
          <select name="dark_mode_default" class="form-control">
            <option value="1" <?= s('dark_mode_default')=='1'?'selected':'' ?>>🌙 Dark Mode</option>
            <option value="0" <?= s('dark_mode_default')!='1'?'selected':'' ?>>☀️ Light Mode</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- HOMEPAGE TAB -->
  <?php elseif ($activeTab === 'homepage'): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">🏠 Homepage Settings</span></div>
    <div class="card-body">
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Hero Title (Line 1)</label>
          <input type="text" name="hero_title" class="form-control" value="<?= htmlspecialchars(s('hero_title')) ?>" placeholder="Where Royalty Meets">
        </div>
        <div class="form-group">
          <label class="form-label">Hero Title Highlight (Line 2 — gradient text)</label>
          <input type="text" name="hero_title_highlight" class="form-control" value="<?= htmlspecialchars(s('hero_title_highlight')) ?>" placeholder="Flavor">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Hero Subtitle</label>
          <textarea name="hero_subtitle" class="form-control" rows="2"><?= htmlspecialchars(s('hero_subtitle')) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Primary CTA Button Text</label>
          <input type="text" name="hero_cta_primary" class="form-control" value="<?= htmlspecialchars(s('hero_cta_primary','Explore Menu')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Secondary CTA Button Text</label>
          <input type="text" name="hero_cta_secondary" class="form-control" value="<?= htmlspecialchars(s('hero_cta_secondary','Our Story')) ?>">
        </div>
      </div>
      <div class="form-row cols-2" style="margin-top:8px">
        <div class="form-group">
          <label class="form-label">About Section Title</label>
          <input type="text" name="about_title" class="form-control" value="<?= htmlspecialchars(s('about_title')) ?>">
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">About Section Text</label>
          <textarea name="about_text" class="form-control" rows="3"><?= htmlspecialchars(s('about_text')) ?></textarea>
        </div>
        <div class="form-group" style="grid-column:1/-1">
          <label class="form-label">Footer Text</label>
          <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars(s('footer_text')) ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ORDERS TAB -->
  <?php elseif ($activeTab === 'orders'): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">🛒 Order & Delivery Settings</span></div>
    <div class="card-body">
      <div class="form-row cols-3">
        <div class="form-group">
          <label class="form-label">Delivery Fee</label>
          <input type="number" name="delivery_fee" class="form-control" value="<?= s('delivery_fee','150') ?>" min="0">
          <div class="form-hint">Charged per order below free delivery threshold</div>
        </div>
        <div class="form-group">
          <label class="form-label">Free Delivery Above</label>
          <input type="number" name="free_delivery_above" class="form-control" value="<?= s('free_delivery_above','2000') ?>" min="0">
          <div class="form-hint">Set to 0 to always charge delivery</div>
        </div>
        <div class="form-group">
          <label class="form-label">Tax Percentage (%)</label>
          <input type="number" name="tax_percentage" class="form-control" value="<?= s('tax_percentage','5') ?>" min="0" max="100" step="0.1">
        </div>
        <div class="form-group">
          <label class="form-label">Minimum Order Amount</label>
          <input type="number" name="min_order_amount" class="form-control" value="<?= s('min_order_amount','500') ?>" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Estimated Delivery Time</label>
          <input type="text" name="estimated_delivery_time" class="form-control" value="<?= htmlspecialchars(s('estimated_delivery_time','30-45')) ?>" placeholder="30-45">
          <div class="form-hint">Shown in minutes (e.g. 30-45)</div>
        </div>
      </div>
    </div>
  </div>

  <!-- SOCIAL TAB -->
  <?php elseif ($activeTab === 'social'): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">📱 Social Media Links</span></div>
    <div class="card-body">
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Facebook URL</label>
          <input type="url" name="facebook_url" class="form-control" value="<?= htmlspecialchars(s('facebook_url')) ?>" placeholder="https://facebook.com/...">
        </div>
        <div class="form-group">
          <label class="form-label">Instagram URL</label>
          <input type="url" name="instagram_url" class="form-control" value="<?= htmlspecialchars(s('instagram_url')) ?>" placeholder="https://instagram.com/...">
        </div>
        <div class="form-group">
          <label class="form-label">Twitter / X URL</label>
          <input type="url" name="twitter_url" class="form-control" value="<?= htmlspecialchars(s('twitter_url')) ?>" placeholder="https://twitter.com/...">
        </div>
        <div class="form-group">
          <label class="form-label">WhatsApp Number (with country code)</label>
          <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars(s('whatsapp_number')) ?>" placeholder="+923001234567">
        </div>
      </div>
    </div>
  </div>

  <!-- THEME TAB -->
  <?php elseif ($activeTab === 'theme'): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">🎨 Color Theme</span></div>
    <div class="card-body">
      <div class="form-row cols-2">
        <div class="form-group">
          <label class="form-label">Primary Color (Orange)</label>
          <div style="display:flex;gap:10px;align-items:center">
            <input type="color" name="primary_color" value="<?= s('primary_color','#FF6B00') ?>" style="width:50px;height:36px;border-radius:6px;border:1px solid var(--glass-border);background:none;cursor:pointer">
            <input type="text" id="primaryHex" class="form-control" value="<?= s('primary_color','#FF6B00') ?>" placeholder="#FF6B00" style="flex:1" oninput="document.querySelector('[name=primary_color]').value=this.value">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Secondary Color (Gold)</label>
          <div style="display:flex;gap:10px;align-items:center">
            <input type="color" name="secondary_color" value="<?= s('secondary_color','#FFD700') ?>" style="width:50px;height:36px;border-radius:6px;border:1px solid var(--glass-border);background:none;cursor:pointer">
            <input type="text" id="secondaryHex" class="form-control" value="<?= s('secondary_color','#FFD700') ?>" placeholder="#FFD700" style="flex:1" oninput="document.querySelector('[name=secondary_color]').value=this.value">
          </div>
        </div>
      </div>
      <div style="background:rgba(255,107,0,.06);border:1px solid rgba(255,107,0,.15);border-radius:var(--radius);padding:16px;margin-top:8px">
        <p style="font-size:.82rem;color:var(--text-3)">💡 Color changes apply to the frontend website. Refresh the website after saving to see the updated colors.</p>
      </div>
    </div>
  </div>

  <!-- SEO TAB -->
  <?php elseif ($activeTab === 'seo'): ?>
  <div class="card">
    <div class="card-header"><span class="card-title">🔍 SEO Settings</span></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Meta Keywords</label>
          <input type="text" name="meta_keywords" class="form-control" value="<?= htmlspecialchars(s('meta_keywords')) ?>" placeholder="haveli restaurant, biryani lahore, mughlai food...">
        </div>
        <div class="form-group">
          <label class="form-label">Google Analytics ID</label>
          <input type="text" name="google_analytics" class="form-control" value="<?= htmlspecialchars(s('google_analytics')) ?>" placeholder="G-XXXXXXXXXX">
          <div class="form-hint">Google Analytics 4 measurement ID</div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div style="margin-top:16px">
    <button type="submit" class="btn btn-primary">💾 Save Settings</button>
    <a href="?tab=<?= $activeTab ?>" class="btn btn-ghost" style="margin-left:8px">Reset</a>
  </div>
</form>

<?php require_once '../includes/footer.php'; ?>
