<?php
session_start();
require '../../config/db.php';
require '../../src/helpers/audit.php';

// Auth protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $companyId = (int) $_POST['company_id'];
    $name      = trim($_POST['name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $role      = trim($_POST['role']);
    $status    = trim($_POST['status']);
    $password  = $_POST['password'];

    // Timestamps
    $createdAt = date('Y-m-d H:i:s');
    $updatedAt = $createdAt;
    $deletedAt = null;

    // Basic Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "Name, Email, and Password are required.";
        header("Location: company-details.php?id=" . $companyId);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email exists.";
        header("Location: company-details.php?id=" . $companyId);
        exit;
    }

    // Handle Image Upload
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $uploadDir = '../../uploads/users/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $_SESSION['error'] = "Failed to create upload directory.";
                    header("Location: company-details.php?id=" . $companyId);
                    exit;
                }
            }

            $newFilename = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $imageName = $newFilename;
            } else {
                $_SESSION['error'] = "Failed to upload image.";
                header("Location: company-details.php?id=" . $companyId);
                exit;
            }
        } else {
            $_SESSION['error'] = "Invalid image format. Allowed: jpg, jpeg, png, gif, webp";
            header("Location: company-details.php?id=" . $companyId);
            exit;
        }
    }

    // Hash Password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Insert User
        $sql = "INSERT INTO users 
                (company_id, name, email, phone, role, status, image, password, text_password, created_at, updated_at, deleted_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $companyId,
            $name,
            $email,
            $phone,
            $role,
            $status,
            $imageName,
            $hashedPassword,
            $password,      // Storing plain text password as requested
            $createdAt,
            $updatedAt,
            $deletedAt
        ]);

        $_SESSION['success'] = "User added successfully.";
    } catch (PDOException $e) {
        // Remove uploaded image if DB insert fails to avoid orphans
        if ($imageName && file_exists('uploads/users/' . $imageName)) {
            unlink('uploads/users/' . $imageName);
        }
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }

    header("Location: ../../dashboard/company-details.php?id=" . $companyId);
    exit;
} else {
    header("Location: ../../dashboard/index.php");
    exit;
}
