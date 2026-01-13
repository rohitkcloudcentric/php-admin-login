<?php
session_start();

require '../config/db.php';
require '../helpers/audit.php';

header('Content-Type: application/json');

/* ===============================
   Auth Check
================================ */
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

/* ===============================
   Input Validation
================================ */
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($name === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Company name is required'
    ]);
    exit;
}

/* ===============================
   Image Upload (Optional)
================================ */
$imageName = null;

if (!empty($_FILES['image']['name'])) {

    $allowedExt  = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid image format'
        ]);
        exit;
    }

    if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Image must be under 2MB'
        ]);
        exit;
    }

    $uploadDir = '../uploads/companies/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageName = 'company_' . uniqid() . '.' . $ext;

    if (!move_uploaded_file(
        $_FILES['image']['tmp_name'],
        $uploadDir . $imageName
    )) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Image upload failed'
        ]);
        exit;
    }
}

/* ===============================
   Insert Company
================================ */
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO companies (name, email, phone, address, image)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$name, $email, $phone, $address, $imageName]);

    $companyId = $pdo->lastInsertId();

    /* ===============================
       Audit Log
    ================================ */
    auditLog(
        $pdo,
        $_SESSION['admin_id'],
        'create',
        'company',
        $companyId,
        'Company created'
    );

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Company added successfully'
    ]);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();

    echo json_encode([
        'status' => 'error',
        'message' => 'Something went wrong'
    ]);
    exit;
}
