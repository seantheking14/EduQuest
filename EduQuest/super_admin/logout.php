<?php
/**
 * Super Admin Logout
 */
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
session_start();
session_unset();
session_destroy();
header('Location: login.php?logged_out=1');
exit;
