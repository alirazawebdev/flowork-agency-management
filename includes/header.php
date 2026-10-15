<?php
// ============================================================
// Flowork Agency Software - Page header (sidebar + topbar)
// Har protected page sab se pehle ye include karta hai.
// $page_title aur $active pehle set karein.
// ============================================================
if (!defined('FW_APP')) { define('FW_APP', true); }
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

require_login();
$user = current_user();
$admin = is_admin();
if (!isset($page_title)) { $page_title = 'Dashboard'; }
if (!isset($active)) { $active = 'dashboard'; }
if (!isset($page_sub)) { $page_sub = ''; }

$nav = [
  ['dashboard', 'dashboard.php', '▦', 'Dashboard', true],
  ['clients',   'clients.php',   '👥', 'Clients', true],
  ['projects',  'projects.php',  '📁', 'Projects', true],
  ['__fin', null, null, 'Finance', true],
  ['invoices',  'invoices.php',  '🧾', 'Invoices', true],
  ['payments',  'payments.php',  '💳', 'Payments', true],
  ['expenses',  'expenses.php',  '💸', 'Expenses', true],
  ['__mng', null, null, 'Manage', true],
  ['team',      'team.php',      '🧑‍💼', 'Team', $admin],
  ['reports',   'reports.php',   '📊', 'Reports', true],
  ['settings',  'settings.php',  '⚙️', 'Settings', $admin],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($page_title); ?> — <?php echo esc(setting('agency_name', 'Flowork')); ?></title>
<style>
/* ============================================================
   Flowork Agency Software — Premium light theme
   ============================================================ */
:root{
  --bg:#eef1f7; --card:#ffffff; --ink:#0f1d33; --muted:#64748b;
  --line:#e6eaf2; --primary:#4f46e5; --primary2:#7c3aed;
  --ok:#16a34a; --warn:#d97706; --err:#dc2626; --info:#0284c7;
  --radius:16px; --shadow:0 2px 14px rgba(15,29,51,.06);
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter','Segoe UI',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--ink);font-size:14.5px;line-height:1.55}
a{color:var(--primary);text-decoration:none}
a:hover{text-decoration:underline}

/* ---------- Layout ---------- */
.sidebar{width:248px;background:linear-gradient(180deg,#0d1830 0%,#14264c 100%);color:#c6d2e8;position:fixed;top:0;bottom:0;left:0;display:flex;flex-direction:column;z-index:50}
.brand{padding:22px 20px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(255,255,255,.08)}
.brand-mark{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;color:#fff;box-shadow:0 4px 12px rgba(79,70,229,.4)}
.brand-name{font-size:19px;font-weight:800;color:#fff;letter-spacing:1px}
.brand-tag{font-size:10px;letter-spacing:2px;color:#8fa1c2}
.nav{flex:1;overflow-y:auto;padding:14px 12px;scrollbar-width:none}
.nav::-webkit-scrollbar{display:none}
.nav-label{font-size:10.5px;text-transform:uppercase;letter-spacing:1.6px;color:#6b7fa3;padding:14px 12px 6px}
.nav a{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:10px;color:#aebdd8;font-size:14px;margin-bottom:2px;font-weight:500}
.nav a:hover{background:rgba(255,255,255,.06);color:#fff;text-decoration:none}
.nav a.active{background:linear-gradient(90deg,#4f46e5,#6d5ef0);color:#fff;font-weight:600;box-shadow:0 4px 12px rgba(79,70,229,.35)}
.nav a .ni{width:22px;text-align:center;font-size:16px}
.side-user{padding:16px;border-top:1px solid rgba(255,255,255,.08);display:flex;align-items:center;gap:10px}
.side-user .av{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff}
.side-user b{color:#fff;font-size:13.5px;display:block}
.side-user small{color:#8fa1c2;font-size:11.5px}
.side-user a{margin-left:auto;color:#8fa1c2;font-size:12px}

.main{margin-left:248px;min-height:100vh;padding:0 30px 50px;max-width:1380px}
.topbar{display:flex;align-items:center;justify-content:space-between;padding:22px 0;gap:16px}
.page-title{font-size:23px;font-weight:800}
.page-sub{color:var(--muted);font-size:13px;margin-top:2px}
.top-right{display:flex;align-items:center;gap:10px}
.icon-btn{width:42px;height:42px;border-radius:12px;background:var(--card);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;box-shadow:var(--shadow);position:relative;color:var(--ink)}
.icon-btn .dot{position:absolute;top:9px;right:10px;width:8px;height:8px;border-radius:50%;background:var(--err)}
.top-avatar{width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700}

/* ---------- KPI cards ---------- */
.kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:4px 0 20px}
.kpi{background:var(--card);border-radius:var(--radius);padding:20px;box-shadow:var(--shadow);display:flex;gap:16px;align-items:flex-start;border:1px solid #f0f3f9}
.kpi-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:23px;flex-shrink:0}
.ic-indigo{background:#eef0ff}.ic-green{background:#e8f7ee}.ic-amber{background:#fdf3e3}
.ic-rose{background:#fdeaea}.ic-sky{background:#e6f5fd}.ic-violet{background:#f1eafe}
.kpi-label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;font-weight:600}
.kpi-value{font-size:25px;font-weight:800;margin:3px 0 1px}
.kpi-sub{font-size:12.5px;color:var(--muted)}
.kpi-sub .up{color:var(--ok);font-weight:600}.kpi-sub .down{color:var(--err);font-weight:600}

/* ---------- Cards / grids ---------- */
.card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:22px;border:1px solid #f0f3f9;margin-bottom:18px}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;gap:12px}
.card-title{font-size:16px;font-weight:700}
.card-sub{font-size:12.5px;color:var(--muted);margin-bottom:14px}
.grid-2{display:grid;grid-template-columns:1.55fr 1fr;gap:18px}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}

/* ---------- Tables ---------- */
table.tbl{width:100%;border-collapse:collapse;font-size:13.8px}
.tbl th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);padding:10px 8px;border-bottom:2px solid var(--line);font-weight:700}
.tbl td{padding:13px 8px;border-bottom:1px solid #f1f4f9;vertical-align:middle}
.tbl tr:last-child td{border-bottom:none}
.tbl tr:hover td{background:#f8fafd}
.tbl .r{text-align:right}.tbl .c{text-align:center}
.tbl .strong{font-weight:700}

/* ---------- Badges ---------- */
.badge{display:inline-block;font-size:11.5px;font-weight:700;padding:4px 11px;border-radius:20px;white-space:nowrap}
.b-ok{background:#e8f7ee;color:#15803d}.b-warn{background:#fdf3e3;color:#b45309}
.b-err{background:#fdeaea;color:#b91c1c}.b-info{background:#e6f5fd;color:#0369a1}
.b-muted{background:#eef1f6;color:#64748b}.b-primary{background:#eef0ff;color:#4f46e5}

/* ---------- Buttons ---------- */
.btn{display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;padding:11px 20px;border-radius:11px;font-weight:700;font-size:14px;background:linear-gradient(90deg,var(--primary),#6d5ef0);color:#fff;box-shadow:0 4px 12px rgba(79,70,229,.28)}
.btn:hover{filter:brightness(1.06);text-decoration:none;color:#fff}
.btn-ghost{background:#fff;color:var(--ink);border:1px solid var(--line);box-shadow:var(--shadow)}
.btn-ghost:hover{color:var(--ink)}
.btn-danger{background:#dc2626;box-shadow:0 4px 12px rgba(220,38,38,.25)}
.btn-ok{background:#16a34a;box-shadow:0 4px 12px rgba(22,163,74,.25)}
.btn-sm{padding:7px 13px;font-size:12.5px;border-radius:9px}
.toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}

/* ---------- Alerts ---------- */
.alert{padding:13px 18px;border-radius:12px;margin-bottom:16px;font-size:14px;font-weight:500}
.alert-ok{background:#e8f7ee;color:#166534;border:1px solid #bbe7c9}
.alert-err{background:#fdeaea;color:#991b1b;border:1px solid #f3c2c2}

/* ---------- Forms ---------- */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{margin-bottom:4px}
.field.full{grid-column:1/-1}
.field label{display:block;font-size:12.5px;font-weight:700;color:#33415c;margin-bottom:6px}
.input,select.input,textarea.input{width:100%;padding:11px 14px;border:1.5px solid var(--line);border-radius:11px;font-size:14px;font-family:inherit;background:#fbfcfe;color:var(--ink)}
.input:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 3px rgba(79,70,229,.12)}
textarea.input{resize:vertical;min-height:90px}
.form-actions{margin-top:16px;display:flex;gap:10px}

/* ---------- Progress / lists ---------- */
.progress{height:8px;background:#edf0f7;border-radius:6px;overflow:hidden}
.progress i{display:block;height:100%;border-radius:6px;background:linear-gradient(90deg,#4f46e5,#7c3aed)}
.pay-row{display:flex;align-items:center;justify-content:space-between;padding:13px 0;border-bottom:1px solid #f1f4f9;gap:10px}
.pay-row:last-child{border-bottom:none}
.pay-row .who b{display:block;font-size:14px}
.pay-row .who small{color:var(--muted);font-size:12px}
.pay-row .amt{text-align:right}
.stat-line{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f1f4f9;font-size:14px}
.stat-line:last-child{border:none}
.stat-line span{color:var(--muted)}
.stat-line b{font-size:14.5px}
.empty{padding:26px;text-align:center;color:var(--muted);font-size:13.5px}

/* ---------- Login page ---------- */
.login-body{background:linear-gradient(135deg,#0d1830,#1b3260);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:400px;max-width:100%;padding:38px 34px}
.login-brand{display:flex;align-items:center;gap:12px;margin-bottom:26px}
.login-card h2{font-size:21px;margin-bottom:4px}
.login-card .lsub{color:var(--muted);font-size:13px;margin-bottom:20px}

/* ---------- Installer ---------- */
.inst-body{background:linear-gradient(135deg,#0d1830,#1b3260);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.inst-card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:620px;max-width:100%;padding:36px}
.steps{display:flex;gap:6px;margin:20px 0 26px}
.step{flex:1;text-align:center;font-size:11.5px;color:var(--muted);font-weight:600}
.step .n{width:30px;height:30px;border-radius:50%;background:#edf0f7;display:flex;align-items:center;justify-content:center;margin:0 auto 5px;font-size:13px;font-weight:700}
.step.done .n{background:#e8f7ee;color:#15803d}
.step.now .n{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff}
.step.now{color:var(--ink)}
.req{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f1f4f9;font-size:14px}
.codebox{background:#0f1d33;color:#c6d2e8;border-radius:12px;padding:16px;font-family:monospace;font-size:12.5px;overflow-x:auto;margin:12px 0;white-space:pre}

/* ---------- Invoice sheet ---------- */
.inv-wrap{max-width:860px;margin:0 auto}
.inv-actions{display:flex;gap:10px;justify-content:flex-end;margin-bottom:16px;flex-wrap:wrap}
.inv-sheet{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 8px 30px rgba(15,29,51,.10)}
.inv-head{background:linear-gradient(120deg,#101d3a,#1d3a75);color:#fff;padding:36px 44px;display:flex;justify-content:space-between;align-items:flex-start;position:relative}
.inv-head::after{content:'';position:absolute;bottom:0;left:0;right:0;height:5px;background:linear-gradient(90deg,#4f46e5,#7c3aed,#f59e0b)}
.inv-logo{font-size:32px;font-weight:800;letter-spacing:2px}
.inv-logo span{color:#8b93ff}
.inv-tagline{font-size:11px;letter-spacing:3px;color:#9fb0d4;margin-top:5px}
.inv-titlebox{text-align:right}
.inv-titlebox h2{font-size:27px;letter-spacing:4px}
.inv-num{color:#8b93ff;font-weight:700;margin-top:6px}
.inv-meta{display:flex;justify-content:space-between;padding:28px 44px;gap:24px;border-bottom:1px solid #eef1f7}
.inv-meta h4{font-size:11px;text-transform:uppercase;letter-spacing:1.6px;color:var(--muted);margin-bottom:8px}
.inv-meta .billed b{font-size:16.5px}
.inv-meta .billed p{font-size:13.5px;color:#4b5a75;line-height:1.65;margin-top:4px}
.inv-meta .dates{text-align:right;font-size:13.8px;line-height:2;white-space:nowrap}
.inv-meta .dates span{color:var(--muted)}
.inv-items{padding:8px 44px 0}
.inv-items table{width:100%;border-collapse:collapse}
.inv-items th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:1.4px;color:var(--muted);padding:12px 8px;border-bottom:2px solid #101d3a}
.inv-items td{padding:17px 8px;border-bottom:1px solid #eef1f7;font-size:14px;vertical-align:top}
.inv-items .d b{display:block;margin-bottom:3px}
.inv-items .d small{color:var(--muted);font-size:12.5px}
.inv-totals{display:flex;justify-content:space-between;gap:28px;padding:24px 44px}
.inv-paybox{background:#f6f8fd;border:1px solid #e3e9f6;border-radius:12px;padding:16px 18px;font-size:13px;line-height:1.9;flex:1;color:#3c4a65}
.inv-paybox h4{font-size:11px;text-transform:uppercase;letter-spacing:1.6px;color:var(--muted);margin-bottom:6px}
.inv-sum{width:290px;font-size:14px}
.inv-sum .ln{display:flex;justify-content:space-between;padding:7px 0;color:#4b5a75}
.inv-sum .ln.tt{font-size:17px;font-weight:800;color:#101d3a;border-top:2px solid #101d3a;margin-top:6px;padding-top:12px}
.inv-bal{margin-top:12px;border-radius:10px;padding:13px 15px;display:flex;justify-content:space-between;font-weight:800;font-size:15px}
.inv-bal.due{background:#fff4e0;border:1.5px dashed #e8a13d;color:#92580a}
.inv-bal.clear{background:#e8f7ee;border:1.5px dashed #34b06a;color:#166534}
.inv-thanks{margin:4px 44px 0;background:linear-gradient(120deg,#101d3a,#23407f);border-radius:12px;color:#fff;text-align:center;padding:24px 20px}
.inv-thanks h3{font-size:17.5px;margin-bottom:5px}
.inv-thanks p{font-size:13px;color:#b9c6e4}
.inv-foot{padding:24px 44px 32px;text-align:center;font-size:12px;color:var(--muted);line-height:1.8}

/* ---------- Misc ---------- */
.mt{margin-top:14px}.mb{margin-bottom:14px}
.muted{color:var(--muted)}.small{font-size:12.5px}
.flex-between{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.searchbar{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.searchbar .input{max-width:320px}
.preview-note{position:fixed;bottom:14px;right:14px;background:#101d3a;color:#c6d2e8;font-size:11px;padding:6px 14px;border-radius:20px;z-index:99}

@media(max-width:1080px){
  .kpis{grid-template-columns:1fr 1fr}
  .grid-2,.grid-3{grid-template-columns:1fr}
}
@media(max-width:820px){
  .sidebar{transform:translateX(-100%)}
  .main{margin-left:0;padding:0 16px 40px}
  .kpis{grid-template-columns:1fr}
  .form-grid{grid-template-columns:1fr}
  .inv-head,.inv-meta,.inv-totals{padding-left:24px;padding-right:24px;flex-direction:column}
  .inv-meta .dates{text-align:left}
  .inv-items{padding-left:14px;padding-right:14px}
  .inv-thanks{margin-left:24px;margin-right:24px}
}

@media print{
  .sidebar,.topbar,.inv-actions,.preview-note,.no-print{display:none !important}
  .main{margin:0;padding:0;max-width:100%}
  body{background:#fff}
  .inv-wrap{max-width:100%}
  .inv-sheet{box-shadow:none}
  .card{box-shadow:none;border:1px solid #ddd}
}

</style>
</head>
<body>

<aside class="sidebar">
  <div class="brand">
    <div class="brand-mark">F</div>
    <div>
      <div class="brand-name"><?php echo esc(strtoupper(setting('agency_name', 'FLOWORK'))); ?></div>
      <div class="brand-tag"><?php echo esc(strtoupper(setting('tagline', 'DIGITAL MARKETING AGENCY'))); ?></div>
    </div>
  </div>
  <nav class="nav">
    <?php foreach ($nav as $n): ?>
      <?php if (!$n[4]) continue; ?>
      <?php if ($n[1] === null): ?>
        <div class="nav-label"><?php echo esc($n[3]); ?></div>
      <?php else: ?>
        <a href="<?php echo $n[1]; ?>" class="<?php echo $active === $n[0] ? 'active' : ''; ?>"><span class="ni"><?php echo $n[2]; ?></span> <?php echo esc($n[3]); ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <div class="side-user">
    <div class="av"><?php echo esc(strtoupper(substr($user['name'], 0, 1))); ?></div>
    <div><b><?php echo esc($user['name']); ?></b><small><?php echo $admin ? 'Admin' : esc($user['designation'] ?: 'Staff'); ?></small></div>
    <a href="logout.php" title="Logout">⏻</a>
  </div>
</aside>

<main class="main">
  <div class="topbar no-print">
    <div>
      <div class="page-title"><?php echo esc($page_title); ?></div>
      <?php if ($page_sub): ?><div class="page-sub"><?php echo esc($page_sub); ?></div><?php endif; ?>
    </div>
    <div class="top-right">
      <?php
      $od = (int)qrow("SELECT COUNT(*) c FROM invoices WHERE status != 'paid' AND due_date IS NOT NULL AND due_date < CURDATE()")['c'];
      ?>
      <a class="icon-btn" href="invoices.php" title="Overdue invoices" style="text-decoration:none">🔔<?php if ($od > 0): ?><span class="dot"></span><?php endif; ?></a>
      <div class="top-avatar"><?php echo esc(strtoupper(substr($user['name'], 0, 1))); ?></div>
    </div>
  </div>

  <?php echo show_flash(); ?>
