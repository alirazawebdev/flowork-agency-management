<?php
// ============================================================
// Flowork Agency Software - Clients (list + add/edit/delete)
// ============================================================
$page_title = 'Clients';
$page_sub = 'Tamam clients ka record';
$active = 'clients';
require_once __DIR__ . '/includes/header.php';

// ---------- Delete ----------
if (isset($_GET['del'])) {
  q("DELETE FROM clients WHERE id = ?", [(int)$_GET['del']]);
  flash('Client delete ho gaya.');
  redirect('clients.php');
}

// ---------- Save (add/edit) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_client'])) {
  $id = (int)($_POST['id'] ?? 0);
  $d = [trim($_POST['name']), trim($_POST['company']), trim($_POST['phone']), trim($_POST['whatsapp']), trim($_POST['email']), trim($_POST['address']), trim($_POST['notes'])];
  if ($d[0] === '') { flash('Client ka naam zaroori hai.', 'err'); }
  elseif ($id > 0) {
    q("UPDATE clients SET name=?, company=?, phone=?, whatsapp=?, email=?, address=?, notes=? WHERE id=?", [...$d, $id]);
    flash('Client update ho gaya.');
  } else {
    $id = qins("INSERT INTO clients (name, company, phone, whatsapp, email, address, notes) VALUES (?,?,?,?,?,?,?)", $d);
    flash('Naya client add ho gaya. 🎉');
  }
  redirect('clients.php');
}

$edit = null;
if (isset($_GET['edit'])) { $edit = qrow("SELECT * FROM clients WHERE id = ?", [(int)$_GET['edit']]); }

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
  $like = '%' . $search . '%';
  $clients = qall("SELECT * FROM clients WHERE name LIKE ? OR company LIKE ? OR phone LIKE ? ORDER BY id DESC", [$like, $like, $like]);
} else {
  $clients = qall("SELECT * FROM clients ORDER BY id DESC");
}
?>

<div class="card">
  <div class="card-head">
    <div class="card-title"><?php echo $edit ? 'Edit Client' : '＋ Naya Client'; ?></div>
  </div>
  <form method="post" action="clients.php">
    <input type="hidden" name="id" value="<?php echo $edit ? (int)$edit['id'] : 0; ?>">
    <div class="form-grid">
      <div class="field"><label>Client ka naam *</label><input name="name" class="input" required value="<?php echo esc($edit['name'] ?? ''); ?>" placeholder="e.g. Ahmed Raza"></div>
      <div class="field"><label>Company / Business</label><input name="company" class="input" value="<?php echo esc($edit['company'] ?? ''); ?>" placeholder="e.g. Ahmed Traders"></div>
      <div class="field"><label>Phone</label><input name="phone" class="input" value="<?php echo esc($edit['phone'] ?? ''); ?>" placeholder="03xx-xxxxxxx"></div>
      <div class="field"><label>WhatsApp</label><input name="whatsapp" class="input" value="<?php echo esc($edit['whatsapp'] ?? ''); ?>" placeholder="03xx-xxxxxxx"></div>
      <div class="field"><label>Email</label><input type="email" name="email" class="input" value="<?php echo esc($edit['email'] ?? ''); ?>"></div>
      <div class="field"><label>Address</label><input name="address" class="input" value="<?php echo esc($edit['address'] ?? ''); ?>"></div>
      <div class="field full"><label>Notes</label><textarea name="notes" class="input" style="min-height:60px"><?php echo esc($edit['notes'] ?? ''); ?></textarea></div>
    </div>
    <div class="form-actions">
      <button type="submit" name="save_client" class="btn"><?php echo $edit ? 'Update Client' : 'Add Client'; ?></button>
      <?php if ($edit): ?><a href="clients.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><div class="card-title">Client List (<?php echo count($clients); ?>)</div></div>
  <form method="get" class="searchbar no-print">
    <input name="q" class="input" placeholder="🔍 Naam, company ya phone se search…" value="<?php echo esc($search); ?>">
    <button class="btn btn-ghost">Search</button>
    <?php if ($search !== ''): ?><a href="clients.php" class="btn btn-ghost">Clear</a><?php endif; ?>
  </form>
  <?php if (!$clients): ?>
    <div class="empty">Koi client nahi mila. Upar form se pehla client add karein.</div>
  <?php else: ?>
  <table class="tbl">
    <tr><th>Client</th><th>Contact</th><th class="c">Projects</th><th class="r">Total Business</th><th class="r">Remaining</th><th class="r">Actions</th></tr>
    <?php foreach ($clients as $c):
      $s = client_stats($c['id']);
    ?>
    <tr>
      <td><a href="client-view.php?id=<?php echo $c['id']; ?>"><b><?php echo esc($c['name']); ?></b></a><br><small class="muted"><?php echo esc($c['company']); ?></small></td>
      <td><small><?php echo esc($c['phone']); ?><br><?php echo esc($c['email']); ?></small></td>
      <td class="c"><?php echo $s['projects']; ?></td>
      <td class="r"><?php echo money($s['business']); ?></td>
      <td class="r" style="color:<?php echo $s['remaining'] > 0 ? 'var(--warn)' : 'var(--muted)'; ?>"><?php echo money($s['remaining']); ?></td>
      <td class="r"><a href="client-view.php?id=<?php echo $c['id']; ?>" class="btn btn-ghost btn-sm">Open</a>
      <a href="clients.php?edit=<?php echo $c['id']; ?>" class="btn btn-ghost btn-sm">Edit</a>
      <a href="clients.php?del=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Ye client aur uska tamam record delete ho jayega. Continue?">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
