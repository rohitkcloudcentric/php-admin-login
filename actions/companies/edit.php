<?php
session_start();

require '../../config/db.php';
require '../../src/helpers/audit.php';

// ✅ Validate request
if (!isset($_POST['company_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
    exit;
}

$companyId = (int) $_POST['company_id'];
$name      = trim($_POST['name']);
$email     = trim($_POST['email']);
$phone     = trim($_POST['phone']);
$address   = trim($_POST['address']);

$imagePath = null;

/* ===============================
   Image Upload (Optional)
================================ */
if (!empty($_FILES['image']['name'])) {

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid image format'
        ]);
        exit;
    }

    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Image size must be under 2MB'
        ]);
        exit;
    }

    $imageName = 'company_' . time() . '.' . $ext;
    $uploadDir = '../../uploads/companies/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    $imagePath = $imageName; // Store only filename to match users behavior or store relative path?
}

/* ===============================
   Update Query
================================ */
if ($imagePath) {
    $stmt = $pdo->prepare("
        UPDATE companies
        SET name=?, email=?, phone=?, address=?, image=?
        WHERE id=? AND deleted_at IS NULL
    ");
    $stmt->execute([$name, $email, $phone, $address, $imagePath, $companyId]);
} else {
    $stmt = $pdo->prepare("
        UPDATE companies
        SET name=?, email=?, phone=?, address=?
        WHERE id=? AND deleted_at IS NULL
    ");
    $stmt->execute([$name, $email, $phone, $address, $companyId]);
}

/* ===============================
   Audit Log
================================ */
auditLog(
    $pdo,
    $_SESSION['admin_id'],
    'update',
    'company',
    $companyId,
    'Company updated'
);

/* ===============================
   Response
================================ */
echo json_encode([
    'status' => 'success',
    'message' => 'Company updated successfully'
]);
exit;
