<?php
// admin/index.php - Primary redirect for the admin directory
require_once 'auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>
