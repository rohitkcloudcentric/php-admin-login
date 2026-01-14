<?php
session_start();
require '../config/db.php';
require '../helpers/audit.php';
require '../auth.php';

$pageTitle = "All Users";
$activeMenu = "users";

// Fetch all users with company details
$sql = "
    SELECT u.*, c.name as company_name 
    FROM users u 
    LEFT JOIN companies c ON u.company_id = c.id 
    WHERE u.deleted_at IS NULL 
    ORDER BY u.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

require '../layouts/app.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="container py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">All Users</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="ti ti-plus me-1"></i> Add User
                </button>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= $user['image'] ? 'uploads/users/' . htmlspecialchars($user['image']) : 'uploads/users/default-user.jpg'; ?>"
                                                    class="rounded-circle object-fit-cover" width="35" height="35" alt="User">
                                                <div class="fw-semibold"><?= htmlspecialchars($user['name']); ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <a href="company-details.php?id=<?= $user['company_id']; ?>">
                                                <?= htmlspecialchars($user['company_name'] ?? 'N/A'); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] == 'admin' ? 'primary' : 'secondary'; ?>">
                                                <?= ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                <?= ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="user-details.php?id=<?= $user['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="user-details.php?id=<?= $user['id']; ?>&edit=1" class="btn btn-sm btn-outline-warning">Edit</a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteUser(<?= $user['id']; ?>, 0, '<?= htmlspecialchars($user['name']); ?>')">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
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
            <form action="add_user_process.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select" required>
                            <option value="">Select Company</option>
                            <?php
                            $compStmt = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name ASC");
                            while ($row = $compStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value=\"{$row['id']}\">" . htmlspecialchars($row['name']) . "</option>";
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
                <form action="delete_user_process.php" method="POST">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="company_id" id="deleteUserCompanyId" value="0">
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