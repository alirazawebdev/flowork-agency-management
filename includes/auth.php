<?php
// ============================================================
// Flowork Agency Software - Login / session / permissions
// ============================================================

if (!defined('FW_APP')) { die('Access denied.'); }

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function current_user() {
  if (empty($_SESSION['fw_uid'])) { return null; }
  static $u = null;
  if ($u === null) {
    $u = qrow("SELECT * FROM users WHERE id = ?", [(int)$_SESSION['fw_uid']]);
    if (!$u) { unset($_SESSION['fw_uid']); }
  }
  return $u;
}

function is_admin() {
  $u = current_user();
  return $u && $u['role'] === 'admin';
}

function require_login() {
  if (!current_user()) {
    redirect('login.php');
  }
}

function require_admin() {
  require_login();
  if (!is_admin()) {
    http_response_code(403);
    die('Access denied. Ye page sirf admin ke liye hai.');
  }
}

function do_login($user) {
  session_regenerate_id(true);
  $_SESSION['fw_uid'] = (int)$user['id'];
}

function do_logout() {
  unset($_SESSION['fw_uid']);
  session_regenerate_id(true);
}
