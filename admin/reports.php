<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    header('Location: /medi/auth/login.php');
    exit;
}

// Get registration trend data
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

// Get approval trend data
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

// Get inventory leaders data
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
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Reports | MediFinder Admin</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Admin reports and analytics" />
    <meta name="keywords" content="reports, analytics, admin" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include __DIR__ . '/../includes/head-css.php'; ?>
    <style>
      .chart-card {
        border: none;
        border-radius: 22px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.06);
        margin-bottom: 24px;
      }
      .chart-card .card-body {
        min-height: 320px;
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
        $pageTitle = 'Reports';
        $breadcrumbItems = ['Admin', 'Reports'];
        $activeItem = 'Reports';
        include __DIR__ . '/../includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 mb-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <h2 class="mb-1">Reports & Analytics</h2>
                    <p class="text-muted mb-0">View detailed reports and analytics for pharmacy registrations, approvals, and inventory</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card chart-card">
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

