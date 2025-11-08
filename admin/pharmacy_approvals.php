<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /medi/auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    header('Location: /medi/dashboard.php');
    exit;
}

$success = null;
$error = null;

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $regId = (int)($_POST['registration_id'] ?? 0);
    $adminId = (int)$_SESSION['user_id'];
    
    if ($action === 'approve' && $regId) {
        $db->begin_transaction();
        try {
            // Get registration details
            $stmt = $db->prepare('SELECT * FROM pharmacy_registrations WHERE id = ?');
            $stmt->bind_param('i', $regId);
            $stmt->execute();
            $reg = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($reg && $reg['status'] === 'pending') {
                // Create pharmacy record
                $stmt = $db->prepare('INSERT INTO pharmacies (registration_id, owner_user_id, name, business_name, license_number, license_type, address, latitude, longitude, phone, email, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->bind_param('iissssssddsi', $regId, $reg['user_id'], $reg['pharmacy_name'], $reg['business_name'], $reg['license_number'], $reg['license_type'], $reg['address'], $reg['latitude'], $reg['longitude'], $reg['phone'], $reg['email'], $adminId);
                $stmt->execute();
                $stmt->close();
                
                // Update registration status
                $stmt = $db->prepare('UPDATE pharmacy_registrations SET status = "approved", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
                $stmt->bind_param('ii', $adminId, $regId);
                $stmt->execute();
                $stmt->close();
                
                // Update user role if needed
                $stmt = $db->prepare('UPDATE users SET role = "pharmacy_owner" WHERE id = ?');
                $stmt->bind_param('i', $reg['user_id']);
                $stmt->execute();
                $stmt->close();
                
                $db->commit();
                $success = 'Pharmacy approved successfully.';
            }
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Approval failed: ' . $e->getMessage();
        }
    } elseif ($action === 'reject' && $regId) {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $stmt = $db->prepare('UPDATE pharmacy_registrations SET status = "rejected", rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
        $stmt->bind_param('sii', $reason, $adminId, $regId);
        $stmt->execute();
        $stmt->close();
        $success = 'Registration rejected.';
    }
}

// Get pending registrations
$stmt = $db->prepare('SELECT pr.*, u.email as user_email FROM pharmacy_registrations pr JOIN users u ON pr.user_id = u.id WHERE pr.status = "pending" ORDER BY pr.created_at DESC');
$stmt->execute();
$pending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Pharmacy Approvals | MediFinder Admin</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Pharmacy registration approvals" />
    <meta name="keywords" content="pharmacy approvals, admin" />
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
        $pageTitle = 'Pharmacy Approvals';
        $breadcrumbItems = ['Admin', 'Approvals'];
        $activeItem = 'Pharmacy Approvals';
        include __DIR__ . '/../includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <?php if ($success): ?>
            <div class="col-span-12">
              <div class="alert alert-success">
                <i class="feather icon-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="col-span-12">
              <div class="alert alert-danger">
                <i class="feather icon-alert-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="col-span-12">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Pharmacy Registration Approvals</h5>
              </div>
              <div class="card-body">
                <?php if (empty($pending)): ?>
                  <div class="text-center py-4 text-muted">
                    <i class="feather icon-check-circle text-[48px] mb-3 opacity-25"></i>
                    <p class="mb-0">No pending registrations.</p>
                  </div>
                <?php else: ?>
                  <div class="grid grid-cols-12 gap-x-6">
                    <?php foreach ($pending as $reg): ?>
                      <div class="col-span-12 xl:col-span-6 md:col-span-6">
                        <div class="card border-warning">
                          <div class="card-header bg-warning-50">
                            <div class="flex items-center justify-between">
                              <strong>Pending Approval</strong>
                              <small class="text-muted"><?php echo date('M d, Y', strtotime($reg['created_at'])); ?></small>
                            </div>
                          </div>
                          <div class="card-body">
                            <h5 class="mb-3"><?php echo htmlspecialchars($reg['pharmacy_name']); ?></h5>
                            <div class="mb-2">
                              <strong>Owner:</strong> <?php echo htmlspecialchars($reg['owner_name']); ?>
                            </div>
                            <div class="mb-2 text-muted small">
                              <strong>Email:</strong> <?php echo htmlspecialchars($reg['user_email']); ?>
                            </div>
                            <div class="mb-2 text-muted small">
                              <strong>License:</strong> <?php echo htmlspecialchars($reg['license_number']); ?> (<?php echo ucfirst($reg['license_type']); ?>)
                            </div>
                            <div class="mb-2 text-muted small">
                              <strong>Address:</strong> <?php echo htmlspecialchars($reg['address']); ?>
                            </div>
                            <div class="mb-2 text-muted small">
                              <strong>Phone:</strong> <?php echo htmlspecialchars($reg['phone']); ?>
                            </div>
                            <?php if ($reg['license_file_path']): ?>
                              <div class="mb-3">
                                <a href="/medi/<?php echo htmlspecialchars($reg['license_file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                  <i class="feather icon-file-text mr-1"></i>View License
                                </a>
                              </div>
                            <?php endif; ?>
                            
                            <form method="post" class="mt-3">
                              <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                              <div class="flex gap-2">
                                <button type="submit" name="action" value="approve" class="btn btn-success">
                                  <i class="feather icon-check mr-1"></i>Approve
                                </button>
                                <button type="button" class="btn btn-danger" onclick="document.getElementById('rejectModal<?php echo $reg['id']; ?>').classList.add('show'); document.getElementById('rejectModal<?php echo $reg['id']; ?>').style.display='block';">
                                  <i class="feather icon-x mr-1"></i>Reject
                                </button>
                              </div>
                            </form>
                            
                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?php echo $reg['id']; ?>" tabindex="-1" style="display: none;">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Reject Registration</h5>
                                    <button type="button" class="btn-close" onclick="document.getElementById('rejectModal<?php echo $reg['id']; ?>').style.display='none';"></button>
                                  </div>
                                  <form method="post">
                                    <div class="modal-body">
                                      <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                      <input type="hidden" name="action" value="reject">
                                      <label class="form-label">Reason for rejection:</label>
                                      <textarea class="form-control" name="rejection_reason" rows="3" required></textarea>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal<?php echo $reg['id']; ?>').style.display='none';">Cancel</button>
                                      <button type="submit" class="btn btn-danger">
                                        <i class="feather icon-x mr-1"></i>Confirm Rejection
                                      </button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
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
