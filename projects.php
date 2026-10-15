<?php
// ============================================================
// Flowork Agency Software - Projects (list + add/edit/delete)
// ============================================================
$page_title = 'Projects';
$page_sub = 'Tamam projects — status aur payment progress ke saath';
$active = 'projects';
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['del'])) {
  q("DELETE FROM projects WHERE id = ?", [(int)$_GET['del']]);
  flash('Project delete ho gaya.');
  redirect('projects.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
  $id = (int)($_POST['id'] ?? 0);
  $title = trim($_POST['title']);
  $client_id = (int)($_POST['client_id'] ?? 0);
  if ($title === '' || $client_id <= 0) { flash('Project title aur client zaroori hain.', 'err'); }
  else {
    $d = [$client_id, $title, trim($_POST['service']), trim($_POST['description']), (float)($_POST['total_amount'] ?? 0), $_POST['deadline'] ?: null, valid_project_status($_POST['status'] ?? 'new')];
    if ($id > 0) {
      q("UPDATE projects SET client_id=?, title=?, service=?, description=?, total_amount=?, deadline=?, status=? WHERE id=?", [...$d, $id]);
      flash('Project update ho gaya.');
    } else {
      $id = qins("INSERT INTO projects (client_id, title, service, description, total_amount, deadline, status) VALUES (?,?,?,?,?,?,?)", $d);
      flash('Naya project ban gaya. Ab iski payment plan (milestones) set karein. 🎉');
      redirect('project-view.php?id=' . $id);
    }
  }
  redirect('projects.php');
}

$edit = null;
if (isset($_GET['edit'])) { $edit = qrow("SELECT * FROM projects WHERE id = ?", [(int)$_GET['edit']]); }
$clients = qall("SELECT id, name, company FROM clients ORDER BY company, name");

$status_f = $_GET['status'] ?? '';
$where = ''; $params = [];
if ($status_f !== '') { $where = "WHERE p.status = ?"; $params[] = $status_f; }
$projects = qall(
  "SELECT p.*, c.company, c.name AS cname,
          COALESCE((SELECT SUM(amount) FROM payments WHERE project_id = p.id),0) AS collected
   FROM projects p JOIN clients c ON c.id = p.client_id
   $where ORDER BY p.id DESC", $params
);

$prefill_client = (int)($_GET['client'] ?? 0);
?>

<div class="card">
  <div class="card-head"><div class="card-title"><?php echo $edit ? 'Edit Project' : '＋ Naya Project'; ?></div></div>
  <form method="post" action="projects.php">
    <input type="hidden" name="id" value="<?php echo $edit ? (int)$edit['id'] : 0; ?>">
    <div class="form-grid">
      <div class="field"><label>Client *</label>
        <select name="client_id" class="input" required>
          <option value="">— Select karein —</option>
          <?php foreach ($clients as $c): $sel = ($edit ? $edit['client_id'] : $prefill_client) == $c['id'] ? 'selected' : ''; ?>
          <option value="<?php echo $c['id']; ?>" <?php echo $sel; ?>><?php echo esc($c['company'] ? $c['company'] . ' (' . $c['name'] . ')' : $c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Project Title *</label><input name="title" class="input" required value="<?php echo esc($edit['title'] ?? ''); ?>" placeholder="e.g. Complete Business Website"></div>
      <div class="field"><label>Service</label><input name="service" class="input" value="<?php echo esc($edit['service'] ?? ''); ?>" placeholder="e.g. Web Development, SEO, Marketing"></div>
      <div class="field"><label>Total Amount (Rs) *</label><input type="number" name="total_amount" class="input" required min="0" step="any" value="<?php echo esc($edit['total_amount'] ?? ''); ?>"></div>
      <div class="field"><label>Deadline</label><input type="date" name="deadline" class="input" value="<?php echo esc($edit['deadline'] ?? ''); ?>"></div>
      <div class="field"><label>Status</label>
        <select name="status" class="input">
          <?php foreach (['new'=>'New','in_progress'=>'In Progress','delivered'=>'Delivered','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$v): ?>
          <option value="<?php echo $k; ?>" <?php echo ($edit['status'] ?? 'new') === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field full"><label>Description</label><textarea name="description" class="input" style="min-height:60px"><?php echo esc($edit['description'] ?? ''); ?></textarea></div>
    </div>
    <div class="form-actions">
      <button type="submit" name="save_project" class="btn"><?php echo $edit ? 'Update Project' : 'Create Project'; ?></button>
      <?php if ($edit): ?><a href="projects.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head">
    <div class="card-title">Project List (<?php echo count($projects); ?>)</div>
    <form method="get" class="toolbar no-print">
      <select name="status" class="input" style="width:auto" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <?php foreach (['new'=>'New','in_progress'=>'In Progress','delivered'=>'Delivered','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$v): ?>
        <option value="<?php echo $k; ?>" <?php echo $status_f === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
  <?php if (!$projects): ?>
    <div class="empty">Koi project nahi. Upar form se pehla project banayein.</div>
  <?php else: ?>
  <table class="tbl">
    <tr><th>Project</th><th>Client</th><th class="r">Total</th><th class="r">Collected</th><th class="c">Deadline</th><th class="r">Status</th><th class="r">Actions</th></tr>
    <?php foreach ($projects as $p): ?>
    <tr>
      <td><a href="project-view.php?id=<?php echo $p['id']; ?>"><b><?php echo esc($p['title']); ?></b></a><br><small class="muted"><?php echo esc($p['service']); ?></small></td>
      <td><?php echo esc($p['company'] ?: $p['cname']); ?></td>
      <td class="r"><?php echo money($p['total_amount']); ?></td>
      <td class="r"><?php echo money($p['collected']); ?></td>
      <td class="c"><?php echo dmy($p['deadline']); ?></td>
      <td class="r"><span class="badge <?php echo project_status_class($p['status']); ?>"><?php echo project_status_label($p['status']); ?></span></td>
      <td class="r"><a href="project-view.php?id=<?php echo $p['id']; ?>" class="btn btn-ghost btn-sm">Open</a>
      <a href="projects.php?edit=<?php echo $p['id']; ?>" class="btn btn-ghost btn-sm">Edit</a>
      <a href="projects.php?del=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Ye project aur uske milestones delete ho jayenge. Continue?">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
