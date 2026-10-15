<?php
// ============================================================
// Flowork Agency Software - Create invoice
// Milestone se ya manual — dono tareeqay
// ============================================================
$page_title = 'Nayi Invoice';
$page_sub = 'Milestone se auto-fill ya manual invoice';
$active = 'invoices';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
  $client_id = (int)($_POST['client_id'] ?? 0);
  $amount = (float)($_POST['amount'] ?? 0);
  if ($client_id <= 0 || $amount <= 0) { flash('Client aur amount zaroori hain.', 'err'); redirect('invoice-create.php'); }

  $project_id = (int)($_POST['project_id'] ?? 0) ?: null;
  $milestone_id = (int)($_POST['milestone_id'] ?? 0) ?: null;
  if ($milestone_id) {
    $m = qrow("SELECT * FROM milestones WHERE id = ?", [$milestone_id]);
    if ($m) { $project_id = (int)$m['project_id']; }
  }
  $inv_no = next_invoice_no();
  $id = qins(
    "INSERT INTO invoices (invoice_no, project_id, milestone_id, client_id, issue_date, due_date, amount, notes) VALUES (?,?,?,?,?,?,?,?)",
    [$inv_no, $project_id, $milestone_id, $client_id, $_POST['issue_date'] ?: today(), $_POST['due_date'] ?: null, $amount, trim($_POST['notes'])]
  );
  bump_invoice_no();
  flash('Invoice ' . $inv_no . ' ban gayi. 🎉');
  redirect('invoice-view.php?id=' . $id);
}

$clients = qall("SELECT id, name, company FROM clients ORDER BY company, name");
$projects = qall("SELECT p.id, p.title, p.client_id, c.company, c.name AS cname FROM projects p JOIN clients c ON c.id = p.client_id ORDER BY p.id DESC");
// Sirf unpaid milestones jin ki invoice abhi nahi bani
$milestones = qall(
  "SELECT m.id, m.title, m.amount, m.project_id, p.client_id, p.title AS ptitle
   FROM milestones m JOIN projects p ON p.id = m.project_id
   WHERE m.status = 'unpaid' AND NOT EXISTS (SELECT 1 FROM invoices i WHERE i.milestone_id = m.id)
   ORDER BY m.id DESC"
);

$pre_ms = (int)($_GET['milestone'] ?? 0);
$pre_proj = (int)($_GET['project'] ?? 0);
$pre_client = 0;
if ($pre_ms) { $mm = qrow("SELECT p.client_id FROM milestones m JOIN projects p ON p.id = m.project_id WHERE m.id = ?", [$pre_ms]); if ($mm) $pre_client = (int)$mm['client_id']; }
elseif ($pre_proj) { $pp = qrow("SELECT client_id FROM projects WHERE id = ?", [$pre_proj]); if ($pp) $pre_client = (int)$pp['client_id']; }
?>

<div class="card" style="max-width:760px">
  <div class="card-head"><div class="card-title">🧾 Invoice Details</div></div>
  <div class="card-sub">Invoice number khud generate hoga: <b><?php echo esc(next_invoice_no()); ?></b></div>
  <form method="post" action="invoice-create.php">
    <div class="form-grid">
      <div class="field"><label>Client *</label>
        <select name="client_id" id="f_client" class="input" required>
          <option value="">— Select karein —</option>
          <?php foreach ($clients as $c): ?>
          <option value="<?php echo $c['id']; ?>" <?php echo $pre_client === (int)$c['id'] ? 'selected' : ''; ?>><?php echo esc($c['company'] ? $c['company'] . ' (' . $c['name'] . ')' : $c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Project (optional)</label>
        <select name="project_id" id="f_project" class="input">
          <option value="">— —</option>
          <?php foreach ($projects as $p): ?>
          <option value="<?php echo $p['id']; ?>" data-client="<?php echo $p['client_id']; ?>" <?php echo $pre_proj === (int)$p['id'] ? 'selected' : ''; ?>><?php echo esc($p['title']); ?> (<?php echo esc($p['company'] ?: $p['cname']); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field full"><label>Milestone (optional — select karne par amount khud fill ho jayegi)</label>
        <select name="milestone_id" id="f_milestone" class="input">
          <option value="">— Manual invoice —</option>
          <?php foreach ($milestones as $m): ?>
          <option value="<?php echo $m['id']; ?>" data-amount="<?php echo $m['amount']; ?>" <?php echo $pre_ms === (int)$m['id'] ? 'selected' : ''; ?>><?php echo esc($m['title']); ?> — <?php echo esc($m['ptitle']); ?> (<?php echo money($m['amount']); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Amount (Rs) *</label><input type="number" name="amount" id="f_amount" class="input" required min="1" step="any"></div>
      <div class="field"><label>Service / Description note</label><input name="notes" class="input" placeholder="e.g. Website development — Stage 2"></div>
      <div class="field"><label>Issue Date</label><input type="date" name="issue_date" class="input" value="<?php echo today(); ?>"></div>
      <div class="field"><label>Due Date</label><input type="date" name="due_date" class="input" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"></div>
    </div>
    <div class="form-actions">
      <button type="submit" name="create_invoice" class="btn">Generate Invoice 🧾</button>
      <a href="invoices.php" class="btn btn-ghost">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
