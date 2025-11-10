<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    header('Location: /medi/auth/login.php');
    exit;
}

$stats = [
    'total_pharmacies' => 0,
    'pending_registrations' => 0,
    'active_pharmacies' => 0,
    'total_users' => 0,
    'locations_covered' => 0,
    'medicines_listed' => 0,
    'total_inventory_stock' => 0,
];

if ($result = $db->query('SELECT COUNT(*) AS cnt FROM pharmacies')) {
    $stats['total_pharmacies'] = (int)($result->fetch_assoc()['cnt'] ?? 0);
    $result->free();
}

$stmt = $db->prepare('SELECT COUNT(*) AS cnt FROM pharmacy_registrations WHERE status = ?');
$status = 'pending';
if ($stmt) {
    $stmt->bind_param('s', $status);
    $stmt->execute();
    $res = $stmt->get_result();
    $stats['pending_registrations'] = (int)($res->fetch_assoc()['cnt'] ?? 0);
    $res->free();
    $stmt->close();
}

if ($result = $db->query('SELECT COUNT(*) AS cnt FROM pharmacies WHERE is_active = 1')) {
    $stats['active_pharmacies'] = (int)($result->fetch_assoc()['cnt'] ?? 0);
    $result->free();
}

if ($result = $db->query('SELECT COUNT(*) AS cnt FROM users')) {
    $stats['total_users'] = (int)($result->fetch_assoc()['cnt'] ?? 0);
    $result->free();
}

if ($result = $db->query('SELECT COUNT(DISTINCT CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN CONCAT(latitude, ",", longitude) END) AS cnt FROM pharmacies')) {
    $locations = (int)($result->fetch_assoc()['cnt'] ?? 0);
    $result->free();
    if ($locations === 0 && $stats['total_pharmacies'] > 0) {
        if ($fallback = $db->query('SELECT COUNT(DISTINCT TRIM(LOWER(address))) AS cnt FROM pharmacies')) {
            $locations = (int)($fallback->fetch_assoc()['cnt'] ?? $stats['total_pharmacies']);
            $fallback->free();
        }
    }
    $stats['locations_covered'] = $locations ?: $stats['total_pharmacies'];
}

if ($result = $db->query('SELECT COUNT(*) AS cnt FROM medicines')) {
    $stats['medicines_listed'] = (int)($result->fetch_assoc()['cnt'] ?? 0);
    $result->free();
}

if ($result = $db->query('SELECT COALESCE(SUM(quantity), 0) AS total_stock FROM pharmacy_inventory')) {
    $stats['total_inventory_stock'] = (int)($result->fetch_assoc()['total_stock'] ?? 0);
    $result->free();
}

$latestRegistrations = [];
$stmt = $db->prepare('SELECT pr.*, u.name AS owner_name, u.email AS owner_email FROM pharmacy_registrations pr JOIN users u ON pr.user_id = u.id ORDER BY pr.created_at DESC LIMIT 5');
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $latestRegistrations[] = $row;
    }
    $res->free();
    $stmt->close();
}

$registrationTrend = [];
for ($i = 11; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-{$i} months"));
    $registrationTrend[$monthKey] = 0;
}
$stmt = $db->prepare('SELECT DATE_FORMAT(created_at, "%Y-%m") AS month_key, COUNT(*) AS total FROM pharmacy_registrations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY month_key');
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($registrationTrend[$row['month_key']])) {
            $registrationTrend[$row['month_key']] = (int)$row['total'];
        }
    }
    $res->free();
    $stmt->close();
}
$registrationTrendLabels = [];
$registrationTrendCounts = [];
foreach ($registrationTrend as $monthKey => $count) {
    $registrationTrendLabels[] = date('M Y', strtotime($monthKey . '-01'));
    $registrationTrendCounts[] = $count;
}

$approvalTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-{$i} months"));
    $approvalTrend[$monthKey] = 0;
}
$stmt = $db->prepare('SELECT DATE_FORMAT(COALESCE(verified_at, created_at), "%Y-%m") AS month_key, COUNT(*) AS total FROM pharmacies WHERE COALESCE(verified_at, created_at) >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_key');
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($approvalTrend[$row['month_key']])) {
            $approvalTrend[$row['month_key']] = (int)$row['total'];
        }
    }
    $res->free();
    $stmt->close();
}
$approvalTrendLabels = [];
$approvalTrendCounts = [];
foreach ($approvalTrend as $monthKey => $count) {
    $approvalTrendLabels[] = date('M Y', strtotime($monthKey . '-01'));
    $approvalTrendCounts[] = $count;
}

