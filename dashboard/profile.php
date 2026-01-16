<?php
session_start();

// Protect admin
require '../includes/auth_check.php';

// Page variables
$pageTitle = "Company Profile";
$activeMenu = "company-profile";

// Load layout
require '../layouts/app.php';

?>

<style>
body {
    background-color: #f5f6fa;
}

.profile-card {
    border-radius: 12px;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, .1);
}
</style>

<div class="pc-container">
    <div class="pc-content">
        <div class="container py-5">
            <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']); ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success']); ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="row">

                <!-- Profile Sidebar -->
                <div class="col-lg-4 mb-4">
                    <div class="card profile-card text-center p-4">
                        <img src="https://i.pravatar.cc/150?img=12" class="profile-avatar mx-auto mb-3">
                        <h5 class="mb-0">Rohit Kashyap</h5>
                        <small class="text-muted">Admin</small>

                        <hr>

                        <button class="btn btn-outline-primary btn-sm">
                            Change Photo
                        </button>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="col-lg-8">
                    <div class="card profile-card p-4">
                        <h5 class="mb-4">Profile Information</h5>

                        <form>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo htmlspecialchars($_SESSION['admin_name']); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo htmlspecialchars($_SESSION['admin_last_name'] ?? ''); ?>"
                                        disabled>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control"
                                    value="<?php echo htmlspecialchars($_SESSION['admin_email']); ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" placeholder="+91 98765 43210"
                                    value="<?php echo htmlspecialchars($_SESSION['admin_phone'] ?? ''); ?>" disabled>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="3"
                                    placeholder="Enter address"><?php echo htmlspecialchars($_SESSION['admin_address'] ?? ''); ?></textarea>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="card profile-card p-4 mt-4">
                        <h5 class="mb-4">Change Password</h5>

                        <form action="../actions/auth/update_password.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" placeholder="Enter current password"
                                    required name="current_password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" placeholder="Enter new password" required
                                    name="new_password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" placeholder="Confirm new password" required
                                    name="confirm_password">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-danger">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
