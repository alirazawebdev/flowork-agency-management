<?php
// ============================================================
// Flowork Agency Software - Project detail: milestones,
// expenses, team, profit — sab kuch ek jagah
// ============================================================
if (!defined('FW_APP')) { define('FW_APP', true); }
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$base = qrow("SELECT p.*, c.name AS cname, c.company FROM projects p JOIN clients c ON c.id = p.client_id WHERE p.id = ?", [$id]);
if (!$base) { die('Project nahi mila.'); }

// ---------- Actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';

  if ($act === 'add_milestone') {
    $t = trim($_POST['title']); $amt = (float)($_POST['amount'] ?? 0);
    if ($t === '' || $amt <= 0) { flash('Milestone ka naam aur amount zaroori hai.', 'err'); }
    else {
      qins("INSERT INTO milestones (project_id, title, amount, due_date) VALUES (?,?,?,?)",
        [$id, $t, $amt, $_POST['due_date'] ?: null]);
      flash('Milestone add ho gaya. ✅');
    }
  }
  elseif ($act === 'milestone_paid') {
    $mid = (int)$_POST['mid'];
    $m = qrow("SELECT * FROM milestones WHERE id = ? AND project_id = ?", [$mid, $id]);
    if ($m && $m['status'] !== 'paid') {
      record_payment([
        'client_id' => $base['client_id'], 'project_id' => $id, 'invoice_id' => null,
        'amount' => (float)$m['amount'], 'method' => $_POST['method'] ?? 'Cash',
        'payment_date' => today(), 'notes' => 'Milestone: ' . $m['title'],
      ]);
      q("UPDATE milestones SET status='paid', paid_date=CURDATE() WHERE id=?", [$mid]);
      flash('Milestone paid mark ho gaya aur payment record ho gayi. 💰');
    }
  }
  elseif ($act === 'del_milestone') {
    q("DELETE FROM milestones WHERE id = ? AND project_id = ?", [(int)$_POST['mid'], $id]);
    flash('Milestone delete ho gaya.');
  }
  elseif ($act === 'add_expense') {
    $amt = (float)($_POST['amount'] ?? 0); $desc = trim($_POST['description'] ?? '');
    if ($amt <= 0 || $desc === '') { flash('Expense ki detail aur amount zaroori hai.', 'err'); }
    else {
      qins("INSERT INTO expenses (project_id, category, description, amount, expense_date) VALUES (?,?,?,?,?)",
        [$id, $_POST['category'] ?? 'General', $desc, $amt, $_POST['expense_date'] ?: today()]);
      flash('Project expense add ho gaya. 💸');
    }
  }
  elseif ($act === 'del_expense') {
    q("DELETE FROM expenses WHERE id = ? AND project_id = ?", [(int)$_POST['eid'], $id]);
    flash('Expense delete ho gaya.');
  }
  elseif ($act === 'assign_team') {
    q("DELETE FROM project_team WHERE project_id = ?", [$id]);
    foreach (($_POST['team'] ?? []) as $uid) {
      qins("INSERT INTO project_team (project_id, user_id) VALUES (?,?)", [$id, (int)$uid]);
    }
    flash('Team assign ho gayi. 👥');
  }
  elseif ($act === 'set_status') {
    q("UPDATE projects SET status = ? WHERE id = ?", [valid_project_status($_POST['status'] ?? 'new'), $id]);
    flash('Project status update ho gaya.');
  }
  redirect('project-view.php?id=' . $id);
}

$page_title = $base['title'];
$page_sub = ($base['company'] ?: $base['cname']) . ($base['service'] ? ' · ' . $base['service'] : '');
$active = 'projects';
require_once __DIR__ . '/includes/header.php';

$st = project_stats($id);
$p = $st['project'];
$milestones = qall("SELECT * FROM milestones WHERE project_id = ? ORDER BY id ASC", [$id]);
$expenses = qall("SELECT * FROM expenses WHERE project_id = ? ORDER BY expense_date DESC", [$id]);
$invoices = qall("SELECT * FROM invoices WHERE project_id = ? ORDER BY id DESC", [$id]);
$payments = qall("SELECT py.*, i.invoice_no FROM payments py LEFT JOIN invoices i ON i.id = py.invoice_id WHERE py.project_id = ? ORDER BY py.payment_date DESC", [$id]);
$team_all = qall("SELECT id, name, designation FROM users ORDER BY name");
$team_assigned = qall("SELECT user_id FROM project_team WHERE project_id = ?", [$id]);
$assigned_ids = array_column($team_assigned, 'user_id');
$cats = qall("SELECT name FROM expense_categories ORDER BY name");
?>

