<?php
// ============================================================
// Flowork Agency Software - Reports
// ============================================================
$page_title = 'Reports';
$page_sub = 'Earning, expenses, profit aur pending — sab reports';
$active = 'reports';
require_once __DIR__ . '/includes/header.php';

// Monthly summary (last 12 months)
$months = [];
for ($i = 11; $i >= 0; $i--) {
  $ym = date('Y-m', strtotime("-$i months"));
  $e = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE DATE_FORMAT(payment_date,'%Y-%m')=?", [$ym])['s'];
  $x = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=?", [$ym])['s'];
  $months[] = ['ym' => $ym, 'label' => date('M Y', strtotime($ym . '-01')), 'earn' => $e, 'exp' => $x, 'profit' => $e - $x];
}

// Project profitability (all projects)
$proj_all = qall(
  "SELECT p.id, p.title, p.total_amount, p.status, c.company, c.name AS cname,
          COALESCE((SELECT SUM(amount) FROM payments WHERE project_id = p.id),0) AS collected,
          COALESCE((SELECT SUM(amount) FROM expenses WHERE project_id = p.id),0) AS expenses
   FROM projects p JOIN clients c ON c.id = p.client_id
   ORDER BY p.id DESC"
);

// Pending invoices
$pending = qall(
  "SELECT i.*, c.company, c.name AS cname FROM invoices i JOIN clients c ON c.id = i.client_id
   WHERE i.status != 'paid' ORDER BY i.due_date ASC"
);

// Client-wise business
$client_biz = qall(
  "SELECT c.id, c.name, c.company,
          COALESCE((SELECT SUM(total_amount) FROM projects WHERE client_id = c.id),0) AS business,
          COALESCE((SELECT SUM(amount) FROM payments WHERE client_id = c.id),0) AS collected
   FROM clients c ORDER BY business DESC"
);
?>

<div class="card">
  <div class="card-head"><div class="card-title">📊 Monthly Summary — Earning vs Expenses vs Profit</div></div>
  <div class="card-sub">Pichlay 12 mahine</div>
  <table class="tbl">
    <tr><th>Month</th><th class="r">Earning</th><th class="r">Expenses</th><th class="r">Profit</th></tr>
    <?php foreach ($months as $m): ?>
    <tr>
      <td><b><?php echo $m['label']; ?></b></td>
      <td class="r"><?php echo money($m['earn']); ?></td>
      <td class="r"><?php echo money($m['exp']); ?></td>
      <td class="r strong" style="color:<?php echo $m['profit'] >= 0 ? 'var(--ok)' : 'var(--err)'; ?>"><?php echo money($m['profit']); ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <div class="card-head"><div class="card-title">💼 Project Profitability</div></div>
  <div class="card-sub">Har project: collected − project expenses = profit</div>
  <?php if (!$proj_all): ?><div class="empty">Koi project nahi.</div>
  <?php else: ?>
  <table class="tbl">
    <tr><th>Project</th><th>Client</th><th class="r">Total</th><th class="r">Collected</th><th class="r">Expenses</th><th class="r">Profit</th><th class="r">Status</th></tr>
    <?php foreach ($proj_all as $pp):
      $profit = (float)$pp['collected'] - (float)$pp['expenses'];
    ?>
    <tr>
      <td><a href="project-view.php?id=<?php echo $pp['id']; ?>"><b><?php echo esc($pp['title']); ?></b></a></td>
      <td><?php echo esc($pp['company'] ?: $pp['cname']); ?></td>
      <td class="r"><?php echo money($pp['total_amount']); ?></td>
      <td class="r"><?php echo money($pp['collected']); ?></td>
      <td class="r"><?php echo money($pp['expenses']); ?></td>
      <td class="r strong" style="color:<?php echo $profit >= 0 ? 'var(--ok)' : 'var(--err)'; ?>"><?php echo money($profit); ?></td>
      <td class="r"><span class="badge <?php echo project_status_class($pp['status']); ?>"><?php echo project_status_label($pp['status']); ?></span></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div class="card-title">⏳ Pending Payments</div></div>
    <?php if (!$pending): ?><div class="empty">Koi pending payment nahi. 🎉</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Invoice</th><th>Client</th><th class="c">Due</th><th class="r">Balance</th></tr>
      <?php foreach ($pending as $pn):
        $bal = (float)$pn['amount'] - (float)$pn['paid_amount'];
        $od = $pn['due_date'] && $pn['due_date'] < today();
      ?>
      <tr>
        <td><a href="invoice-view.php?id=<?php echo $pn['id']; ?>"><b><?php echo esc($pn['invoice_no']); ?></b></a></td>
        <td><?php echo esc($pn['company'] ?: $pn['cname']); ?></td>
        <td class="c"><?php echo dmy($pn['due_date']); ?> <?php echo $od ? '<span class="badge b-err">Late</span>' : ''; ?></td>
        <td class="r strong"><?php echo money($bal); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">👥 Client-wise Business</div></div>
    <?php if (!$client_biz): ?><div class="empty">Koi client nahi.</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Client</th><th class="r">Business</th><th class="r">Collected</th><th class="r">Remaining</th></tr>
      <?php foreach ($client_biz as $cb): ?>
      <tr>
        <td><a href="client-view.php?id=<?php echo $cb['id']; ?>"><b><?php echo esc($cb['company'] ?: $cb['name']); ?></b></a></td>
        <td class="r"><?php echo money($cb['business']); ?></td>
        <td class="r"><?php echo money($cb['collected']); ?></td>
        <td class="r" style="color:var(--warn)"><?php echo money((float)$cb['business'] - (float)$cb['collected']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
