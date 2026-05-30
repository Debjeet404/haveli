<?php
// admin/logout.php
require_once dirname(__DIR__) . '/../includes/config.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
redirect(BASE_URL . '/admin/login.php');