<div class="kpis" style="grid-template-columns:repeat(3,1fr)">
  <div class="kpi"><div class="kpi-icon ic-indigo">💼</div><div><div class="kpi-label">Project Total</div><div class="kpi-value"><?php echo money($p['total_amount']); ?></div><div class="kpi-sub">Deadline: <?php echo dmy($p['deadline']); ?></div></div></div>
  <div class="kpi"><div class="kpi-icon ic-green">✅</div><div><div class="kpi-label">Collected</div><div class="kpi-value"><?php echo money($st['collected']); ?></div><div class="kpi-sub"><?php echo money($st['remaining']); ?> abhi baaki</div></div></div>
  <div class="kpi"><div class="kpi-icon ic-amber">💸</div><div><div class="kpi-label">Project Expenses</div><div class="kpi-value"><?php echo money($st['expenses']); ?></div><div class="kpi-sub">Is project par kharcha</div></div></div>
  <div class="kpi"><div class="kpi-icon ic-violet">✨</div><div><div class="kpi-label">Project Profit</div><div class="kpi-value" style="color:<?php echo $st['profit'] >= 0 ? 'var(--ok)' : 'var(--err)'; ?>"><?php echo money($st['profit']); ?></div><div class="kpi-sub">Collected − Expenses</div></div></div>
  <div class="kpi"><div class="kpi-icon ic-sky">🧾</div><div><div class="kpi-label">Milestones</div><div class="kpi-value"><?php echo $st['ms_paid']; ?>/<?php echo $st['ms_total']; ?></div><div class="kpi-sub">Paid / Total</div></div></div>
  <div class="kpi"><div class="kpi-icon ic-rose">🏁</div><div><div class="kpi-label">Status</div>
    <form method="post" style="margin-top:6px"><input type="hidden" name="action" value="set_status">
      <select name="status" class="input" style="padding:7px 10px;font-size:13px" onchange="this.form.submit()">
        <?php foreach (['new'=>'New','in_progress'=>'In Progress','delivered'=>'Delivered','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$v): ?>
        <option value="<?php echo $k; ?>" <?php echo $p['status'] === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
    </form></div></div>
</div>

<div class="grid-2">
  <!-- Milestones / Payment plan -->
  <div class="card">
    <div class="card-head"><div class="card-title">Payment Plan (Milestones)</div></div>
    <div class="card-sub">Project ki payment ko hisson mein taqseem karein — Advance, Mid, Final</div>
    <form method="post" class="mb">
      <input type="hidden" name="action" value="add_milestone">
      <div class="form-grid">
        <div class="field"><label>Milestone title *</label><input name="title" class="input" required placeholder="e.g. Advance Payment"></div>
        <div class="field"><label>Amount (Rs) *</label><input type="number" name="amount" class="input" required min="1" step="any"></div>
        <div class="field full"><label>Due date</label><input type="date" name="due_date" class="input"></div>
      </div>
      <div class="form-actions"><button class="btn btn-sm">＋ Add Milestone</button></div>
    </form>
    <?php if (!$milestones): ?><div class="empty">Koi milestone nahi — upar se payment plan banayein.</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Milestone</th><th class="r">Amount</th><th class="c">Due</th><th class="r">Status</th><th class="r"></th></tr>
      <?php foreach ($milestones as $m):
        $has_inv = qrow("SELECT id FROM invoices WHERE milestone_id = ?", [$m['id']]);
        $od = $m['status'] !== 'paid' && $m['due_date'] && $m['due_date'] < today();
      ?>
      <tr>
        <td><b><?php echo esc($m['title']); ?></b></td>
        <td class="r"><?php echo money($m['amount']); ?></td>
        <td class="c"><?php echo dmy($m['due_date']); ?></td>
        <td class="r"><?php echo $m['status'] === 'paid' ? '<span class="badge b-ok">Paid</span>' : ($od ? '<span class="badge b-err">Overdue</span>' : '<span class="badge b-warn">Unpaid</span>'); ?></td>
        <td class="r">
          <?php if ($m['status'] !== 'paid'): ?>
            <?php if (!$has_inv): ?><a href="invoice-create.php?milestone=<?php echo $m['id']; ?>" class="btn btn-sm">🧾 Invoice</a><?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Is milestone ko paid mark karein?')">
              <input type="hidden" name="action" value="milestone_paid"><input type="hidden" name="mid" value="<?php echo $m['id']; ?>">
              <input type="hidden" name="method" value="Cash">
              <button class="btn btn-ok btn-sm">✓ Paid</button>
            </form>
          <?php endif; ?>
          <form method="post" style="display:inline"><input type="hidden" name="action" value="del_milestone"><input type="hidden" name="mid" value="<?php echo $m['id']; ?>">
            <button class="btn btn-danger btn-sm" data-confirm="Milestone delete karein?">✕</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <!-- Project expenses -->
  <div class="card">
    <div class="card-head"><div class="card-title">Project Expenses</div></div>
    <div class="card-sub">Is project par jo kharcha aaya — posting, ads, etc. Profit isi se niklega.</div>
    <form method="post" class="mb">
      <input type="hidden" name="action" value="add_expense">
      <div class="form-grid">
        <div class="field"><label>Category</label><select name="category" class="input"><?php foreach ($cats as $cc): ?><option><?php echo esc($cc['name']); ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Date</label><input type="date" name="expense_date" class="input" value="<?php echo today(); ?>"></div>
        <div class="field"><label>Description *</label><input name="description" class="input" required placeholder="e.g. Facebook ads / posting"></div>
        <div class="field"><label>Amount (Rs) *</label><input type="number" name="amount" class="input" required min="1" step="any"></div>
      </div>
      <div class="form-actions"><button class="btn btn-sm">＋ Add Expense</button></div>
    </form>
    <?php if (!$expenses): ?><div class="empty">Is project ka koi expense record nahi.</div>
    <?php else: ?>
    <table class="tbl">
      <tr><th>Date</th><th>Detail</th><th class="r">Amount</th><th class="r"></th></tr>
      <?php foreach ($expenses as $e): ?>
      <tr><td><?php echo dmy($e['expense_date']); ?></td><td><b><?php echo esc($e['description']); ?></b><br><small class="muted"><?php echo esc($e['category']); ?></small></td>
      <td class="r"><?php echo money($e['amount']); ?></td>
      <td class="r"><form method="post" style="display:inline"><input type="hidden" name="action" value="del_expense"><input type="hidden" name="eid" value="<?php echo $e['id']; ?>"><button class="btn btn-danger btn-sm" data-confirm="Expense delete karein?">✕</button></form></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div class="card-title">Team on this Project</div></div>
    <form method="post">
      <input type="hidden" name="action" value="assign_team">
      <div class="field"><label>Team members select karein</label>
        <select name="team[]" class="input" multiple size="4">
          <?php foreach ($team_all as $t): ?>
          <option value="<?php echo $t['id']; ?>" <?php echo in_array($t['id'], $assigned_ids) ? 'selected' : ''; ?>><?php echo esc($t['name']); ?><?php echo $t['designation'] ? ' — ' . esc($t['designation']) : ''; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-actions"><button class="btn btn-sm">Save Team</button></div>
    </form>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">Invoices &amp; Payments</div><a href="invoice-create.php?project=<?php echo $id; ?>" class="btn btn-sm">＋ Nayi Invoice</a></div>
    <?php if (!$invoices && !$payments): ?><div class="empty">Koi invoice ya payment nahi.</div><?php endif; ?>
    <?php if ($invoices): ?>
    <div class="card-sub" style="margin-top:8px">Invoices</div>
    <table class="tbl">
      <tr><th>No.</th><th class="r">Amount</th><th class="r">Paid</th><th class="r">Status</th></tr>
      <?php foreach ($invoices as $inv): ?>
      <tr><td><a href="invoice-view.php?id=<?php echo $inv['id']; ?>"><b><?php echo esc($inv['invoice_no']); ?></b></a></td>
      <td class="r"><?php echo money($inv['amount']); ?></td><td class="r"><?php echo money($inv['paid_amount']); ?></td>
      <td class="r"><span class="badge <?php echo $inv['status'] === 'paid' ? 'b-ok' : ($inv['status'] === 'partial' ? 'b-info' : 'b-warn'); ?>"><?php echo ucfirst($inv['status']); ?></span></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <?php if ($payments): ?>
    <div class="card-sub" style="margin-top:14px">Payments</div>
    <table class="tbl">
      <tr><th>Date</th><th>Method</th><th class="r">Amount</th></tr>
      <?php foreach ($payments as $py): ?>
      <tr><td><?php echo dmy($py['payment_date']); ?></td><td><?php echo esc($py['method']); ?><br><small class="muted"><?php echo esc($py['notes']); ?></small></td><td class="r strong"><?php echo money($py['amount']); ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
