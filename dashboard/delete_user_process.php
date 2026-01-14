<?php
session_start();
require '../config/db.php';
require '../helpers/audit.php';

// Auth protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $userId = (int) $_POST['user_id'];
    $companyId = (int) $_POST['company_id'];
    $deletedAt = date('Y-m-d H:i:s');

    try {
        // Soft delete the user
        $stmt = $pdo->prepare("UPDATE users SET deleted_at = ?, status = 'inactive' WHERE id = ?");
        $stmt->execute([$deletedAt, $userId]);

        $_SESSION['success'] = "User deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }

    // Redirect back to company details or users list
    if ($companyId > 0) {
        header("Location: company-details.php?id=" . $companyId);
    } else {
        header("Location: users.php");
    }
    exit;
} else {
    header("Location: index.php");
    exit;
}
