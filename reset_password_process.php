<?php
session_start();
require 'config/db.php';

$token = $_POST['token'];
$password = $_POST['password'];

if (strlen($password) < 6) {
    die("Password must be at least 6 characters.");
}

$hash = password_hash($password, PASSWORD_BCRYPT);

/* Update password */
$stmt = $pdo->prepare(
    "UPDATE admins 
     SET password=?, reset_token=NULL, reset_expires=NULL 
     WHERE reset_token=?"
);
$stmt->execute([$hash, $token]);

$_SESSION['success'] = "Password reset successful. Please login.";
header("Location: login.php");
exit;
