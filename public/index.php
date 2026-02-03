<?php
// Entry point - redirect to login or dashboard
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;

$auth = new Auth();

if ($auth->check()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
