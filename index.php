<?php
// Flowork Agency Software - entry point
define('FW_APP', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) { redirect('dashboard.php'); }
redirect('login.php');
