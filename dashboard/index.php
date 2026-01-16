<?php
session_start();
require '../config/db.php';

// Protect admin
require '../includes/auth_check.php';

// Get Filter Parameters
$range = $_GET['range'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$dateFilter = " AND 1=1 ";
$params = [];

if ($range === 'today') {
    $dateFilter = " AND DATE(created_at) = CURDATE() ";
} elseif ($range === 'week') {
    $dateFilter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ";
} elseif ($range === 'month') {
    $dateFilter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ";
} elseif ($range === 'custom' && !empty($startDate) && !empty($endDate)) {
    $dateFilter = " AND DATE(created_at) BETWEEN ? AND ? ";
    $params = [$startDate, $endDate];
}

// Total Users
$stmtUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL $dateFilter");
$stmtUsers->execute($params);
$totalUsers = $stmtUsers->fetchColumn();

// Total Companies
$stmtCompanies = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE deleted_at IS NULL $dateFilter");
$stmtCompanies->execute($params);
$totalCompanies = $stmtCompanies->fetchColumn();

// Active Users
$stmtActive = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL $dateFilter");
$stmtActive->execute($params);
$activeUsers = $stmtActive->fetchColumn();

// Inactive Users
$stmtInactive = $pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'inactive' AND deleted_at IS NULL $dateFilter");
$stmtInactive->execute($params);
$inactiveUsers = $stmtInactive->fetchColumn();

// Chart Data
// Handle ambiguity for the JOIN query
$chartDateFilter = str_replace('created_at', 'u.created_at', $dateFilter);
$stmtChart = $pdo->prepare("
    SELECT c.name, COUNT(u.id) as user_count 
    FROM companies c 
    LEFT JOIN users u ON c.id = u.company_id AND u.deleted_at IS NULL $chartDateFilter
    WHERE c.deleted_at IS NULL 
    GROUP BY c.id 
    ORDER BY user_count DESC 
    LIMIT 5
");
$stmtChart->execute($params);
$chartData = $stmtChart->fetchAll(PDO::FETCH_ASSOC);

$companyNames = array_map(function ($row) {
    return $row['name'];
}, $chartData);
$userCounts = array_map(function ($row) {
    return (int)$row['user_count'];
}, $chartData);

// Page variables
$pageTitle = "Admin Dashboard";
$activeMenu = "dashboard";

// Extra JS for Charts
$extraJs = '
<script src="../assets/js/plugins/apexcharts.min.js"></script>
<script>
    function toggleCustomDates() {
        const range = document.getElementById("rangeSelect").value;
        document.querySelector(".custom-date-inputs").style.display = (range === "custom") ? "flex" : "none";
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Bar Chart
        if (document.querySelector("#users-per-company-chart")) {
            var barOptions = {
                chart: { type: "bar", height: 350, toolbar: { show: false } },
                series: [{ name: "Users", data: ' . json_encode($userCounts) . ' }],
                xaxis: { categories: ' . json_encode($companyNames) . ' },
                colors: ["#673ab7"],
                plotOptions: { bar: { borderRadius: 4, horizontal: true } },
                dataLabels: { enabled: false }
            };
            new ApexCharts(document.querySelector("#users-per-company-chart"), barOptions).render();
        }

        // Pie Chart
        if (document.querySelector("#user-status-pie-chart")) {
            var pieOptions = {
                chart: { type: "donut", height: 350 },
                series: [' . (int)$activeUsers . ', ' . (int)$inactiveUsers . '],
                labels: ["Active", "Inactive"],
                colors: ["#27ae60", "#f39c12"],
                legend: { position: "bottom" },
                plotOptions: { pie: { donut: { size: "65%" } } }
            };
            new ApexCharts(document.querySelector("#user-status-pie-chart"), pieOptions).render();
        }
    });
</script>';

// Load layout
require '../layouts/app.php';
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- [ Filter ] start -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-auto">
                        <!-- <label class="fw-bold text-muted small text-uppercase mb-1 d-block fs-4">Filter All Data</label> -->
                        <select name="range" class="form-select" id="rangeSelect" onchange="toggleCustomDates()">
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
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
                        </div>
                        <span class="mt-4">to</span>
                        <div>
                            <label class="small text-muted mb-1 d-block">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
                        </div>
                    </div>
                    <div class="col-auto">
                        <!-- <label class="d-block">&nbsp;</label> -->
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <?php if ($range !== 'all'): ?>
                            <a href="index.php" class="btn btn-light-secondary ms-1">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- [ Summary Cards ] start -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary dashnum-card text-white overflow-hidden border-0 shadow-sm">
                    <span class="round small"></span>
                    <span class="round big"></span>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="avtar avtar-lg bg-light-primary text-primary">
                                <i class="ti ti-users"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="text-white mb-0"><?= $totalUsers ?></h3>
                                <p class="mb-0 opacity-75">Total Users</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card bg-secondary dashnum-card text-white overflow-hidden border-0 shadow-sm">
                    <span class="round small"></span>
                    <span class="round big"></span>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="avtar avtar-lg bg-light-secondary text-secondary">
                                <i class="ti ti-building"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="text-white mb-0"><?= $totalCompanies ?></h3>
                                <p class="mb-0 opacity-75">Total Companies</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card dashnum-card bg-success text-white overflow-hidden border-0 shadow-sm">
                    <span class="round small"></span>
                    <span class="round big"></span>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="avtar avtar-lg bg-light text-success">
                                <i class="ti ti-user-check"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="text-white mb-0"><?= $activeUsers ?></h3>
                                <p class="mb-0 opacity-75">Active Users</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card dashnum-card bg-warning text-white overflow-hidden border-0 shadow-sm">
                    <span class="round small"></span>
                    <span class="round big"></span>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="avtar avtar-lg bg-light text-warning">
                                <i class="ti ti-user-x"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="text-white mb-0"><?= $inactiveUsers ?></h3>
                                <p class="mb-0 opacity-75">Inactive Users</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- [ Charts ] start -->
        <div class="row">
            <div class="col-xl-7 col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 bg-transparent py-3">
                        <h5 class="mb-0">Users per Company</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($userCounts)): ?>
                            <div id="users-per-company-chart"></div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="ti ti-chart-bar fs-1 text-light"></i>
                                <p class="text-muted mt-2">No data found in this period</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header border-0 bg-transparent py-3">
                        <h5 class="mb-0">User Status</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($activeUsers > 0 || $inactiveUsers > 0): ?>
                            <div id="user-status-pie-chart"></div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="ti ti-chart-pie fs-1 text-light"></i>
                                <p class="text-muted mt-2">No data found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Companies -->
            <div class="col-xl-6 col-md-12">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center border-0 bg-transparent py-3">
                        <h5 class="mb-0">Recent Companies</h5>
                        <a href="company-profile.php" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Company</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmtRecentComp = $pdo->prepare("SELECT name, image, created_at FROM companies WHERE deleted_at IS NULL $dateFilter ORDER BY created_at DESC LIMIT 5");
                                    $stmtRecentComp->execute($params);
                                    $recentComp = $stmtRecentComp->fetchAll(PDO::FETCH_ASSOC);
                                    if ($recentComp):
                                        foreach ($recentComp as $c):
                                    ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= $c['image'] ? '../uploads/companies/' . $c['image'] : '../assets/images/company-default.jpg' ?>" class="rounded-circle me-3" width="35" height="35" style="object-fit: cover;">
                                                        <span class="fw-semibold"><?= htmlspecialchars($c['name']) ?></span>
                                                    </div>
                                                </td>
                                                <td><span class="text-muted small"><?= date('M d, Y', strtotime($c['created_at'])) ?></span></td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">No results</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-xl-6 col-md-12">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center border-0 bg-transparent py-3">
                        <h5 class="mb-0">Recent Users</h5>
                        <a href="users.php" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmtRecentUsers = $pdo->prepare("SELECT name, role, status, image FROM users WHERE deleted_at IS NULL $dateFilter ORDER BY created_at DESC LIMIT 5");
                                    $stmtRecentUsers->execute($params);
                                    $recentUsers = $stmtRecentUsers->fetchAll(PDO::FETCH_ASSOC);
                                    if ($recentUsers):
                                        foreach ($recentUsers as $u):
                                    ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?= $u['image'] ? '../uploads/users/' . $u['image'] : '../assets/images/user/avatar-2.svg' ?>" class="rounded-circle me-3" width="35" height="35" style="object-fit: cover;">
                                                        <span class="fw-semibold"><?= htmlspecialchars($u['name']) ?></span>
                                                    </div>
                                                </td>
                                                <td><span class="badge bg-light-primary text-primary"><?= ucfirst($u['role']) ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?= $u['status'] == 'active' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($u['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">No results</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>