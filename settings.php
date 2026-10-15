<?php
// ============================================================
// Flowork Agency Software - Settings (admin only)
// Branding, contact, invoice — sab kuch yahan se
// ============================================================
$page_title = 'Settings';
$page_sub = 'Agency branding, contact aur invoice settings';
$active = 'settings';
require_once __DIR__ . '/includes/header.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
  $keys = ['agency_name','tagline','phone','whatsapp','email','address','currency','invoice_prefix','thankyou_note','payment_details'];
  foreach ($keys as $k) { save_setting($k, trim($_POST[$k] ?? '')); }
  $nxt = max(1, (int)($_POST['invoice_next'] ?? 1));
  save_setting('invoice_next', (string)$nxt);

  // Logo upload
  if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $info = @getimagesize($_FILES['logo']['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($allowed[$info[2]])) { flash('Logo sirf JPG, PNG ya WebP ho sakta hai.', 'err'); }
    elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) { flash('Logo 2MB se chhota ho.', 'err'); }
    else {
      if (!is_dir(__DIR__ . '/uploads')) { @mkdir(__DIR__ . '/uploads', 0755, true); }
      $ext = $allowed[$info[2]];
      $target = 'uploads/logo.' . $ext;
      // purana logo delete
      foreach (['jpg','png','webp'] as $e) { @unlink(__DIR__ . '/uploads/logo.' . $e); }
      if (@move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/' . $target)) {
        save_setting('logo_path', $target);
        flash('Logo update ho gaya. 🎨');
      } else { flash('Logo upload nahi ho saka — uploads folder writable hai?', 'err'); }
    }
  } else {
    flash('Settings save ho gayi. ✅');
  }
  redirect('settings.php');
}

if (isset($_GET['remove_logo'])) {
  foreach (['jpg','png','webp'] as $e) { @unlink(__DIR__ . '/uploads/logo.' . $e); }
  save_setting('logo_path', '');
  flash('Logo remove ho gaya.');
  redirect('settings.php');
}

$logo = setting('logo_path', '');
?>

<form method="post" enctype="multipart/form-data" action="settings.php">
<div class="grid-2">
  <div class="card">
    <div class="card-head"><div class="card-title">🎨 Branding</div></div>
    <div class="card-sub">Ye naam aur logo invoice aur sidebar par nazar aayega</div>
    <div class="field" style="margin-bottom:14px"><label>Agency Name</label><input name="agency_name" class="input" value="<?php echo esc(setting('agency_name')); ?>"></div>
    <div class="field" style="margin-bottom:14px"><label>Tagline</label><input name="tagline" class="input" value="<?php echo esc(setting('tagline')); ?>"></div>
    <div class="field"><label>Logo (JPG/PNG/WebP, max 2MB)</label>
      <?php if ($logo && file_exists(__DIR__ . '/' . $logo)): ?>
        <div class="mb"><img src="<?php echo esc($logo); ?>?v=<?php echo time(); ?>" style="max-height:70px;background:#0f1d33;padding:8px;border-radius:10px"> <a href="settings.php?remove_logo=1" class="btn btn-danger btn-sm" data-confirm="Logo remove karein?">Remove</a></div>
      <?php endif; ?>
      <input type="file" name="logo" class="input" accept=".jpg,.jpeg,.png,.webp">
    </div>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">📞 Contact Info</div></div>
    <div class="card-sub">Invoice ke neeche aur thank-you note mein show hoga</div>
    <div class="form-grid">
      <div class="field"><label>Phone</label><input name="phone" class="input" value="<?php echo esc(setting('phone')); ?>"></div>
      <div class="field"><label>WhatsApp</label><input name="whatsapp" class="input" value="<?php echo esc(setting('whatsapp')); ?>"></div>
      <div class="field"><label>Email</label><input name="email" class="input" value="<?php echo esc(setting('email')); ?>"></div>
      <div class="field"><label>Address</label><input name="address" class="input" value="<?php echo esc(setting('address')); ?>"></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head"><div class="card-title">🧾 Invoice Settings</div></div>
  <div class="form-grid">
    <div class="field"><label>Invoice Prefix</label><input name="invoice_prefix" class="input" value="<?php echo esc(setting('invoice_prefix')); ?>" placeholder="INV"></div>
    <div class="field"><label>Next Invoice Number</label><input type="number" name="invoice_next" class="input" min="1" value="<?php echo esc(setting('invoice_next')); ?>"><small class="muted">Agli invoice isi number se banegi</small></div>
    <div class="field"><label>Currency Symbol</label><input name="currency" class="input" value="<?php echo esc(setting('currency')); ?>" placeholder="Rs"></div>
    <div class="field"><label>Thank-you Note</label><input name="thankyou_note" class="input" value="<?php echo esc(setting('thankyou_note')); ?>"></div>
    <div class="field full"><label>Payment Details (invoice par show hoga)</label><textarea name="payment_details" class="input"><?php echo esc(setting('payment_details')); ?></textarea><small class="muted">e.g. Bank: Meezan — Account: 0123-0104567890 · Easypaisa: 0300-0000000</small></div>
  </div>
  <div class="form-actions"><button name="save_settings" class="btn">Save Settings ✅</button></div>
</div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
