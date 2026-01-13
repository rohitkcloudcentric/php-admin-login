<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php
session_start();
require '../config/db.php';
require '../helpers/audit.php';

/* Auth protection */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* Fetch companies */


$companyId = (int) ($_GET['id'] ?? 0);

if ($companyId <= 0) {
    die('Invalid company ID');
}

/* Fetch company details */
$stmt = $pdo->prepare("
    SELECT id, name, email, phone, address, image, created_at
    FROM companies
    WHERE id = ?
      AND deleted_at IS NULL
    LIMIT 1
");

$stmt->execute([$companyId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    die('Company not found');
}

?>

<?php

// Protect admin
require '../auth.php';

// Page variables
$pageTitle = "Company Details";
$activeMenu = "company-details";

// Load layout
require '../layouts/app.php';


?>

<style>
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

@media (max-width: 768px) {
    .desktop-only {
        display: none;
    }
}
</style>



<div class="pc-container">
    <div class="pc-content">
        <div class="container py-4">

            <!-- Company Info -->
            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex align-items-center gap-3">
                    <img src="<?= $company['image'] ? 'uploads/companies/' . htmlspecialchars($company['image']) : '../assets/images/company-default.jpg'; ?>"
                        class="rounded-circle" width="70" height="70">
                    <div>
                        <h5 class="mb-0 fw-bold"><?= htmlspecialchars($company['name']); ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($company['email'] ?? '-'); ?> ·
                            <?= htmlspecialchars($company['phone'] ?? '-'); ?></small>
                        <div class="text-muted"><?= htmlspecialchars($company['address'] ?? '-'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Users Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">Company Users</h5>
                <button class="btn btn-primary btn-sm">+ Add User</button>
            </div>

            <!-- Users Table -->
            <div class="card shadow">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th class="desktop-only">Email</th>
                                <th class="desktop-only">Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <!-- User Row -->
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="https://via.placeholder.com/40" class="avatar">
                                        <div>
                                            <div class="fw-semibold">John Doe</div>
                                            <small class="text-muted">Joined: 12 Jan 2025</small>
                                        </div>
                                    </div>
                                </td>

                                <td class="desktop-only">john@acme.com</td>
                                <td class="desktop-only">+91 98765 43210</td>

                                <td>
                                    <span class="badge bg-info">Admin</span>
                                </td>

                                <td>
                                    <span class="badge bg-success">Active</span>
                                </td>

                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-warning">Edit</button>
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </td>
                            </tr>

                            <!-- Repeat rows dynamically -->

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>