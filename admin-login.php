<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    /* 1️⃣ Empty field validation */
    if ($email === '' || $pass === '') {
        $_SESSION['error'] = "Email and password are required.";
        header("Location: index.php");
        exit;
    }

    /* 2️⃣ Email format validation */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header("Location: index.php");
        exit;
    }

    /* 3️⃣ Fetch admin */
    $stmt = $pdo->prepare("SELECT id, name, password FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    /* 4️⃣ Validate credentials */
    if (!$admin || !password_verify($pass, $admin['password'])) {
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: index.php");
        exit;
    }

    /* 5️⃣ Login success */
    session_regenerate_id(true);

    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_last_name'] = $admin['last_name'];
    // $_SESSION['admin_phone'] = $admin['phone'];
    // $_SESSION['admin_address'] = $admin['address'];



    $_SESSION['success'] = "Welcome back, {$admin['name']}!";

    header("Location: dashboard/index.php");
    exit;
}