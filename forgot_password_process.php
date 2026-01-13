<?php
session_start();
require 'config/db.php';

$email = trim($_POST['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email address.";
    header("Location: forgot_password.php");
    exit;
}

/* Check email */
$stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() === 0) {
    $_SESSION['error'] = "Email not registered.";
    header("Location: forgot_password.php");
    exit;
}

/* Generate secure token */
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

/* Save token */
$update = $pdo->prepare(
    "UPDATE admins SET reset_token=?, reset_expires=? WHERE email=?"
);
$update->execute([$token, $expires, $email]);

/* Reset link */
$resetLink = "/reset_password.php?token=" . $token;

/* Send email (basic) */
mail($email, "Password Reset", "Reset your password: $resetLink");

$_SESSION['success'] = "Password reset link sent to your email.";
header("Location: forgot_password.php");
exit;