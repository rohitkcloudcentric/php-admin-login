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
    $userId    = (int) $_POST['user_id'];
    $companyId = (int) $_POST['company_id'];
    $name      = trim($_POST['name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $role      = trim($_POST['role']);
    $status    = trim($_POST['status']);
    $password  = $_POST['password'];
    $existingImage = $_POST['existing_image'];

    // Timestamps
    $updatedAt = date('Y-m-d H:i:s');

    // Basic Validation
    if (empty($name) || empty($email)) {
        $_SESSION['error'] = "Name and Email are required.";
        header("Location: user-details.php?id=" . $userId);
        exit;
    }

    // Check if email already exists (for other users)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? AND deleted_at IS NULL");
    $stmt->execute([$email, $userId]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Email already exists for another user.";
        header("Location: user-details.php?id=" . $userId);
        exit;
    }

    // Handle Image Upload
    $imageName = $existingImage;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $uploadDir = '../../uploads/users/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFilename = uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                // Delete old image if exists
                if ($existingImage && file_exists($uploadDir . $existingImage)) {
                    unlink($uploadDir . $existingImage);
                }
                $imageName = $newFilename;
            } else {
                $_SESSION['error'] = "Failed to upload image.";
                header("Location: user-details.php?id=" . $userId);
                exit;
            }
        } else {
            $_SESSION['error'] = "Invalid image format.";
            header("Location: user-details.php?id=" . $userId);
            exit;
        }
    }

    try {
        // Prepare Update SQL
        $sql = "UPDATE users SET 
                company_id = ?, 
                name = ?, 
                email = ?, 
                phone = ?, 
                role = ?, 
                status = ?, 
                image = ?, 
                updated_at = ?";

        $params = [$companyId, $name, $email, $phone, $role, $status, $imageName, $updatedAt];

        // Only update password if provided
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $sql .= ", password = ?, text_password = ?";
            $params[] = $hashedPassword;
            $params[] = $password;
        }

        $sql .= " WHERE id = ?";
        $params[] = $userId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['success'] = "User updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }

    header("Location: ../../dashboard/user-details.php?id=" . $userId);
    exit;
} else {
    header("Location: ../../dashboard/index.php");
    exit;
}
