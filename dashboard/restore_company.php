<?php
session_start();
require 'config/db.php';
require 'helpers/audit.php';

$id = (int) $_POST['company_id'];
$adminId = $_SESSION['admin_id'];

$pdo->prepare("
    UPDATE companies SET deleted_at = NULL WHERE id = ?
")->execute([$id]);

auditLog(
    $pdo,
    $adminId,
    'restore',
    'company',
    $id,
    'Company restored'
);

echo json_encode([
    'status' => 'success',
    'message' => 'Company restored successfully'
]);
exit;