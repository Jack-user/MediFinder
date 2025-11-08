<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    header('Location: /CEMO_System/system/auth/login.php');
    exit;
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
    <link rel="icon" href="/CEMO_System/system/assets/img/medifinder-logo.svg" type="image/x-icon" />

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
                  <a class="btn btn-primary" href="/CEMO_System/system/admin/pharmacy_approvals.php">
                    <i class="feather icon-shield mr-2"></i>Pharmacy Approvals
                  </a>
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
                    <a href="/CEMO_System/system/admin/pharmacy_approvals.php" class="text-decoration-none">
                      <i class="feather icon-clock mr-2 text-warning-500"></i>Pending Registrations
                    </a>
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
