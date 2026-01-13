<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name       = trim($_POST['name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $pass       = $_POST['password'];

    // 1️⃣ Required fields validation
    if (!$name || !$last_name || !$email || !$pass) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: register.php");
        exit;
    }

    // 2️⃣ Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email address.";
        header("Location: register.php");
        exit;
    }

    // 3️⃣ Check duplicate email
    $check = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        $_SESSION['error'] = "Email address already exists.";
        header("Location: register.php");
        exit;
    }

    // 4️⃣ Hash password
    $hashed = password_hash($pass, PASSWORD_BCRYPT);

    // 5️⃣ Insert admin
    $stmt = $pdo->prepare(
        "INSERT INTO admins (name, last_name, email, password, Text_Password)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $last_name, $email, $hashed, $pass]);

    $_SESSION['success'] = "Admin created successfully.";
    header("Location: index.php");
    exit;
}