<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    header('Location: /medi/auth/login.php');
    exit;
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
    <title>Management | MediFinder Admin</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Admin management dashboard" />
    <meta name="keywords" content="admin management, pharmacy approvals" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include __DIR__ . '/../includes/head-css.php'; ?>
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
        $pageTitle = 'Management';
        $breadcrumbItems = ['Admin', 'Management'];
        $activeItem = 'Management';
        include __DIR__ . '/../includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 mb-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <h2 class="mb-1">Management</h2>
                    <p class="text-muted mb-0">Manage pharmacy registrations and approvals</p>
                  </div>
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
  </body>
  <!-- [Body] end -->
</html>

