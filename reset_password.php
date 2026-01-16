<?php
session_start();
require 'config/db.php';

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare(
    "SELECT id FROM admins WHERE reset_token=? AND reset_expires > NOW()"
);
$stmt->execute([$token]);

if ($stmt->rowCount() === 0) {
    die("Invalid or expired reset link.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>

<form action="actions/auth/reset_password.php" method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
    <input type="password" name="password" placeholder="New Password" required>
    <button>Reset Password</button>
</form>

</body>
</html>
