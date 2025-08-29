<?php
require_once 'includes/functions.php';

// Destroy session
session_start();
session_unset();
session_destroy();

// Remove remember me cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Redirect to home page
header('Location: index.php?logged_out=1');
exit();
?>
