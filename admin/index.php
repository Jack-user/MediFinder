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

$highestStockPharmacy = $inventoryStats[0] ?? null;
$lowestStockPharmacy = null;
$inventoryCount = count($inventoryStats);
if ($inventoryCount > 1) {
    $lowestStockPharmacy = $inventoryStats[$inventoryCount - 1];
} elseif ($inventoryCount === 1) {
    $lowestStockPharmacy = $inventoryStats[0];
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
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </div>
    <!-- [ Main Content ] end -->
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php include __DIR__ . '/../includes/footer-js.php'; ?>
  </body>
  <!-- [Body] end -->
</html>
