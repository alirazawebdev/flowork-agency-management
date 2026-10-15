<?php
// ============================================================
// Flowork Agency Software - Database connection + query helpers
// ============================================================

if (!defined('FW_APP')) { die('Access denied.'); }

function fw_config() {
  static $cfg = null;
  if ($cfg === null) {
    $file = __DIR__ . '/../config/config.php';
    if (!file_exists($file)) {
      // Installer par bhejo (lekin install.php khud yahan nahi aayega)
      $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
      header('Location: ' . $dir . '/install.php');
      exit;
    }
    $cfg = include $file;
  }
  return $cfg;
}

function db() {
  static $db = null;
  if ($db === null) {
    $c = fw_config();
    $db = @new mysqli($c['host'], $c['user'], $c['pass'], $c['name']);
    if ($db->connect_error) {
      die('Database connection failed. config/config.php check karein ya dobara install.php chalayein. (' . htmlspecialchars($db->connect_error) . ')');
    }
    $db->set_charset('utf8mb4');
  }
  return $db;
}

// Prepared-statement helper. SELECT ho ya INSERT/UPDATE — sab ke liye.
function q($sql, $params = []) {
  $db = db();
  if (empty($params)) {
    $res = $db->query($sql);
    if ($res === false) { die('Database error: ' . htmlspecialchars($db->error)); }
    return $res;
  }
  $stmt = $db->prepare($sql);
  if (!$stmt) { die('Database error: ' . htmlspecialchars($db->error)); }
  $types = '';
  foreach ($params as $p) {
    $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
  }
  // bind_param ko references chahiye hotay hain
  $refs = [];
  foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
  $stmt->bind_param($types, ...$refs);
  if (!$stmt->execute()) { die('Database error: ' . htmlspecialchars($stmt->error)); }
  $res = $stmt->get_result();
  $stmt->close();
  return $res;
}

function qrow($sql, $params = []) {
  $r = q($sql, $params);
  return ($r instanceof mysqli_result) ? $r->fetch_assoc() : null;
}

function qall($sql, $params = []) {
  $r = q($sql, $params);
  $out = [];
  if ($r instanceof mysqli_result) {
    while ($row = $r->fetch_assoc()) { $out[] = $row; }
  }
  return $out;
}

// INSERT ke baad nayi id deta hai
function qins($sql, $params = []) {
  q($sql, $params);
  return db()->insert_id;
}
