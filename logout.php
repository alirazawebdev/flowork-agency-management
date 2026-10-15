<?php
// Flowork Agency Software - Logout
define('FW_APP', true);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

do_logout();
redirect('login.php');
