<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /medi/auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'patient';

if ($userRole !== 'pharmacy_owner') {
    header('Location: /medi/dashboard.php');
    exit;
}

// Get pharmacy owned by this user
$stmt = $db->prepare('SELECT * FROM pharmacies WHERE owner_user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$pharmacy = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pharmacy) {
    // Check if there's a pending registration
    $stmt = $db->prepare('SELECT * FROM pharmacy_registrations WHERE user_id = ? AND status = "pending" LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $pending = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($pending) {
        echo '<!DOCTYPE html><html><head><title>Pending Approval</title></head><body><div class="container py-5"><div class="alert alert-warning"><h4>Registration Pending</h4><p>Your pharmacy registration is currently under review. You will be notified once it\'s approved.</p><a href="/medi/auth/logout.php" class="btn btn-primary">Logout</a></div></div></body></html>';
        exit;
    } else {
        header('Location: /medi/auth/register_pharmacy.php');
        exit;
    }
}

$pharmacyId = (int)$pharmacy['id'];

// Get inventory stats
$stmt = $db->prepare('SELECT COUNT(*) as total_medicines, SUM(quantity) as total_stock, SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) as in_stock FROM pharmacy_inventory WHERE pharmacy_id = ?');
$stmt->bind_param('i', $pharmacyId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get low stock items
$stmt = $db->prepare('SELECT pi.*, m.name as medicine_name FROM pharmacy_inventory pi JOIN medicines m ON pi.medicine_id = m.id WHERE pi.pharmacy_id = ? AND pi.quantity <= 10 ORDER BY pi.quantity ASC LIMIT 5');
$stmt->bind_param('i', $pharmacyId);
$stmt->execute();
$lowStock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Pharmacy Dashboard | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Pharmacy management dashboard" />
    <meta name="keywords" content="pharmacy dashboard, inventory management" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include 'includes/head-css.php'; ?>
  </head>
  <!-- [Head] end -->
  <!-- [Body] Start -->

  <body>
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
      <div class="pc-content">
        <?php
        $pageTitle = 'Pharmacy Dashboard';
        $breadcrumbItems = ['Pharmacy'];
        $activeItem = 'Dashboard';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <!-- Pharmacy Info -->
          <div class="col-span-12 mb-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <h2 class="mb-1"><?php echo htmlspecialchars($pharmacy['name']); ?></h2>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($pharmacy['address']); ?></p>
                  </div>
                  <a href="/medi/pharmacy_inventory.php" class="btn btn-primary">
                    <i class="feather icon-plus mr-2"></i>Manage Inventory
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="col-span-12 xl:col-span-4 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>Total Medicines</h5>
              </div>
              <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-pill text-primary-500 text-[30px] mr-1.5"></i>
                    <?php echo $stats['total_medicines'] ?? 0; ?>
                  </h3>
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 xl:col-span-4 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>Total Stock</h5>
              </div>
              <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-package text-success-500 text-[30px] mr-1.5"></i>
                    <?php echo $stats['total_stock'] ?? 0; ?>
                  </h3>
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 xl:col-span-4 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>In Stock Items</h5>
              </div>
              <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-check-circle text-info-500 text-[30px] mr-1.5"></i>
                    <?php echo $stats['in_stock'] ?? 0; ?>
                  </h3>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="col-span-12 xl:col-span-6 md:col-span-6">
            <div class="card">
              <div class="card-header">
                <h5>Quick Actions</h5>
              </div>
              <div class="card-body">
                <div class="grid grid-cols-2 gap-3">
                  <a href="/medi/pharmacy_inventory.php" class="card text-decoration-none border-primary hover:border-primary-600 transition-colors">
                    <div class="card-body text-center">
                      <i class="feather icon-package text-primary-500 text-[32px] mb-2"></i>
                      <h6 class="mb-0">Inventory</h6>
                    </div>
                  </a>
                  <a href="/medi/pharmacy_location.php" class="card text-decoration-none border-info hover:border-info-600 transition-colors">
                    <div class="card-body text-center">
                      <i class="feather icon-map-pin text-info-500 text-[32px] mb-2"></i>
                      <h6 class="mb-0">Update Location</h6>
                    </div>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Low Stock Alert -->
          <?php if (!empty($lowStock)): ?>
            <div class="col-span-12 xl:col-span-6 md:col-span-6">
              <div class="card border-warning">
                <div class="card-header bg-warning-50">
                  <h5 class="mb-0">
                    <i class="feather icon-alert-triangle mr-2 text-warning-500"></i>Low Stock Alert
                  </h5>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <tbody>
                        <?php foreach ($lowStock as $item): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <i class="feather icon-pill text-warning-500"></i>
                                <div>
                                  <h6 class="mb-1"><?php echo htmlspecialchars($item['medicine_name']); ?></h6>
                                  <small class="text-danger">Only <?php echo $item['quantity']; ?> units left</small>
                                </div>
                              </div>
                            </td>
                            <td class="text-end">
                              <a href="/medi/pharmacy_inventory.php?edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="feather icon-edit mr-1"></i>Update
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </div>
    <!-- [ Main Content ] end -->
    
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/footer-js.php'; ?>
  </body>
  <!-- [Body] end -->
</html>
