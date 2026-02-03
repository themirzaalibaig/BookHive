<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;

$auth = new Auth();
$auth->logout();

flash('success', 'You have been logged out');
redirect('login.php');
