<?php
// ============================================================
// Flowork Agency Software - Expenses (list + add/delete)
// Project se link ho sakta hai taake project profit nikle.
// ============================================================
$page_title = 'Expenses';
$page_sub = 'Agency ke kharchay — project-wise ya general';
$active = 'expenses';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['del'])) {
  q("DELETE FROM expenses WHERE id = ?", [(int)$_GET['del']]);
  flash('Expense delete ho gaya.');
  redirect('expenses.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
  $amt = (float)($_POST['amount'] ?? 0); $desc = trim($_POST['description'] ?? '');
  if ($amt <= 0 || $desc === '') { flash('Detail aur amount zaroori hain.', 'err'); }
  else {
    $pid = (int)($_POST['project_id'] ?? 0) ?: null;
    qins("INSERT INTO expenses (project_id, category, description, amount, expense_date) VALUES (?,?,?,?,?)",
      [$pid, $_POST['category'] ?? 'General', $desc, $amt, $_POST['expense_date'] ?: today()]);
    flash('Expense add ho gaya. 💸');
  }
  redirect('expenses.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cat'])) {
  $n = trim($_POST['cat_name']);
  if ($n !== '') { qins("INSERT IGNORE INTO expense_categories (name) VALUES (?)", [$n]); flash('Category add ho gayi.'); }
  redirect('expenses.php');
}

$cats = qall("SELECT name FROM expense_categories ORDER BY name");
$projects = qall("SELECT p.id, p.title, c.company FROM projects p JOIN clients c ON c.id = p.client_id ORDER BY p.id DESC");

$month_f = $_GET['month'] ?? '';
$where = ''; $params = [];
if ($month_f !== '' && preg_match('/^\d{4}-\d{2}$/', $month_f)) { $where = "WHERE DATE_FORMAT(e.expense_date,'%Y-%m') = ?"; $params[] = $month_f; }
$expenses = qall(
  "SELECT e.*, p.title AS ptitle FROM expenses e LEFT JOIN projects p ON p.id = e.project_id
   $where ORDER BY e.expense_date DESC, e.id DESC LIMIT 300", $params
);
$total = array_sum(array_column($expenses, 'amount'));
?>

<div class="grid-2">
  <div class="card">
    <div class="card-head"><div class="card-title">＋ Naya Expense</div></div>
    <form method="post">
      <div class="form-grid">
        <div class="field"><label>Description *</label><input name="description" class="input" required placeholder="e.g. Team salaries / FB ads"></div>
        <div class="field"><label>Amount (Rs) *</label><input type="number" name="amount" class="input" required min="1" step="any"></div>
        <div class="field"><label>Category</label><select name="category" class="input"><?php foreach ($cats as $cc): ?><option><?php echo esc($cc['name']); ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Date</label><input type="date" name="expense_date" class="input" value="<?php echo today(); ?>"></div>
        <div class="field full"><label>Project (optional — project profit ke liye link karein)</label>
          <select name="project_id" class="input"><option value="">— General expense (kisi project se linked nahi) —</option>
          <?php foreach ($projects as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo esc($p['title']); ?> (<?php echo esc($p['company']); ?>)</option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-actions"><button name="add_expense" class="btn btn-sm">Add Expense 💸</button></div>
    </form>
  </div>
  <div class="card">
    <div class="card-head"><div class="card-title">Categories</div></div>
    <div class="card-sub">Apni categories banayein</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
      <?php foreach ($cats as $cc): ?><span class="badge b-muted" style="font-size:12.5px;padding:7px 14px"><?php echo esc($cc['name']); ?></span><?php endforeach; ?>
    </div>
    <form method="post" style="display:flex;gap:8px">
      <input name="cat_name" class="input" placeholder="Nayi category…" required>
      <button name="add_cat" class="btn btn-ghost btn-sm" style="white-space:nowrap">Add</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <div class="card-title">Expense List <?php echo $month_f ? '(' . esc($month_f) . ')' : ''; ?> — Total: <?php echo money($total); ?></div>
    <form method="get" class="toolbar no-print">
      <input type="month" name="month" class="input" style="width:auto" value="<?php echo esc($month_f); ?>" onchange="this.form.submit()">
      <?php if ($month_f): ?><a href="expenses.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
  <?php if (!$expenses): ?><div class="empty">Koi expense record nahi.</div>
  <?php else: ?>
  <table class="tbl">
    <tr><th>Date</th><th>Description</th><th>Category</th><th>Project</th><th class="r">Amount</th><th class="r"></th></tr>
    <?php foreach ($expenses as $e): ?>
    <tr>
      <td><?php echo dmy($e['expense_date']); ?></td>
      <td><b><?php echo esc($e['description']); ?></b></td>
      <td><span class="badge b-muted"><?php echo esc($e['category']); ?></span></td>
      <td><?php echo $e['ptitle'] ? '<a href="project-view.php?id=' . $e['project_id'] . '">' . esc($e['ptitle']) . '</a>' : '<small class="muted">General</small>'; ?></td>
      <td class="r strong"><?php echo money($e['amount']); ?></td>
      <td class="r"><a href="expenses.php?del=<?php echo $e['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Expense delete karein?">✕</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
