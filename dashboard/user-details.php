<?php
session_start();
require '../config/db.php';
require '../src/helpers/audit.php';
require '../includes/auth_check.php';

$pageTitle = "User Details";
$activeMenu = "companies"; // Highlight companies logic or create new menu

// Get User ID
$userId = (int) ($_GET['id'] ?? 0);
if ($userId <= 0) {
    die("Invalid User ID");
}

// Fetch User
$stmt = $pdo->prepare("
    SELECT u.*, c.name as company_name 
    FROM users u 
    LEFT JOIN companies c ON u.company_id = c.id 
    WHERE u.id = ? AND u.deleted_at IS NULL
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found");
}

require '../layouts/app.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="container py-4">

            <div class="mb-3">
                <a href="company-details.php?id=<?= $user['company_id']; ?>" class="btn btn-outline-secondary btn-sm">
                    &larr; Back to Company
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-4">
                        <div class="flex-shrink-0">
                            <img src="<?= $user['image'] ? '../uploads/users/' . htmlspecialchars($user['image']) : '../uploads/users/default-user.jpg'; ?>"
                                class="rounded-circle object-fit-cover" width="100" height="100" alt="User Image">
                        </div>
                        <div class="flex-grow-1">
                            <h3 class="mb-1 fw-bold"><?= htmlspecialchars($user['name']); ?></h3>
                            <div class="mb-2">
                                <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                    <?= ucfirst($user['status']); ?>
                                </span>
                                <span class="badge bg-info ms-2"><?= ucfirst($user['role']); ?></span>
                            </div>
                            <div class="text-muted">
                                <i class="ti ti-mail me-1"></i> <?= htmlspecialchars($user['email']); ?>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal">
                                <i class="ti ti-edit me-1"></i> Edit User
                            </button>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                <i class="ti ti-trash me-1"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Personal Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Full Name</div>
                                <div class="col-sm-9"><?= htmlspecialchars($user['name']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Email</div>
                                <div class="col-sm-9"><?= htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Phone</div>
                                <div class="col-sm-9"><?= htmlspecialchars($user['phone'] ?? '-'); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Company</div>
                                <div class="col-sm-9">
                                    <a href="company-details.php?id=<?= $user['company_id']; ?>">
                                        <?= htmlspecialchars($user['company_name'] ?? 'Unknown'); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-3 fw-bold">Joined Date</div>
                                <div class="col-sm-9"><?= date('F j, Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../actions/users/edit.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($user['image'] ?? ''); ?>">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <?php
                            $compStmt = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name ASC");
                            while ($row = $compStmt->fetch(PDO::FETCH_ASSOC)) {
                                $selected = ($row['id'] == $user['company_id']) ? 'selected' : '';
                                echo "<option value=\"{$row['id']}\" $selected>" . htmlspecialchars($row['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="user" <?= $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Profile Image</label>
                        <?php if ($user['image']): ?>
                            <div class="mb-2">
                                <img src="../uploads/users/<?= htmlspecialchars($user['image']); ?>" width="50" class="rounded">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Leave empty to keep existing image</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <small class="text-muted">(Optional)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password to change">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
                <p>Are you sure you want to delete <strong><?= htmlspecialchars($user['name']); ?></strong>?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="../actions/users/delete.php" method="POST">
                    <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                    <input type="hidden" name="company_id" value="<?= $user['company_id']; ?>">
                    <button type="submit" class="btn btn-danger">Confirm Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('edit')) {
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
    });
</script>
