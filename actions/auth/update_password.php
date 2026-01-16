<?php
session_start();
require '../../config/db.php';

/* Auth check */
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $adminId = $_SESSION['admin_id'];

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    /* 1️⃣ Validate empty fields */
    if ($current === '' || $new === '' || $confirm === '') {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../../dashboard/profile.php");
        exit;
    }

    /* 2️⃣ Password length check */
    if (strlen($new) < 6) {
        $_SESSION['error'] = "New password must be at least 6 characters.";
        header("Location: ../../dashboard/profile.php");
        exit;
    }

    /* 3️⃣ Match new & confirm password */
    if ($new !== $confirm) {
        $_SESSION['error'] = "New password and confirm password do not match.";
        header("Location: ../../dashboard/profile.php");
        exit;
    }

    /* 4️⃣ Fetch current password hash */
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($current, $admin['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: ../../dashboard/profile.php");
        exit;
    }

    /* 5️⃣ Update password */
    $newHash = password_hash($new, PASSWORD_BCRYPT);

    $update = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $update->execute([$newHash, $adminId]);

    /* 6️⃣ Optional: regenerate session */
    session_regenerate_id(true);

    $_SESSION['success'] = "Password updated successfully.";
    header("Location: dashboard/profile.php");
    exit;
}
