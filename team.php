<?php
// ============================================================
// Flowork Agency Software - Team (admin only)
// Team members = login users (admin / staff roles)
// ============================================================
$page_title = 'Team';
$page_sub = 'Team members aur unke login accounts';
$active = 'team';
require_once __DIR__ . '/includes/header.php';
require_admin();

if (isset($_GET['del'])) {
  $del_id = (int)$_GET['del'];
  if ($del_id === (int)$user['id']) { flash('Apne aap ko delete nahi kar sakte.', 'err'); }
  else { q("DELETE FROM users WHERE id = ?", [$del_id]); flash('Team member delete ho gaya.'); }
  redirect('team.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_member'])) {
  $id = (int)($_POST['id'] ?? 0);
  $name = trim($_POST['name']); $email = trim($_POST['email']);
  $role = $_POST['role'] === 'admin' ? 'admin' : 'staff';
  $desig = trim($_POST['designation']); $phone = trim($_POST['phone']);
  $pass = $_POST['password'] ?? '';
  if ($name === '' || $email === '') { flash('Naam aur email zaroori hain.', 'err'); }
  elseif ($id > 0) {
    if ($pass !== '') { q("UPDATE users SET name=?, email=?, role=?, designation=?, phone=?, password=? WHERE id=?", [$name, $email, $role, $desig, $phone, password_hash($pass, PASSWORD_DEFAULT), $id]); }
    else { q("UPDATE users SET name=?, email=?, role=?, designation=?, phone=? WHERE id=?", [$name, $email, $role, $desig, $phone, $id]); }
    flash('Team member update ho gaya.');
  } else {
    if ($pass === '') { flash('Naye member ke liye password zaroori hai.', 'err'); redirect('team.php'); }
    $exists = qrow("SELECT id FROM users WHERE email = ?", [$email]);
    if ($exists) { flash('Ye email pehle se use ho rahi hai.', 'err'); }
    else { qins("INSERT INTO users (name, email, password, role, designation, phone) VALUES (?,?,?,?,?,?)", [$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $desig, $phone]); flash('Naya team member add ho gaya. 👥'); }
  }
  redirect('team.php');
}

$edit = null;
if (isset($_GET['edit'])) { $edit = qrow("SELECT * FROM users WHERE id = ?", [(int)$_GET['edit']]); }
$members = qall("SELECT u.*, (SELECT COUNT(*) FROM project_team WHERE user_id = u.id) AS projects FROM users u ORDER BY u.id ASC");
?>

<div class="card">
  <div class="card-head"><div class="card-title"><?php echo $edit ? 'Edit Team Member' : '＋ Naya Team Member'; ?></div></div>
  <div class="card-sub">Staff role: dashboard, clients, projects, invoices, payments, expenses aur reports dekh sakta hai. Settings aur Team sirf admin ke liye.</div>
  <form method="post" action="team.php">
    <input type="hidden" name="id" value="<?php echo $edit ? (int)$edit['id'] : 0; ?>">
    <div class="form-grid">
      <div class="field"><label>Naam *</label><input name="name" class="input" required value="<?php echo esc($edit['name'] ?? ''); ?>"></div>
      <div class="field"><label>Email (login) *</label><input type="email" name="email" class="input" required value="<?php echo esc($edit['email'] ?? ''); ?>"></div>
      <div class="field"><label>Designation</label><input name="designation" class="input" value="<?php echo esc($edit['designation'] ?? ''); ?>" placeholder="e.g. Web Developer"></div>
      <div class="field"><label>Phone</label><input name="phone" class="input" value="<?php echo esc($edit['phone'] ?? ''); ?>"></div>
      <div class="field"><label>Role</label>
        <select name="role" class="input">
          <option value="staff" <?php echo ($edit['role'] ?? '') === 'staff' ? 'selected' : ''; ?>>Staff</option>
          <option value="admin" <?php echo ($edit['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
      </div>
      <div class="field"><label>Password <?php echo $edit ? '(khaali chhorein to same rahega)' : '*'; ?></label><input type="password" name="password" class="input" <?php echo $edit ? '' : 'required'; ?>></div>
    </div>
    <div class="form-actions">
      <button type="submit" name="save_member" class="btn"><?php echo $edit ? 'Update Member' : 'Add Member'; ?></button>
      <?php if ($edit): ?><a href="team.php" class="btn btn-ghost">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><div class="card-title">Team Members (<?php echo count($members); ?>)</div></div>
  <table class="tbl">
    <tr><th>Name</th><th>Designation</th><th>Email</th><th class="c">Role</th><th class="c">Projects</th><th class="r">Actions</th></tr>
    <?php foreach ($members as $m): ?>
    <tr>
      <td><b><?php echo esc($m['name']); ?></b><?php echo $m['id'] == $user['id'] ? ' <span class="badge b-primary">You</span>' : ''; ?></td>
      <td><?php echo esc($m['designation'] ?: '—'); ?></td>
      <td><?php echo esc($m['email']); ?></td>
      <td class="c"><span class="badge <?php echo $m['role'] === 'admin' ? 'b-primary' : 'b-muted'; ?>"><?php echo ucfirst($m['role']); ?></span></td>
      <td class="c"><?php echo $m['projects']; ?></td>
      <td class="r"><a href="team.php?edit=<?php echo $m['id']; ?>" class="btn btn-ghost btn-sm">Edit</a>
      <?php if ($m['id'] != $user['id']): ?><a href="team.php?del=<?php echo $m['id']; ?>" class="btn btn-danger btn-sm" data-confirm="Team member delete karein?">✕</a><?php endif; ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
