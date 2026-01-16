<?php
// session_start();

/* No cache */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Pragma: no-cache");

/* Check login */
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
