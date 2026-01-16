<?php
session_start();
require '../config/db.php';
require '../src/helpers/audit.php';
require '../includes/auth_check.php';

$pageTitle = "All Users";
$activeMenu = "users";

// Get Filter Parameters
$range = $_GET['range'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$filterRole = $_GET['role'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterCompany = $_GET['company_id'] ?? '';

$whereSQL = " u.deleted_at IS NULL ";
$params = [];

// Date Filter
if ($range === 'today') {
    $whereSQL .= " AND DATE(u.created_at) = CURDATE() ";
} elseif ($range === 'week') {
    $whereSQL .= " AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ";
} elseif ($range === 'month') {
    $whereSQL .= " AND u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ";
} elseif ($range === 'custom' && !empty($startDate) && !empty($endDate)) {
    $whereSQL .= " AND DATE(u.created_at) BETWEEN ? AND ? ";
    $params[] = $startDate;
    $params[] = $endDate;
}

// Role Filter
if (!empty($filterRole)) {
    $whereSQL .= " AND u.role = ? ";
    $params[] = $filterRole;
}

// Status Filter
if (!empty($filterStatus)) {
    $whereSQL .= " AND u.status = ? ";
    $params[] = $filterStatus;
}

// Company Filter
if (!empty($filterCompany)) {
    $whereSQL .= " AND u.company_id = ? ";
    $params[] = $filterCompany;
}

// Fetch all users with company details
$sql = "
    SELECT u.*, c.name as company_name 
    FROM users u 
    LEFT JOIN companies c ON u.company_id = c.id 
    WHERE $whereSQL 
    ORDER BY u.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch companies for the filter
$companies = $pdo->query("SELECT id, name FROM companies WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

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

            <!-- Filter Section -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <!-- Date Range -->
                        <div class="col-md-2">
                            <label class="form-label small text-muted text-uppercase fw-bold">Date Range</label>
                            <select name="range" class="form-select form-select-sm" id="rangeSelect" onchange="toggleCustomDates()">
                                <option value="all" <?= $range === 'all' ? 'selected' : '' ?>>All Time</option>
                                <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>This Week</option>
                                <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>This Month</option>
                                <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                            </select>
                        </div>

                        <!-- Custom Dates -->
                        <div class="col-md-3 custom-date-inputs" style="display: <?= $range === 'custom' ? 'block' : 'none' ?>;">
                            <label class="form-label small text-muted text-uppercase fw-bold">Custom Period</label>
                            <div class="d-flex gap-1">
                                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
                                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="col-md-2">
                            <label class="form-label small text-muted text-uppercase fw-bold">Role</label>
                            <select name="role" class="form-select form-select-sm">
                                <option value="">All Roles</option>
                                <option value="user" <?= $filterRole === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-2">
                            <label class="form-label small text-muted text-uppercase fw-bold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Status</option>
                                <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <!-- Company -->
                        <div class="col-md-2">
                            <label class="form-label small text-muted text-uppercase fw-bold">Company</label>
                            <select name="company_id" class="form-select form-select-sm">
                                <option value="">All Companies</option>
                                <?php foreach ($companies as $comp): ?>
                                    <option value="<?= $comp['id'] ?>" <?= (string)$filterCompany === (string)$comp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($comp['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                        </div>
                        <?php if ($range !== 'all' || !empty($filterRole) || !empty($filterStatus) || !empty($filterCompany)): ?>
                            <div class="col-md-1 d-flex align-items-end">
                                <a href="users.php" class="btn btn-light-secondary btn-sm w-100">Reset</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
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

            <div class="card shadow-sm border-0">
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
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= $user['image'] ? '../uploads/users/' . htmlspecialchars($user['image']) : '../uploads/users/default-user.jpg'; ?>"
                                                    class="rounded-circle object-fit-cover" width="40" height="40" alt="User">
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($user['name']); ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <a href="company-details.php?id=<?= $user['company_id']; ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($user['company_name'] ?? 'N/A'); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-<?= $user['role'] == 'admin' ? 'primary text-primary' : 'secondary text-secondary'; ?> py-1 px-2">
                                                <?= ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'warning'; ?> py-1 px-2">
                                                <?= ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="user-details.php?id=<?= $user['id']; ?>" class="btn btn-sm btn-light-primary"><i class="ti ti-eye"></i></a>
                                            <a href="user-details.php?id=<?= $user['id']; ?>&edit=1" class="btn btn-sm btn-light-warning"><i class="ti ti-edit"></i></a>
                                            <button class="btn btn-sm btn-light-danger" onclick="confirmDeleteUser(<?= $user['id']; ?>, 0, '<?= htmlspecialchars($user['name']); ?>')"><i class="ti ti-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="ti ti-user-off fs-1 d-block mb-2"></i>
                                        No users match the selected filters.
                                    </td>
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
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="../actions/users/add.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select" required>
                            <option value="">Select Company</option>
                            <?php foreach ($companies as $comp): ?>
                                <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 234 567 890">
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
                <div class="modal-footer border-0">
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
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p class="text-muted small mb-0">This action will soft-delete the user from the system.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="../actions/users/delete.php" method="POST">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <input type="hidden" name="company_id" id="deleteUserCompanyId" value="0">
                    <button type="submit" class="btn btn-danger px-4">Confirm Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleCustomDates() {
        const range = document.getElementById('rangeSelect').value;
        const customInputs = document.querySelector('.custom-date-inputs');
        customInputs.style.display = (range === 'custom') ? 'block' : 'none';
    }

    function confirmDeleteUser(userId, companyId, userName) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserCompanyId').value = companyId;
        document.getElementById('deleteUserName').innerText = userName;
        new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
    }
</script>