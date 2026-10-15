<?php
// ============================================================
// Flowork Agency Software - Dashboard
// ============================================================
$page_title = 'Dashboard';
$page_sub = date('l, d F Y') . ' — poori agency ek nazar mein';
$active = 'dashboard';
require_once __DIR__ . '/includes/header.php';

$st = dashboard_stats();
$series = monthly_series(6);

// Upcoming / overdue payments (unpaid milestones + unpaid invoices)
$upcoming = qall(
  "SELECT m.id, m.title, m.amount, m.due_date, p.title AS project, c.company, c.name AS cname
   FROM milestones m
   JOIN projects p ON p.id = m.project_id
   JOIN clients c ON c.id = p.client_id
   WHERE m.status = 'unpaid'
   ORDER BY (m.due_date IS NULL), m.due_date ASC
   LIMIT 6"
);

// Project profitability
$proj_profit = qall(
  "SELECT p.id, p.title, c.company, c.name AS cname,
          COALESCE((SELECT SUM(amount) FROM payments WHERE project_id = p.id),0) AS collected,
          COALESCE((SELECT SUM(amount) FROM expenses WHERE project_id = p.id),0) AS expenses
   FROM projects p
   JOIN clients c ON c.id = p.client_id
   WHERE p.status IN ('new','in_progress','delivered')
   ORDER BY p.id DESC
   LIMIT 6"
);

$recent_inv = qall(
  "SELECT i.*, c.company, c.name AS cname
   FROM invoices i JOIN clients c ON c.id = i.client_id
   ORDER BY i.id DESC LIMIT 5"
);
?>

