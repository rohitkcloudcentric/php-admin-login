<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php
session_start();
require '../config/db.php';
require '../src/helpers/audit.php';

/* Auth protection */
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
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
require '../includes/auth_check.php';

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
                    <img src="<?= $company['image'] ? '../uploads/companies/' . htmlspecialchars($company['image']) : '../assets/images/company-default.jpg'; ?>"
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
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">+ Add User</button>
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

                            <!-- User Row (Placeholder for now, later to be dynamic) -->
                            <?php
                            // Fetch users for this company
                            $userStmt = $pdo->prepare("SELECT * FROM users WHERE company_id = ? AND deleted_at IS NULL");
                            $userStmt->execute([$companyId]);
                            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

                            if (count($users) > 0):
                                foreach ($users as $user):
                            ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar bg-light d-flex align-items-center justify-content-center text-primary fw-bold">
                                                    <img src="<?= $user['image'] ? '../uploads/users/' . htmlspecialchars($user['image']) : '../uploads/users/default-user.jpg'; ?>"
                                                        class="rounded-circle" width="40" height="40">
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($user['name']); ?></div>
                                                    <small class="text-muted d-block d-md-none"><?= htmlspecialchars($user['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="desktop-only"><?= htmlspecialchars($user['email']); ?></td>
                                        <td class="desktop-only"><?= htmlspecialchars($user['phone'] ?? '-'); ?></td>

                                        <td>
                                            <?php if ($user['role'] == 'admin'): ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">User</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-success">Active</span>
                                        </td>

                                        <td class="text-end">
                                            <a href="user-details.php?id=<?= $user['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="user-details.php?id=<?= $user['id']; ?>&edit=1" class="btn btn-sm btn-outline-warning">Edit</a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteUser(<?= $user['id']; ?>, <?= $companyId; ?>, '<?= htmlspecialchars($user['name']); ?>')">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No users found for this company.</td>
                                </tr>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../actions/users/add.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <?php
                            $compStmt = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name ASC");
                            while ($row = $compStmt->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($row['id'] == $companyId) ? 'selected' : '';
                                echo "<option value=\"{$row['id']}\" $selected>" . htmlspecialchars($row['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Profile Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="../actions/users/delete.php" method="POST">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="company_id" id="deleteUserCompanyId">
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDeleteUser(userId, companyId, userName) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserCompanyId').value = companyId;
        document.getElementById('deleteUserName').innerText = userName;
        new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
    }
</script>
