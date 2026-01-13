<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php
session_start();
require '../config/db.php';

/* Auth protection */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* Fetch companies */
$sql = "
    SELECT id, name, email, phone, address, image, created_at
    FROM companies
    WHERE deleted_at IS NULL
    ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php

// Protect admin
require '../auth.php';

// Page variables
$pageTitle = "Company Profile";
$activeMenu = "company-profile";

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

.table-responsive {
    border-radius: 12px;
    overflow: hidden;
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

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold mb-0">Company List</h4>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                    <i class="ti ti-plus"></i> Add Company
                </button>
            </div>

            <!-- Table -->
            <div class="card shadow-sm">
                <div class="card-body p-0">

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
                                <tr>
                                    <td><?= $i + 1 ?></td>

                                    <td>
                                        <img src="<?= $company['image']
                                                ? 'uploads/companies/' . htmlspecialchars($company['image'])
                                                : '../assets/images/company-default.jpg'; ?>" width="45" height="45"
                                            class="rounded-circle" style="object-fit:cover;">
                                    </td>

                                    <td class="fw-semibold">
                                        <a href="company-details.php?id=<?= $company['id'] ?>">
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
                                        <button class="btn btn-sm btn-warning" onclick="openEditCompany(this)"
                                            data-id="<?= $company['id'] ?>"
                                            data-name="<?= htmlspecialchars($company['name']) ?>"
                                            data-email="<?= htmlspecialchars($company['email'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($company['phone'] ?? '') ?>"
                                            data-address="<?= htmlspecialchars($company['address'] ?? '') ?>"
                                            data-image="<?= $company['image'] ? 'uploads/companies/' . $company['image'] : '' ?>">
                                            Edit
                                        </button>

                                        <button class="btn btn-sm btn-outline-danger" onclick="openDeleteCompany(this)"
                                            data-id="<?= $company['id'] ?>"
                                            data-name="<?= htmlspecialchars($company['name']) ?>">
                                            Delete
                                        </button>

                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No companies found
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>

                        </table>
                    </div>

                </div>


            </div>

            <div class="modal fade" id="addCompanyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">

                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">Add Company</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <!-- Modal Body -->
                        <form id="addCompanyForm" enctype="multipart/form-data">
                            <div class="modal-body">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Image</label>
                                        <input type="file" name="image" class="form-control" accept="image/*">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" class="form-control"></textarea>
                                    </div>
                                </div>

                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Company</button>
                            </div>
                        </form>


                    </div>
                </div>
            </div>

            <div class="modal fade" id="editCompanyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">

                        <!-- Modal Header -->
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Company</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <!-- Modal Body -->
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
                                        <label class="form-label">Image</label>
                                        <input type="file" name="image" class="form-control" accept="image/*"
                                            onchange="previewEditImage(event)">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea name="address" id="edit_address" class="form-control"></textarea>
                                    </div>

                                    <!-- Image Preview -->
                                    <div class="col-12 text-center mt-2">
                                        <img id="edit_image_preview" src="https://via.placeholder.com/120"
                                            class="rounded border" style="width:120px;height:120px;object-fit:cover;">
                                    </div>
                                </div>

                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Company</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <div class="modal fade" id="deleteCompanyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title text-danger">Delete Company</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form id="deleteCompanyForm">
                            <input type="hidden" name="company_id" id="delete_company_id">

                            <div class="modal-body">
                                <p class="mb-1">
                                    Are you sure you want to delete
                                    <strong id="delete_company_name"></strong>?
                                </p>
                                <small class="text-danger">
                                    This action cannot be undone.
                                </small>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-danger">
                                    Yes, Delete
                                </button>
                                <button class="btn btn-sm btn-success" onclick="restoreCompany(<?= $row['id'] ?>)">
                                    Restore
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>


        </div>

        <!-- Toast Container -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
            <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body" id="toastMessage"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
                </div>
            </div>
        </div>


        <script>
        document.getElementById('addCompanyForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('add_company_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {

                        document.getElementById('addCompanyForm').reset();

                        const modalEl = document.getElementById('addCompanyModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);

                        modal.hide();

                        // 🔥 Wait for modal animation to finish
                        modalEl.addEventListener('hidden.bs.modal', function() {
                            showSuccessToast(data.message);
                        }, {
                            once: true
                        });

                    } else {
                        alert(data.message);
                    }
                });
        });

        function showSuccessToast(msg) {
            const toastEl = document.getElementById('successToast');
            document.getElementById('toastMessage').innerText = msg;

            const toast = new bootstrap.Toast(toastEl, {
                delay: 2000
            });

            toast.show();
            // page reload after toast hides
            toastEl.addEventListener('hidden.bs.toast', function() {
                location.reload();
            });
        }
        </script>

        <script>
        function openEditCompany(btn) {

            document.getElementById('edit_company_id').value = btn.dataset.id;
            document.getElementById('edit_name').value = btn.dataset.name;
            document.getElementById('edit_email').value = btn.dataset.email;
            document.getElementById('edit_phone').value = btn.dataset.phone;
            document.getElementById('edit_address').value = btn.dataset.address;

            const img = document.getElementById('edit_image_preview');
            img.src = btn.dataset.image || 'https://via.placeholder.com/120';

            new bootstrap.Modal(document.getElementById('editCompanyModal')).show();
        }

        function previewEditImage(event) {
            document.getElementById('edit_image_preview').src =
                URL.createObjectURL(event.target.files[0]);
        }
        </script>


        <script>
        document.getElementById('editCompanyForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('edit_company_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {

                    if (data.status === 'success') {

                        bootstrap.Modal.getInstance(
                            document.getElementById('editCompanyModal')
                        ).hide();

                        showSuccessToast(data.message);

                        // Optional: reload table or update row dynamically
                    } else {
                        alert(data.message);
                    }
                });
        });
        </script>

        <script>
        function openDeleteCompany(btn) {

            document.getElementById('delete_company_id').value = btn.dataset.id;
            document.getElementById('delete_company_name').innerText = btn.dataset.name;

            new bootstrap.Modal(
                document.getElementById('deleteCompanyModal')
            ).show();
        }
        </script>

        <script>
        document.getElementById('deleteCompanyForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('delete_company_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {

                    if (data.status === 'success') {

                        bootstrap.Modal.getInstance(
                            document.getElementById('deleteCompanyModal')
                        ).hide();

                        showSuccessToast(data.message);

                        // 🔥 Remove row instantly (optional)
                        document.querySelector(
                            `tr[data-company-id="${formData.get('company_id')}"]`
                        )?.remove();

                    } else {
                        alert(data.message);
                    }
                });
        });
        </script>

        <script>
        function restoreCompany(id) {
            fetch('restore_company.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'company_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        showSuccessToast(data.message);
                        location.reload();
                    }
                });
        }
        </script>