<div class="kpis">
  <div class="kpi">
    <div class="kpi-icon ic-indigo">💰</div>
    <div><div class="kpi-label">Earning — Is Mahine</div><div class="kpi-value"><?php echo money($st['month_earn']); ?></div><div class="kpi-sub">Collected in <?php echo date('F'); ?></div></div>
  </div>
  <div class="kpi">
    <div class="kpi-icon ic-green">📈</div>
    <div><div class="kpi-label">Total Earning</div><div class="kpi-value"><?php echo money($st['total_earn']); ?></div><div class="kpi-sub">All-time collected</div></div>
  </div>
  <div class="kpi">
    <div class="kpi-icon ic-amber">💸</div>
    <div><div class="kpi-label">Total Expenses</div><div class="kpi-value"><?php echo money($st['total_exp']); ?></div><div class="kpi-sub"><?php echo money($st['month_exp']); ?> is mahine</div></div>
  </div>
  <div class="kpi">
    <div class="kpi-icon ic-violet">✨</div>
    <div><div class="kpi-label">Net Profit</div><div class="kpi-value"><?php echo money($st['net_profit']); ?></div><div class="kpi-sub">Earning − Expenses (kul)</div></div>
  </div>
  <div class="kpi">
    <div class="kpi-icon ic-rose">⏳</div>
    <div><div class="kpi-label">Pending Payments</div><div class="kpi-value"><?php echo money($st['pending']); ?></div><div class="kpi-sub"><?php echo $st['overdue']; ?> invoices overdue</div></div>
  </div>
  <div class="kpi">
    <div class="kpi-icon ic-sky">📁</div>
    <div><div class="kpi-label">Active Projects</div><div class="kpi-value"><?php echo $st['active']; ?></div><div class="kpi-sub"><?php echo $st['clients']; ?> total clients · <?php echo $st['month_new_clients']; ?> new is mahine</div></div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div class="card-title">Earning vs Expenses</div></div>
    <div class="card-sub">Pichlay 6 mahine — collected payments aur kharchay</div>
    <div style="position:relative;height:270px"><canvas id="finChart"></canvas></div>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">Aanay Wali Payments</div><a href="invoices.php" class="btn btn-ghost btn-sm">Sab dekhein</a></div>
    <div class="card-sub">Unpaid milestones — due date ke hisaab se</div>
    <?php if (!$upcoming): ?>
      <div class="empty">Koi pending milestone nahi. 🎉</div>
    <?php else: ?>
      <?php foreach ($upcoming as $u):
        $od = $u['due_date'] && $u['due_date'] < today();
        $badge = $od ? '<span class="badge b-err">Overdue</span>' : '<span class="badge b-warn">Unpaid</span>';
      ?>
      <div class="pay-row">
        <div class="who"><b><?php echo esc($u['company'] ?: $u['cname']); ?></b><small><?php echo esc($u['title']); ?> · <?php echo $u['due_date'] ? dmy($u['due_date']) : 'No due date'; ?></small></div>
        <div class="amt"><b><?php echo money($u['amount']); ?></b><br><?php echo $badge; ?></div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div class="card-title">Project Profitability</div><a href="reports.php" class="btn btn-ghost btn-sm">Reports</a></div>
    <div class="card-sub">Har project par kitna kamaya — collected minus us project ke expenses</div>
    <?php if (!$proj_profit): ?>
      <div class="empty">Abhi koi active project nahi.</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Project</th><th class="r">Collected</th><th class="r">Expenses</th><th class="r">Profit</th></tr>
      <?php foreach ($proj_profit as $pp):
        $profit = (float)$pp['collected'] - (float)$pp['expenses'];
      ?>
      <tr>
        <td><b><?php echo esc($pp['title']); ?></b><br><small class="muted"><?php echo esc($pp['company'] ?: $pp['cname']); ?></small></td>
        <td class="r"><?php echo money($pp['collected']); ?></td>
        <td class="r"><?php echo money($pp['expenses']); ?></td>
        <td class="r strong" style="color:<?php echo $profit >= 0 ? 'var(--ok)' : 'var(--err)'; ?>"><?php echo money($profit); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">Recent Invoices</div><a href="invoices.php" class="btn btn-ghost btn-sm">Sab dekhein</a></div>
    <div class="card-sub">Aakhri 5 invoices</div>
    <?php if (!$recent_inv): ?>
      <div class="empty">Abhi koi invoice nahi bani.</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Invoice</th><th>Client</th><th class="r">Amount</th><th class="r">Status</th></tr>
      <?php foreach ($recent_inv as $ri):
        $st_cls = $ri['status'] === 'paid' ? 'b-ok' : ($ri['status'] === 'partial' ? 'b-info' : 'b-warn');
        if ($ri['status'] !== 'paid' && $ri['due_date'] && $ri['due_date'] < today()) { $st_cls = 'b-err'; }
      ?>
      <tr>
        <td><a href="invoice-view.php?id=<?php echo $ri['id']; ?>"><b><?php echo esc($ri['invoice_no']); ?></b></a></td>
        <td><?php echo esc($ri['company'] ?: $ri['cname']); ?></td>
        <td class="r"><?php echo money($ri['amount']); ?></td>
        <td class="r"><span class="badge <?php echo $st_cls; ?>"><?php echo ucfirst($ri['status']); ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php
$extra_js = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById("finChart"), {
  type: "bar",
  data: {
    labels: ' . json_encode($series['labels']) . ',
    datasets: [
      { label: "Earning", data: ' . json_encode($series['earn']) . ', backgroundColor: "#4f46e5", borderRadius: 7, barThickness: 26 },
      { label: "Expenses", data: ' . json_encode($series['exp']) . ', backgroundColor: "#f59e0b", borderRadius: 7, barThickness: 26 }
    ]
  },
  options: {
    maintainAspectRatio: false,
    plugins: { legend: { position: "top", labels: { boxWidth: 12, font: { size: 12 } } } },
    scales: {
      x: { grid: { display: false } },
      y: { ticks: { callback: function(v){ return "Rs " + (v/1000) + "k"; } }, grid: { color: "#eef1f7" } }
    }
  }
});
</script>';
require_once __DIR__ . '/includes/footer.php';
