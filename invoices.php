<?php
// ============================================================
// Flowork Agency Software - Invoices list
// ============================================================
$page_title = 'Invoices';
$page_sub = 'Sab invoices — status ke saath';
$active = 'invoices';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['del'])) {
  $inv = qrow("SELECT * FROM invoices WHERE id = ?", [(int)$_GET['del']]);
  if ($inv) {
    // Linked milestone wapas unpaid
    if ($inv['milestone_id']) { q("UPDATE milestones SET status='unpaid', paid_date=NULL WHERE id=?", [(int)$inv['milestone_id']]); }
    q("DELETE FROM invoices WHERE id = ?", [(int)$_GET['del']]);
    flash('Invoice delete ho gayi.');
  }
  redirect('invoices.php');
}

$st_f = $_GET['status'] ?? '';
$where = ''; $params = [];
if ($st_f !== '') {
  if ($st_f === 'overdue') { $where = "WHERE i.status != 'paid' AND i.due_date IS NOT NULL AND i.due_date < CURDATE()"; }
  else { $where = "WHERE i.status = ?"; $params[] = $st_f; }
}
$invoices = qall(
  "SELECT i.*, c.company, c.name AS cname
   FROM invoices i JOIN clients c ON c.id = i.client_id
   $where ORDER BY i.id DESC", $params
);
?>

<div class="card">
  <div class="card-head">
    <div class="card-title">Invoice List (<?php echo count($invoices); ?>)</div>
    <div class="toolbar no-print">
      <form method="get" style="display:flex;gap:8px">
        <select name="status" class="input" style="width:auto" onchange="this.form.submit()">
          <option value="">All</option>
          <option value="unpaid" <?php echo $st_f === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
          <option value="partial" <?php echo $st_f === 'partial' ? 'selected' : ''; ?>>Partial</option>
          <option value="paid" <?php echo $st_f === 'paid' ? 'selected' : ''; ?>>Paid</option>
          <option value="overdue" <?php echo $st_f === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
        </select>
      </form>
      <a href="invoice-create.php" class="btn btn-sm">＋ Nayi Invoice</a>
    </div>
  </div>
  <?php if (!$invoices): ?>
    <div class="empty">Koi invoice nahi. Pehli invoice banayein. 🧾</div>
  <?php else: ?>
  <table class="tbl">
    <tr><th>Invoice No.</th><th>Client</th><th class="c">Issue Date</th><th class="c">Due Date</th><th class="r">Amount</th><th class="r">Paid</th><th class="r">Balance</th><th class="r">Status</th><th class="r">Actions</th></tr>
    <?php foreach ($invoices as $inv):
      $bal = (float)$inv['amount'] - (float)$inv['paid_amount'];
      $od = $inv['status'] !== 'paid' && $inv['due_date'] && $inv['due_date'] < today();
      $cls = $inv['status'] === 'paid' ? 'b-ok' : ($inv['status'] === 'partial' ? 'b-info' : ($od ? 'b-err' : 'b-warn'));
      $lbl = $od ? 'Overdue' : ucfirst($inv['status']);
    ?>
    <tr>
      <td><a href="invoice-view.php?id=<?php echo $inv['id']; ?>"><b><?php echo esc($inv['invoice_no']); ?></b></a></td>
      <td><?php echo esc($inv['company'] ?: $inv['cname']); ?></td>
      <td class="c"><?php echo dmy($inv['issue_date']); ?></td>
      <td class="c"><?php echo dmy($inv['due_date']); ?></td>
      <td class="r"><?php echo money($inv['amount']); ?></td>
      <td class="r"><?php echo money($inv['paid_amount']); ?></td>
      <td class="r strong" style="color:<?php echo $bal > 0 ? 'var(--warn)' : 'var(--ok)'; ?>"><?php echo money($bal); ?></td>
      <td class="r"><span class="badge <?php echo $cls; ?>"><?php echo $lbl; ?></span></td>
      <td class="r"><a href="invoice-view.php?id=<?php echo $inv['id']; ?>" class="btn btn-ghost btn-sm">Open</a>
      <a href="invoices.php?del=<?php echo $inv['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Invoice delete ho jayegi. Continue?">✕</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
