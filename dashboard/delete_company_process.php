<?php
session_start();
require '../config/db.php';
require '../helpers/audit.php';

$companyId = (int) $_POST['company_id'];
$adminId  = $_SESSION['admin_id'];

$stmt = $pdo->prepare("
    UPDATE companies 
    SET deleted_at = NOW() 
    WHERE id = ?
");
$stmt->execute([$companyId]);

auditLog(
    $pdo,
    $adminId,
    'soft_delete',
    'company',
    $companyId,
    'Company soft deleted'
);

echo json_encode([
    'status' => 'success',
    'message' => 'Company deleted (soft delete)'
]);
exit;