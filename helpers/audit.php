<?php
function auditLog($pdo, $adminId, $action, $entity, $entityId, $description)
{
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs 
        (admin_id, action, entity, entity_id, description, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $adminId,
        $action,
        $entity,
        $entityId,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? 'CLI'
    ]);
}
