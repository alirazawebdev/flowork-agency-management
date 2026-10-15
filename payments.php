<?php
// ============================================================
// Flowork Agency Software - Payments (list + add/delete)
// ============================================================
$page_title = 'Payments';
$page_sub = 'Har received payment ka record';
$active = 'payments';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['del'])) {
  $py = qrow("SELECT * FROM payments WHERE id = ?", [(int)$_GET['del']]);
  if ($py) {
    if ($py['invoice_id']) {
      q("UPDATE invoices SET paid_amount = GREATEST(0, paid_amount - ?) WHERE id = ?", [(float)$py['amount'], (int)$py['invoice_id']]);
      refresh_invoice_status((int)$py['invoice_id']);
    }
    q("DELETE FROM payments WHERE id = ?", [(int)$_GET['del']]);
    flash('Payment delete ho gayi.');
  }
  redirect('payments.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
  $client_id = (int)($_POST['client_id'] ?? 0);
  $amount = (float)($_POST['amount'] ?? 0);
  if ($client_id <= 0 || $amount <= 0) { flash('Client aur amount zaroori hain.', 'err'); }
  else {
    record_payment([
      'client_id' => $client_id,
      'project_id' => (int)($_POST['project_id'] ?? 0) ?: null,
      'invoice_id' => (int)($_POST['invoice_id'] ?? 0) ?: null,
      'amount' => $amount, 'method' => $_POST['method'] ?? 'Cash',
      'payment_date' => $_POST['payment_date'] ?: today(), 'notes' => trim($_POST['notes'] ?? ''),
    ]);
    flash('Payment record ho gayi. 💰');
  }
  redirect('payments.php');
}

$clients = qall("SELECT id, name, company FROM clients ORDER BY company, name");
$projects = qall("SELECT p.id, p.title, p.client_id FROM projects p ORDER BY p.id DESC");
$invoices = qall("SELECT id, invoice_no, client_id, amount, paid_amount FROM invoices WHERE status != 'paid' ORDER BY id DESC");

$month_f = $_GET['month'] ?? '';
$where = ''; $params = [];
if ($month_f !== '' && preg_match('/^\d{4}-\d{2}$/', $month_f)) { $where = "WHERE DATE_FORMAT(py.payment_date,'%Y-%m') = ?"; $params[] = $month_f; }
$payments = qall(
  "SELECT py.*, c.company, c.name AS cname, i.invoice_no
   FROM payments py JOIN clients c ON c.id = py.client_id LEFT JOIN invoices i ON i.id = py.invoice_id
   $where ORDER BY py.payment_date DESC, py.id DESC LIMIT 200", $params
);
$total = array_sum(array_column($payments, 'amount'));
$methods = ['Cash', 'Bank Transfer', 'Easypaisa', 'JazzCash', 'Other'];
?>

<div class="card">
  <div class="card-head"><div class="card-title">＋ Payment Record Karein</div></div>
  <form method="post">
    <div class="form-grid">
      <div class="field"><label>Client *</label>
        <select name="client_id" class="input" required><option value="">— Select —</option>
        <?php foreach ($clients as $c): ?><option value="<?php echo $c['id']; ?>"><?php echo esc($c['company'] ? $c['company'] . ' (' . $c['name'] . ')' : $c['name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Project (optional)</label>
        <select name="project_id" class="input"><option value="">— —</option>
        <?php foreach ($projects as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo esc($p['title']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Invoice (optional)</label>
        <select name="invoice_id" class="input"><option value="">— —</option>
        <?php foreach ($invoices as $i): ?><option value="<?php echo $i['id']; ?>"><?php echo esc($i['invoice_no']); ?> (baaki <?php echo money($i['amount'] - $i['paid_amount']); ?>)</option><?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Amount (Rs) *</label><input type="number" name="amount" class="input" required min="1" step="any"></div>
      <div class="field"><label>Method</label><select name="method" class="input"><?php foreach ($methods as $m): ?><option><?php echo $m; ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Date</label><input type="date" name="payment_date" class="input" value="<?php echo today(); ?>"></div>
      <div class="field full"><label>Note</label><input name="notes" class="input" placeholder="e.g. Advance — Bank transfer"></div>
    </div>
    <div class="form-actions"><button name="add_payment" class="btn btn-sm">Record Payment 💰</button></div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <div class="card-title">Payment History <?php echo $month_f ? '(' . esc($month_f) . ')' : ''; ?> — Total: <?php echo money($total); ?></div>
    <form method="get" class="toolbar no-print">
      <input type="month" name="month" class="input" style="width:auto" value="<?php echo esc($month_f); ?>" onchange="this.form.submit()">
      <?php if ($month_f): ?><a href="payments.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <?php if (!$payments): ?><div class="empty">Koi payment record nahi.</div>
  <?php else: ?>
  <table class="tbl">
    <tr><th>Date</th><th>Client</th><th>Invoice</th><th>Method</th><th>Note</th><th class="r">Amount</th><th class="r"></th></tr>
    <?php foreach ($payments as $py): ?>
    <tr>
      <td><?php echo dmy($py['payment_date']); ?></td>
      <td><b><?php echo esc($py['company'] ?: $py['cname']); ?></b></td>
      <td><?php echo $py['invoice_no'] ? '<a href="invoice-view.php?id=' . $py['invoice_id'] . '">' . esc($py['invoice_no']) . '</a>' : '—'; ?></td>
      <td><?php echo esc($py['method']); ?></td>
      <td><small class="muted"><?php echo esc($py['notes']); ?></small></td>
      <td class="r strong"><?php echo money($py['amount']); ?></td>
      <td class="r"><a href="payments.php?del=<?php echo $py['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Payment delete ho jayegi. Continue?">✕</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
