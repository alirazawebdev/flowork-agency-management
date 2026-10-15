<?php
// ============================================================
// Flowork Agency Software - Client profile
// ============================================================
if (!defined('FW_APP')) { define('FW_APP', true); }
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$client = qrow("SELECT * FROM clients WHERE id = ?", [$id]);
if (!$client) { die('Client nahi mila.'); }

$page_title = $client['company'] ?: $client['name'];
$page_sub = 'Client profile — projects, payments aur hisaab';
$active = 'clients';
require_once __DIR__ . '/includes/header.php';

$s = client_stats($id);
$projects = qall("SELECT * FROM projects WHERE client_id = ? ORDER BY id DESC", [$id]);
$invoices = qall("SELECT * FROM invoices WHERE client_id = ? ORDER BY id DESC LIMIT 10", [$id]);
$payments = qall("SELECT py.*, i.invoice_no FROM payments py LEFT JOIN invoices i ON i.id = py.invoice_id WHERE py.client_id = ? ORDER BY py.payment_date DESC LIMIT 10", [$id]);
?>

<div class="kpis" style="grid-template-columns:repeat(4,1fr)">
  <div class="kpi"><div class="kpi-icon ic-indigo">📁</div><div><div class="kpi-label">Projects</div><div class="kpi-value"><?php echo $s['projects']; ?></div></div></div>
  <div class="kpi"><div class="kpi-icon ic-green">💼</div><div><div class="kpi-label">Total Business</div><div class="kpi-value"><?php echo money($s['business']); ?></div></div></div>
  <div class="kpi"><div class="kpi-icon ic-sky">✅</div><div><div class="kpi-label">Collected</div><div class="kpi-value"><?php echo money($s['collected']); ?></div></div></div>
  <div class="kpi"><div class="kpi-icon ic-amber">⏳</div><div><div class="kpi-label">Remaining</div><div class="kpi-value"><?php echo money($s['remaining']); ?></div></div></div>
</div>

<div class="card">
  <div class="card-head"><div class="card-title">Client Details</div><a href="clients.php?edit=<?php echo $id; ?>" class="btn btn-ghost btn-sm">Edit</a></div>
  <div class="form-grid">
    <div><span class="muted small">Naam</span><br><b><?php echo esc($client['name']); ?></b></div>
    <div><span class="muted small">Company</span><br><b><?php echo esc($client['company'] ?: '—'); ?></b></div>
    <div><span class="muted small">Phone</span><br><b><?php echo esc($client['phone'] ?: '—'); ?></b></div>
    <div><span class="muted small">WhatsApp</span><br><b><?php echo esc($client['whatsapp'] ?: '—'); ?></b></div>
    <div><span class="muted small">Email</span><br><b><?php echo esc($client['email'] ?: '—'); ?></b></div>
    <div><span class="muted small">Address</span><br><b><?php echo esc($client['address'] ?: '—'); ?></b></div>
  </div>
  <?php if ($client['notes']): ?><div class="mt"><span class="muted small">Notes</span><br><?php echo nl2br(esc($client['notes'])); ?></div><?php endif; ?>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div class="card-title">Projects</div><a href="projects.php?client=<?php echo $id; ?>" class="btn btn-sm">＋ Naya Project</a></div>
    <?php if (!$projects): ?><div class="empty">Is client ka koi project nahi.</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Project</th><th class="r">Total</th><th class="r">Status</th></tr>
      <?php foreach ($projects as $p): ?>
      <tr>
        <td><a href="project-view.php?id=<?php echo $p['id']; ?>"><b><?php echo esc($p['title']); ?></b></a><br><small class="muted"><?php echo esc($p['service']); ?></small></td>
        <td class="r"><?php echo money($p['total_amount']); ?></td>
        <td class="r"><span class="badge <?php echo project_status_class($p['status']); ?>"><?php echo project_status_label($p['status']); ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">Recent Payments</div></div>
    <?php if (!$payments): ?><div class="empty">Koi payment record nahi.</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Date</th><th>Invoice</th><th class="r">Amount</th></tr>
      <?php foreach ($payments as $py): ?>
      <tr><td><?php echo dmy($py['payment_date']); ?></td><td><?php echo esc($py['invoice_no'] ?: '—'); ?></td><td class="r strong"><?php echo money($py['amount']); ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