$inventoryStats = [];
$stmt = $db->prepare('SELECT p.id, p.name, COALESCE(SUM(pi.quantity), 0) AS total_stock FROM pharmacies p LEFT JOIN pharmacy_inventory pi ON pi.pharmacy_id = p.id GROUP BY p.id ORDER BY total_stock DESC');
if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $inventoryStats[] = [
            'name' => $row['name'],
            'total_stock' => (int)$row['total_stock'],
        ];
    }
    $res->free();
    $stmt->close();
}

$inventoryLeaders = array_slice($inventoryStats, 0, 5);
$highestStockPharmacy = $inventoryStats[0] ?? null;
$lowestStockPharmacy = null;
$inventoryCount = count($inventoryStats);
if ($inventoryCount > 1) {
    $lowestStockPharmacy = $inventoryStats[$inventoryCount - 1];
} elseif ($inventoryCount === 1) {
    $lowestStockPharmacy = $inventoryStats[0];
}

$statusBreakdown = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
if ($result = $db->query('SELECT status, COUNT(*) AS total FROM pharmacy_registrations GROUP BY status')) {
    while ($row = $result->fetch_assoc()) {
        $status = $row['status'] ?? '';
        if (isset($statusBreakdown[$status])) {
            $statusBreakdown[$status] = (int)$row['total'];
        }
    }
    $result->free();
}
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Admin Dashboard | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Admin management dashboard" />
    <meta name="keywords" content="admin dashboard, pharmacy approvals" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include __DIR__ . '/../includes/head-css.php'; ?>
    <style>
      .stat-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
      }
      .stat-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        padding: 24px 18px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 160px;
        transition: all 0.25s ease;
      }
      .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 22px 48px rgba(15, 23, 42, 0.12);
      }
      .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
        font-size: 24px;
      }
      .stat-icon-primary { background: rgba(13, 110, 253, 0.12); color: #0d6efd; }
      .stat-icon-warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
      .stat-icon-success { background: rgba(16, 185, 129, 0.12); color: #16a34a; }
      .stat-icon-info { background: rgba(14, 165, 233, 0.12); color: #0ea5e9; }
      .stat-icon-coverage { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
      .stat-icon-inventory { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
      .stat-label {
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: 0.7rem;
        color: #64748b;
        margin-bottom: 6px;
        font-weight: 600;
      }
      .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #0f172a;
      }
      .stat-caption {
        font-size: 0.85rem;
        color: #475569;
        margin-top: 6px;
      }
      .chart-card {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.06);
        margin-bottom: 24px;
      }
      .chart-card .card-body {
        min-height: 320px;
      }
      .highlight-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 22px 55px rgba(15, 23, 42, 0.08);
      }
      .table-heading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #1f2937;
      }
      .table-heading i {
        font-size: 1rem;
        color: #0f172a;
      }
      .latest-registrations .table tbody td {
        color: #111827;
        font-weight: 500;
      }
      .latest-registrations .table tbody td span,
      .latest-registrations .table tbody td small {
        color: #1f2937;
      }
    </style>
  </head>
  <!-- [Head] end -->
  <!-- [Body] Start -->

  <body>
    <?php include __DIR__ . '/../includes/loader.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
      <div class="pc-content">
        <?php
        $pageTitle = 'Admin Dashboard';
        $breadcrumbItems = ['Admin'];
        $activeItem = 'Dashboard';
        include __DIR__ . '/../includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 mb-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <h2 class="mb-1">Admin Dashboard</h2>
                    <p class="text-muted mb-0">Manage pharmacy registrations and system settings</p>
                  </div>
                  <a class="btn btn-primary" href="/medi/admin/pharmacy_approvals.php">
                    <i class="feather icon-shield mr-2"></i>Pharmacy Approvals
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="stat-cards-grid">
              <div class="stat-card">
                <div class="stat-icon stat-icon-primary">
                  <i class="feather icon-layers"></i>
                </div>
                <p class="stat-label mb-1">Total Pharmacies</p>
                <span class="stat-value"><?php echo number_format($stats['total_pharmacies']); ?></span>
                <span class="stat-caption">All records in the network</span>
              </div>
              <div class="stat-card">
                <div class="stat-icon stat-icon-warning">
                  <i class="feather icon-clock"></i>
                </div>
                <p class="stat-label mb-1">Pending Approvals</p>
                <span class="stat-value"><?php echo number_format($stats['pending_registrations']); ?></span>
                <span class="stat-caption">Awaiting admin review</span>
              </div>
              <div class="stat-card">
                <div class="stat-icon stat-icon-success">
                  <i class="feather icon-check-circle"></i>
                </div>
                <p class="stat-label mb-1">Active Pharmacies</p>
                <span class="stat-value"><?php echo number_format($stats['active_pharmacies']); ?></span>
                <span class="stat-caption">Currently verified & active</span>
              </div>
              <div class="stat-card">
                <div class="stat-icon stat-icon-info">
                  <i class="feather icon-users"></i>
                </div>
                <p class="stat-label mb-1">Total Users</p>
                <span class="stat-value"><?php echo number_format($stats['total_users']); ?></span>
                <span class="stat-caption">Patients, owners & admins</span>
              </div>
              <div class="stat-card">
                <div class="stat-icon stat-icon-coverage">
                  <i class="feather icon-map-pin"></i>
                </div>
                <p class="stat-label mb-1">Locations Covered</p>
                <span class="stat-value"><?php echo number_format($stats['locations_covered']); ?></span>
                <span class="stat-caption">Unique pharmacy locations</span>
              </div>
              <div class="stat-card">
                <div class="stat-icon stat-icon-inventory">
                  <i class="feather icon-package"></i>
                </div>
                <p class="stat-label mb-1">Medicines Listed</p>
                <span class="stat-value"><?php echo number_format($stats['medicines_listed']); ?></span>
                <span class="stat-caption">Inventory units: <?php echo number_format($stats['total_inventory_stock']); ?></span>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card chart-card latest-registrations">
              <div class="card-header border-0 pb-0">
                <div>
                  <h5 class="mb-1 font-semibold">Monthly Pharmacy Registrations</h5>
                  <p class="text-muted mb-0 text-sm">Registrations captured across the last 12 months</p>
                </div>
              </div>
              <div class="card-body">
                <canvas id="monthlyRegistrationsChart"></canvas>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card chart-card">
              <div class="card-header border-0 pb-0">
                <div>
                  <h5 class="mb-1 font-semibold">Inventory Leaders</h5>
                  <p class="text-muted mb-0 text-sm">Top pharmacies ranked by total stock on hand</p>
                </div>
              </div>
              <div class="card-body">
                <canvas id="pharmacyInventoryLeadersChart"></canvas>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card chart-card">
              <div class="card-header border-0 pb-0">
                <div>
                  <h5 class="mb-1 font-semibold">New Pharmacy Approvals</h5>
                  <p class="text-muted mb-0 text-sm">Approved pharmacies over the last 6 months</p>
                </div>
              </div>
              <div class="card-body">
                <canvas id="monthlyApprovalsChart"></canvas>
              </div>
            </div>
          </div>

          <div class="col-span-12 mb-4">
            <div class="stat-cards-grid">
              <div class="stat-card" style="min-height: 180px;">
                <div class="stat-icon stat-icon-success">
                  <i class="feather icon-trending-up"></i>
                </div>
                <p class="stat-label mb-1">Highest Stocked Pharmacy</p>
                <?php if ($highestStockPharmacy): ?>
                  <span class="stat-value"><?php echo number_format($highestStockPharmacy['total_stock']); ?></span>
                  <span class="stat-caption text-success"><?php echo htmlspecialchars($highestStockPharmacy['name']); ?></span>
                  <small class="text-muted mt-2">Units recorded in inventory</small>
                <?php else: ?>
                  <span class="stat-value">0</span>
                  <small class="text-muted mt-2">No inventory data available yet</small>
                <?php endif; ?>
              </div>
              <div class="stat-card" style="min-height: 180px;">
                <div class="stat-icon stat-icon-warning">
                  <i class="feather icon-trending-down"></i>
                </div>
                <p class="stat-label mb-1">Lowest Stocked Pharmacy</p>
                <?php if ($lowestStockPharmacy): ?>
                  <span class="stat-value"><?php echo number_format($lowestStockPharmacy['total_stock']); ?></span>
                  <span class="stat-caption text-danger"><?php echo htmlspecialchars($lowestStockPharmacy['name']); ?></span>
                  <small class="text-muted mt-2">Units recorded in inventory</small>
                <?php else: ?>
                  <span class="stat-value">0</span>
                  <small class="text-muted mt-2">No inventory data available yet</small>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card chart-card">
              <div class="card-header border-0 pb-0">
                <div>
                  <h5 class="mb-1 font-semibold">Latest Pharmacy Registrations</h5>
                  <p class="text-muted mb-0 text-sm">Most recent five registrations awaiting review</p>
                </div>
              </div>
              <div class="card-body pt-3">
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead class="bg-light">
                      <tr>
                        <th scope="col"><span class="table-heading"><i class="feather icon-home"></i>Pharmacy Name</span></th>
                        <th scope="col"><span class="table-heading"><i class="feather icon-user"></i>Owner</span></th>
                        <th scope="col"><span class="table-heading"><i class="feather icon-map-pin"></i>Address</span></th>
                        <th scope="col"><span class="table-heading"><i class="feather icon-calendar"></i>Date Registered</span></th>
                        <th scope="col"><span class="table-heading"><i class="feather icon-award"></i>Status</span></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($latestRegistrations)): ?>
                        <tr>
                          <td colspan="5" class="text-center text-muted py-4">
                            <i class="feather icon-info mr-2"></i>No pharmacy registrations recorded yet.
                          </td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($latestRegistrations as $registration): ?>
                          <?php
                            $status = $registration['status'] ?? 'pending';
                            $statusLabel = ucfirst($status);
                            $statusClass = 'bg-warning-subtle text-warning';
                            $statusIcon = 'icon-alert-circle';
                            if ($status === 'approved') {
                                $statusClass = 'bg-success-subtle text-success';
                                $statusIcon = 'icon-check';
                            } elseif ($status === 'rejected') {
                                $statusClass = 'bg-danger-subtle text-danger';
                                $statusIcon = 'icon-x';
                            }
                          ?>
                          <tr>
                            <td>
                              <div class="d-flex align-items-center gap-3">
                                <span class="stat-icon stat-icon-primary" style="margin-bottom:0; width:38px; height:38px; font-size:18px;">
                                  <i class="feather icon-home"></i>
                                </span>
                                <div>
                                  <span class="fw-semibold d-block text-slate-700"><?php echo htmlspecialchars($registration['pharmacy_name']); ?></span>
                                  <?php if (!empty($registration['business_name'])): ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($registration['business_name']); ?></small>
                                  <?php endif; ?>
                                </div>
                              </div>
                            </td>
                            <td>
                              <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-slate-100 text-slate-600 rounded-pill px-2 py-1"><i class="feather icon-user"></i></span>
                                <span>
                                  <?php echo htmlspecialchars($registration['owner_name']); ?>
                                  <small class="text-muted d-block"><?php echo htmlspecialchars($registration['owner_email']); ?></small>
                                </span>
                              </div>
                            </td>
                            <td>
                              <div class="d-flex align-items-center gap-2">
                                <i class="feather icon-map-pin text-primary"></i>
                                <span><?php echo htmlspecialchars($registration['address']); ?></span>
                              </div>
                            </td>
                            <td>
                              <div class="d-flex align-items-center gap-2">
                                <i class="feather icon-clock text-warning"></i>
                                <span><?php echo date('M d, Y g:i A', strtotime($registration['created_at'])); ?></span>
                              </div>
                            </td>
                            <td>
                              <span class="badge <?php echo $statusClass; ?> rounded-pill d-inline-flex align-items-center gap-1">
                                <i class="feather <?php echo $statusIcon; ?>"></i><?php echo $statusLabel; ?>
                              </span>
                              <?php if ($status === 'rejected' && !empty($registration['rejection_reason'])): ?>
                                <div class="text-muted small mt-1"><?php echo htmlspecialchars($registration['rejection_reason']); ?></div>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-12 xl:col-span-6 md:col-span-6">
            <div class="card">
              <div class="card-header">
                <h5>Management</h5>
              </div>
              <div class="card-body">
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">
                    <a href="/medi/admin/pharmacy_approvals.php" class="text-decoration-none">
                      <i class="feather icon-clock mr-2 text-warning-500"></i>Pending Registrations
                    </a>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="feather icon-list mr-2 text-primary-500"></i>Total Pending</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-1"><?php echo number_format($statusBreakdown['pending']); ?></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="feather icon-check mr-2 text-success-500"></i>Total Approved</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1"><?php echo number_format($statusBreakdown['approved']); ?></span>
                  </li>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="feather icon-x mr-2 text-danger-500"></i>Total Rejected</span>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1"><?php echo number_format($statusBreakdown['rejected']); ?></span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </div>
    <!-- [ Main Content ] end -->
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php include __DIR__ . '/../includes/footer-js.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (!window.Chart) { return; }

        const registrationLabels = <?php echo json_encode($registrationTrendLabels, JSON_UNESCAPED_UNICODE); ?>;
        const registrationCounts = <?php echo json_encode($registrationTrendCounts, JSON_NUMERIC_CHECK); ?>;
        const inventoryLabels = <?php echo json_encode(array_column($inventoryLeaders, 'name'), JSON_UNESCAPED_UNICODE); ?>;
        const inventoryCounts = <?php echo json_encode(array_map('intval', array_column($inventoryLeaders, 'total_stock')), JSON_NUMERIC_CHECK); ?>;
        const approvalLabels = <?php echo json_encode($approvalTrendLabels, JSON_UNESCAPED_UNICODE); ?>;
        const approvalCounts = <?php echo json_encode($approvalTrendCounts, JSON_NUMERIC_CHECK); ?>;

        const registrationsCanvas = document.getElementById('monthlyRegistrationsChart');
        if (registrationsCanvas) {
          const ctx = registrationsCanvas.getContext('2d');
          const gradient = ctx.createLinearGradient(0, 0, 0, registrationsCanvas.height);
          gradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
          gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');
          new Chart(ctx, {
            type: 'line',
            data: {
              labels: registrationLabels,
              datasets: [{
                label: 'Registrations',
                data: registrationCounts,
                fill: true,
                backgroundColor: gradient,
                borderColor: 'rgba(16, 185, 129, 1)',
                tension: 0.35,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(16,185,129,1)',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const value = context.parsed.y || 0;
                      return value.toLocaleString() + ' registrations';
                    }
                  }
                }
              },
              scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { weight: 500 } } },
                y: {
                  beginAtZero: true,
                  grid: { color: 'rgba(148, 163, 184, 0.2)', drawBorder: false },
                  ticks: {
                    precision: 0,
                    color: '#64748b'
                  }
                }
              }
            }
          });
        }

        const inventoryCanvas = document.getElementById('pharmacyInventoryLeadersChart');
        if (inventoryCanvas) {
          new Chart(inventoryCanvas, {
            type: 'bar',
            data: {
              labels: inventoryLabels,
              datasets: [{
                label: 'Total Stock',
                data: inventoryCounts,
                backgroundColor: [
                  'rgba(13,110,253,0.85)',
                  'rgba(16,185,129,0.85)',
                  'rgba(59,130,246,0.85)',
                  'rgba(249,115,22,0.85)',
                  'rgba(124,58,237,0.85)'
                ],
                borderRadius: 12,
                maxBarThickness: 48
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { weight: 500 } } },
                y: {
                  beginAtZero: true,
                  grid: { color: 'rgba(148, 163, 184, 0.2)', drawBorder: false },
                  ticks: {
                    precision: 0,
                    color: '#64748b'
                  }
                }
              },
              plugins: {
                legend: { display: false },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const value = context.parsed.y || 0;
                      return value.toLocaleString() + ' units';
                    }
                  }
                }
              }
            }
          });
        }

        const approvalsCanvas = document.getElementById('monthlyApprovalsChart');
        if (approvalsCanvas) {
          const approvalsCtx = approvalsCanvas.getContext('2d');
          const approvalsGradient = approvalsCtx.createLinearGradient(0, 0, 0, approvalsCanvas.height);
          approvalsGradient.addColorStop(0, 'rgba(13, 110, 253, 0.28)');
          approvalsGradient.addColorStop(1, 'rgba(13, 110, 253, 0.03)');
          new Chart(approvalsCtx, {
            type: 'line',
            data: {
              labels: approvalLabels,
              datasets: [{
                label: 'Approved Pharmacies',
                data: approvalCounts,
                fill: true,
                backgroundColor: approvalsGradient,
                borderColor: 'rgba(13,110,253,1)',
                tension: 0.35,
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: 'rgba(13,110,253,1)',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: { display: false },
                tooltip: {
                  callbacks: {
                    label: function (context) {
                      const value = context.parsed.y || 0;
                      return value.toLocaleString() + ' approvals';
                    }
                  }
                }
              },
              scales: {
                x: { grid: { display: false }, ticks: { color: '#64748b', font: { weight: 500 } } },
                y: {
                  beginAtZero: true,
                  grid: { color: 'rgba(148, 163, 184, 0.2)', drawBorder: false },
                  ticks: {
                    precision: 0,
                    color: '#64748b'
                  }
                }
              }
            }
          });
        }
      });
    </script>
  </body>
  <!-- [Body] end -->
</html>
