<?php
// Start the session
session_start();

// Set admin session variables
$_SESSION['user_id'] = 1;
$_SESSION['is_admin'] = true;
$_SESSION['username'] = 'admin';

// Redirect to the fault reports page
header('Location: /waterbill/frontend/admin/fault-reports.html');
exit;
?>
