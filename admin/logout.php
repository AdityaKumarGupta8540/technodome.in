<?php
// admin/logout.php - Admin logout for Technodome
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
