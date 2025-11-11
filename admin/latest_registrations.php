<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? '') !== 'admin')) {
    header('Location: /medi/auth/login.php');
    exit;
}

// Get latest pharmacy registrations
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
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Latest Pharmacy Registrations | MediFinder Admin</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Latest pharmacy registrations" />
    <meta name="keywords" content="pharmacy registrations, admin" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include __DIR__ . '/../includes/head-css.php'; ?>
    <style>
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
      .table tbody td {
        color: #111827;
        font-weight: 500;
      }
      .table tbody td span,
      .table tbody td small {
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
        $pageTitle = 'Latest Pharmacy Registrations';
        $breadcrumbItems = ['Admin', 'Registrations'];
        $activeItem = 'Latest Registrations';
        include __DIR__ . '/../includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 mb-4">
            <div class="card">
              <div class="card-body">
                <div class="flex items-center justify-between">
                  <div>
                    <h2 class="mb-1">Latest Pharmacy Registrations</h2>
                    <p class="text-muted mb-0">Most recent five registrations awaiting review</p>
                  </div>
                  <a class="btn btn-primary" href="/medi/admin/pharmacy_approvals.php">
                    <i class="feather icon-shield mr-2"></i>View All Approvals
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="col-span-12">
            <div class="card">
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
                                <span class="badge bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:38px; height:38px; font-size:18px;">
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

