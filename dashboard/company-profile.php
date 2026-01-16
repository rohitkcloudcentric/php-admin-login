<?php
session_start();
require '../config/db.php';
require '../includes/auth_check.php';

// Filter Variables
$range = $_GET['range'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$whereSQL = " deleted_at IS NULL ";
$params = [];

if ($range === 'today') {
    $whereSQL .= " AND DATE(created_at) = CURDATE() ";
} elseif ($range === 'week') {
    $whereSQL .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ";
} elseif ($range === 'month') {
    $whereSQL .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ";
} elseif ($range === 'custom' && !empty($startDate) && !empty($endDate)) {
    $whereSQL .= " AND DATE(created_at) BETWEEN ? AND ? ";
    $params[] = $startDate;
    $params[] = $endDate;
}

/* Fetch companies */
$sql = "
    SELECT id, name, email, phone, address, image, created_at
    FROM companies
    WHERE $whereSQL
    ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Company Profile";
$activeMenu = "company-profile";

// Extra JS for custom date toggle
$extraJs = '
<script>
    function toggleCustomDates() {
        const range = document.getElementById("rangeSelect").value;
        document.querySelector(".custom-date-inputs").style.display = (range === "custom") ? "flex" : "none";
    }
</script>';

require '../layouts/app.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="container py-4">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Company List</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                    <i class="ti ti-plus me-1"></i> Add Company
                </button>
            </div>

            <!-- Filter Section -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label class="form-label small text-muted text-uppercase fw-bold mb-1 d-block">Filter Range</label>
                            <select name="range" class="form-select form-select-sm" id="rangeSelect" onchange="toggleCustomDates()">
                                <option value="all" <?= $range === 'all' ? 'selected' : '' ?>>All Time</option>
                                <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                                <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>This Week</option>
                                <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>This Month</option>
                                <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                            </select>
                        </div>

                        <div class="col-auto custom-date-inputs" style="display: <?= $range === 'custom' ? 'flex' : 'none' ?>; gap: 10px; align-items: center;">
                            <div>
                                <label class="small text-muted mb-1 d-block">Start Date</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate) ?>">
                            </div>
                            <span class="mt-4">to</span>
                            <div>
                                <label class="small text-muted mb-1 d-block">End Date</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate) ?>">
                            </div>
                        </div>

                        <div class="col-auto">
                            <label class="d-block mb-1">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                            <?php if ($range !== 'all'): ?>
                                <a href="company-profile.php" class="btn btn-light-secondary btn-sm ms-1">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Logo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($companies)): ?>
                                <?php foreach ($companies as $i => $company): ?>
                                    <tr data-company-id="<?= $company['id'] ?>">
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <img src="<?= $company['image']
                                                            ? '../uploads/companies/' . htmlspecialchars($company['image'])
                                                            : '../assets/images/company-default.jpg'; ?>" width="45" height="45"
                                                class="rounded-circle" style="object-fit:cover;">
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            <a href="company-details.php?id=<?= $company['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($company['name']); ?></a>
                                        </td>
                                        <td><?= htmlspecialchars($company['email'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($company['phone'] ?? '-'); ?></td>
                                        <td style="max-width: 220px;">
                                            <small class="text-muted">
                                                <?= htmlspecialchars($company['address'] ?? '-'); ?>
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light-warning" onclick="openEditCompany(this)"
                                                data-id="<?= $company['id'] ?>"
                                                data-name="<?= htmlspecialchars($company['name']) ?>"
                                                data-email="<?= htmlspecialchars($company['email'] ?? '') ?>"
                                                data-phone="<?= htmlspecialchars($company['phone'] ?? '') ?>"
                                                data-address="<?= htmlspecialchars($company['address'] ?? '') ?>"
                                                data-image="<?= $company['image'] ? '../uploads/companies/' . $company['image'] : '' ?>">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-light-danger" onclick="openDeleteCompany(this)"
                                                data-id="<?= $company['id'] ?>"
                                                data-name="<?= htmlspecialchars($company['name']) ?>">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ti ti-building-off fs-1 d-block mb-2"></i>
                                        No companies found for the selected period.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modals -->
            <!-- Add Company Modal -->
            <div class="modal fade" id="addCompanyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Add Company</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="addCompanyForm" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="Company Ltd." required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" placeholder="info@company.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" placeholder="+1 234 567 890">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Logo</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control" placeholder="Street Address, City, Country"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Company</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Company Modal -->
            <div class="modal fade" id="editCompanyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Edit Company</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="editCompanyForm" enctype="multipart/form-data">
                            <input type="hidden" name="company_id" id="edit_company_id">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" name="name" id="edit_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="edit_email" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" id="edit_phone" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Change Logo</label>
                                        <input type="file" name="image" class="form-control" accept="image/*" onchange="previewEditImage(event)">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" id="edit_address" class="form-control"></textarea>
                                    </div>
                                    <div class="col-12 text-center mt-3">
                                        <img id="edit_image_preview" src="" class="rounded border shadow-sm" style="width:100px;height:100px;object-fit:cover; display:none;">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Company</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Company Modal -->
            <div class="modal fade" id="deleteCompanyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold text-danger">Delete Company</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="deleteCompanyForm">
                            <input type="hidden" name="company_id" id="delete_company_id">
                            <div class="modal-body">
                                <p class="mb-1">Are you sure you want to delete <strong id="delete_company_name"></strong>?</p>
                                <p class="text-muted small">This will move the company to the recycle bin.</p>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger px-4">Confirm Delete</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="successToast" class="toast align-items-center text-bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    // Add Company
    document.getElementById('addCompanyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('../actions/companies/add.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('addCompanyForm').reset();
                    bootstrap.Modal.getInstance(document.getElementById('addCompanyModal')).hide();
                    showSuccessToast(data.message);
                } else alert(data.message);
            });
    });

    // Edit Modal Data
    function openEditCompany(btn) {
        document.getElementById('edit_company_id').value = btn.dataset.id;
        document.getElementById('edit_name').value = btn.dataset.name;
        document.getElementById('edit_email').value = btn.dataset.email;
        document.getElementById('edit_phone').value = btn.dataset.phone;
        document.getElementById('edit_address').value = btn.dataset.address;

        const preview = document.getElementById('edit_image_preview');
        if (btn.dataset.image) {
            preview.src = btn.dataset.image;
            preview.style.display = 'inline-block';
        } else preview.style.display = 'none';

        new bootstrap.Modal(document.getElementById('editCompanyModal')).show();
    }

    function previewEditImage(event) {
        const preview = document.getElementById('edit_image_preview');
        preview.src = URL.createObjectURL(event.target.files[0]);
        preview.style.display = 'inline-block';
    }

    // Update Company
    document.getElementById('editCompanyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('../actions/companies/edit.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('editCompanyModal')).hide();
                    showSuccessToast(data.message);
                } else alert(data.message);
            });
    });

    // Delete Modal Data
    function openDeleteCompany(btn) {
        document.getElementById('delete_company_id').value = btn.dataset.id;
        document.getElementById('delete_company_name').innerText = btn.dataset.name;
        new bootstrap.Modal(document.getElementById('deleteCompanyModal')).show();
    }

    // Delete Action
    document.getElementById('deleteCompanyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('../actions/companies/delete.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('deleteCompanyModal')).hide();
                    showSuccessToast(data.message);
                } else alert(data.message);
            });
    });

    function showSuccessToast(msg) {
        const toastEl = document.getElementById('successToast');
        document.getElementById('toastMessage').innerText = msg;
        const toast = new bootstrap.Toast(toastEl, {
            delay: 1500
        });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => location.reload(), {
            once: true
        });
    }
</script>