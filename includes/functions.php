<?php
// ============================================================
// Flowork Agency Software - Common helpers
// ============================================================

if (!defined('FW_APP')) { die('Access denied.'); }

// ---------- URLs / output ----------
function base_url() {
  $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
  return $proto . '://' . $_SERVER['HTTP_HOST'] . $dir;
}

function redirect($page) {
  $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
  header('Location: ' . $dir . '/' . ltrim($page, '/'));
  exit;
}

function esc($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function money($n) {
  return setting('currency', 'Rs') . ' ' . number_format((float)$n, 0);
}

function dmy($d) {
  if (!$d || $d === '0000-00-00') { return '—'; }
  return date('d M Y', strtotime($d));
}

function today() { return date('Y-m-d'); }

// ---------- Settings ----------
function setting($key, $def = '') {
  static $cache = [];
  if (!array_key_exists($key, $cache)) {
    $r = qrow("SELECT svalue FROM settings WHERE skey = ?", [$key]);
    $cache[$key] = $r ? $r['svalue'] : $def;
  }
  return $cache[$key];
}

function save_setting($key, $value) {
  q("INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)", [$key, (string)$value]);
}

// ---------- Invoice numbers: PREFIX-YEAR-0001 ----------
function next_invoice_no() {
  $prefix = setting('invoice_prefix', 'INV');
  $n = (int)setting('invoice_next', '1');
  return $prefix . '-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function bump_invoice_no() {
  $n = (int)setting('invoice_next', '1');
  save_setting('invoice_next', (string)($n + 1));
}

// ---------- Status labels ----------
function valid_project_status($s) {
  $ok = ['new','in_progress','delivered','completed','cancelled'];
  return in_array($s, $ok, true) ? $s : 'new';
}
function project_status_label($s) {
  $m = ['new' => 'New', 'in_progress' => 'In Progress', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
  return isset($m[$s]) ? $m[$s] : $s;
}
function project_status_class($s) {
  $m = ['new' => 'b-info', 'in_progress' => 'b-warn', 'delivered' => 'b-primary', 'completed' => 'b-ok', 'cancelled' => 'b-muted'];
  return isset($m[$s]) ? $m[$s] : 'b-muted';
}

// ---------- Dashboard stats ----------
function dashboard_stats() {
  $ym = date('Y-m');
  $month_earn = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE DATE_FORMAT(payment_date,'%Y-%m') = ?", [$ym])['s'];
  $total_earn = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM payments")['s'];
  $month_exp  = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m') = ?", [$ym])['s'];
  $total_exp  = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM expenses")['s'];
  $pending    = (float)qrow("SELECT COALESCE(SUM(amount - paid_amount),0) s FROM invoices WHERE status != 'paid'")['s']
                + (float)qrow("SELECT COALESCE(SUM(m.amount),0) s FROM milestones m LEFT JOIN invoices i ON i.milestone_id = m.id WHERE m.status = 'unpaid' AND i.id IS NULL")['s'];
  $active     = (int)qrow("SELECT COUNT(*) c FROM projects WHERE status IN ('new','in_progress','delivered')")['c'];
  $clients    = (int)qrow("SELECT COUNT(*) c FROM clients")['c'];
  $overdue    = (int)qrow("SELECT COUNT(*) c FROM invoices WHERE status != 'paid' AND due_date IS NOT NULL AND due_date < CURDATE()")['c'];
  $month_new_clients = (int)qrow("SELECT COUNT(*) c FROM clients WHERE DATE_FORMAT(created_at,'%Y-%m') = ?", [$ym])['c'];
  return [
    'month_earn' => $month_earn,
    'total_earn' => $total_earn,
    'month_exp'  => $month_exp,
    'total_exp'  => $total_exp,
    'net_profit' => $total_earn - $total_exp,
    'month_profit' => $month_earn - $month_exp,
    'pending'    => $pending,
    'active'     => $active,
    'clients'    => $clients,
    'overdue'    => $overdue,
    'month_new_clients' => $month_new_clients,
  ];
}

// Last N months: earnings + expenses per month (chart ke liye)
function monthly_series($n = 6) {
  $labels = []; $earn = []; $exp = [];
  for ($i = $n - 1; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-$i months"));
    $labels[] = date('M', strtotime($ym . '-01'));
    $e = qrow("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE DATE_FORMAT(payment_date,'%Y-%m') = ?", [$ym]);
    $x = qrow("SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m') = ?", [$ym]);
    $earn[] = (float)$e['s'];
    $exp[]  = (float)$x['s'];
  }
  return ['labels' => $labels, 'earn' => $earn, 'exp' => $exp];
}

// ---------- Project stats: collected, expenses, profit, remaining ----------
function project_stats($pid) {
  $p = qrow("SELECT * FROM projects WHERE id = ?", [(int)$pid]);
  if (!$p) { return null; }
  $collected = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE project_id = ?", [(int)$pid])['s'];
  $pexp = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM expenses WHERE project_id = ?", [(int)$pid])['s'];
  $ms = qrow("SELECT COUNT(*) c, SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) paid FROM milestones WHERE project_id = ?", [(int)$pid]);
  return [
    'project'   => $p,
    'collected' => $collected,
    'expenses'  => $pexp,
    'profit'    => $collected - $pexp,
    'remaining' => (float)$p['total_amount'] - $collected,
    'expected_profit' => (float)$p['total_amount'] - $pexp,
    'ms_total'  => (int)$ms['c'],
    'ms_paid'   => (int)$ms['paid'],
  ];
}

// ---------- Client stats ----------
function client_stats($cid) {
  $business = (float)qrow("SELECT COALESCE(SUM(total_amount),0) s FROM projects WHERE client_id = ?", [(int)$cid])['s'];
  $collected = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE client_id = ?", [(int)$cid])['s'];
  $projects = (int)qrow("SELECT COUNT(*) c FROM projects WHERE client_id = ?", [(int)$cid])['c'];
  return ['business' => $business, 'collected' => $collected, 'remaining' => $business - $collected, 'projects' => $projects];
}

// ---------- Invoice helpers ----------
function refresh_invoice_status($invoice_id) {
  $inv = qrow("SELECT * FROM invoices WHERE id = ?", [(int)$invoice_id]);
  if (!$inv) { return; }
  $paid = (float)$inv['paid_amount'];
  $amt = (float)$inv['amount'];
  $status = ($paid >= $amt && $amt > 0) ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
  q("UPDATE invoices SET status = ? WHERE id = ?", [$status, (int)$invoice_id]);
  // Linked milestone paid?
  if ($inv['milestone_id'] && $status === 'paid') {
    q("UPDATE milestones SET status = 'paid', paid_date = CURDATE() WHERE id = ?", [(int)$inv['milestone_id']]);
  }
}

// Payment record karo + invoice/milestone update karo (ek jagah se)
function record_payment($data) {
  // $data: client_id, project_id, invoice_id, amount, method, payment_date, notes
  qins(
    "INSERT INTO payments (client_id, project_id, invoice_id, amount, method, payment_date, notes) VALUES (?,?,?,?,?,?,?)",
    [(int)$data['client_id'], $data['project_id'] ? (int)$data['project_id'] : null, $data['invoice_id'] ? (int)$data['invoice_id'] : null, (float)$data['amount'], $data['method'], $data['payment_date'], $data['notes']]
  );
  if (!empty($data['invoice_id'])) {
    q("UPDATE invoices SET paid_amount = paid_amount + ? WHERE id = ?", [(float)$data['amount'], (int)$data['invoice_id']]);
    refresh_invoice_status((int)$data['invoice_id']);
  }
}

// ---------- Flash messages ----------
function flash($msg, $type = 'ok') {
  $_SESSION['fw_flash'] = ['msg' => $msg, 'type' => $type];
}
function show_flash() {
  if (empty($_SESSION['fw_flash'])) { return ''; }
  $f = $_SESSION['fw_flash'];
  unset($_SESSION['fw_flash']);
  $cls = $f['type'] === 'err' ? 'alert-err' : 'alert-ok';
  return '<div class="alert ' . $cls . '">' . esc($f['msg']) . '</div>';
}
