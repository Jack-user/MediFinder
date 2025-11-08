<?php
// session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get statistics
$stats = [
    'total_uploads' => 0,
    'total_reminders' => 0,
    'upcoming_reminders' => 0,
];

// Count total prescription uploads
$stmt = $db->prepare('SELECT COUNT(*) as cnt FROM uploads WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_uploads'] = $result->fetch_assoc()['cnt'];
$stmt->close();

// Count total reminders
$stmt = $db->prepare('SELECT COUNT(*) as cnt FROM reminders WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_reminders'] = $result->fetch_assoc()['cnt'];
$stmt->close();

// Count upcoming reminders (within next 7 days)
$stmt = $db->prepare('SELECT COUNT(*) as cnt FROM reminders WHERE user_id = ? AND remind_at >= NOW() AND remind_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$stats['upcoming_reminders'] = $result->fetch_assoc()['cnt'];
$stmt->close();

// Get recent prescription uploads (last 5)
$recentUploads = [];
$stmt = $db->prepare('SELECT id, original_name, extracted_text, created_at FROM uploads WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentUploads[] = $row;
}
$stmt->close();

// Get upcoming reminders (next 5)
$upcomingReminders = [];
$stmt = $db->prepare('SELECT id, title, remind_at FROM reminders WHERE user_id = ? AND remind_at >= NOW() ORDER BY remind_at ASC LIMIT 5');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcomingReminders[] = $row;
}
$stmt->close();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
<head>
    <title>Dashboard | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="MediFinder - Find the right medicine fast" />
    <meta name="keywords" content="medicine finder, pharmacy locator, prescription analyzer" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="assets/img/medifinder-logo.svg" type="image/x-icon" />

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
        $pageTitle = 'Dashboard';
        $breadcrumbItems = [];
        $activeItem = 'Dashboard';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
        <!-- Welcome Section -->
          <div class="col-span-12 mb-4">
            <div class="card">
              <div class="card-body">
                <h2 class="mb-2">Welcome back, <?php echo htmlspecialchars($userName); ?>!</h2>
                <p class="text-muted mb-0">Here's your health and medicine overview</p>
                    </div>
            </div>
            </div>

        <!-- Statistics Cards -->
          <div class="col-span-12 xl:col-span-4 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>Prescriptions Uploaded</h5>
              </div>
                    <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-file-text text-primary-500 text-[30px] mr-1.5"></i>
                    <?php echo $stats['total_uploads']; ?>
                  </h3>
                            </div>
                        </div>
                    </div>
                </div>
          <div class="col-span-12 xl:col-span-4 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>Total Reminders</h5>
            </div>
                    <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-bell text-warning-500 text-[30px] mr-1.5"></i>
                    <?php echo $stats['total_reminders']; ?>
                  </h3>
                            </div>
                        </div>
                    </div>
                </div>
          <div class="col-span-12 xl:col-span-4 md:col-span-6">
            <div class="card">
              <div class="card-header !pb-0 !border-b-0">
                <h5>Upcoming (7 days)</h5>
            </div>
                    <div class="card-body">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <h3 class="font-light flex items-center mb-0">
                    <i class="feather icon-calendar text-success-500 text-[30px] mr-1.5"></i>
                    <?php echo $stats['upcoming_reminders']; ?>
                  </h3>
                    </div>
                </div>
            </div>
        </div>

            <!-- Recent Prescriptions -->
          <div class="col-span-12 xl:col-span-6">
            <div class="card">
              <div class="card-header flex items-center justify-between">
                <h5 class="mb-0">
                  <i class="feather icon-clock mr-2 text-primary-500"></i>Recent Prescriptions
                </h5>
                        <a href="prescription.php" class="btn btn-sm btn-outline-primary">Upload New</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentUploads)): ?>
                  <div class="text-center py-4 text-muted">
                    <i class="feather icon-file-text text-[48px] mb-3 opacity-25"></i>
                                <p class="mb-0">No prescriptions uploaded yet</p>
                                <a href="prescription.php" class="btn btn-sm btn-primary mt-3">Upload Your First</a>
                            </div>
                        <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <tbody>
                                <?php foreach ($recentUploads as $upload): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <i class="feather icon-file-text text-primary-500"></i>
                                <div>
                                  <h6 class="mb-1"><?php echo htmlspecialchars($upload['original_name'] ?: 'Prescription'); ?></h6>
                                  <p class="text-muted small mb-0">
                                                    <?php 
                                                    $text = $upload['extracted_text'] ?? '';
                                                    echo htmlspecialchars(mb_substr($text, 0, 80)) . (mb_strlen($text) > 80 ? '...' : '');
                                                    ?>
                                                </p>
                                                <small class="text-muted">
                                    <i class="feather icon-calendar mr-1"></i>
                                                    <?php echo date('M d, Y g:i A', strtotime($upload['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                            </td>
                          </tr>
                                <?php endforeach; ?>
                      </tbody>
                    </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Reminders -->
          <div class="col-span-12 xl:col-span-6">
            <div class="card">
              <div class="card-header flex items-center justify-between">
                <h5 class="mb-0">
                  <i class="feather icon-bell mr-2 text-warning-500"></i>Upcoming Reminders
                </h5>
                        <a href="reminders.php" class="btn btn-sm btn-outline-warning">Manage</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingReminders)): ?>
                  <div class="text-center py-4 text-muted">
                    <i class="feather icon-bell-off text-[48px] mb-3 opacity-25"></i>
                                <p class="mb-0">No upcoming reminders</p>
                                <a href="reminders.php" class="btn btn-sm btn-warning mt-3">Set Reminder</a>
                            </div>
                        <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <tbody>
                                <?php foreach ($upcomingReminders as $reminder): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <i class="feather icon-pill text-success-500"></i>
                                <div>
                                  <h6 class="mb-1"><?php echo htmlspecialchars($reminder['title']); ?></h6>
                                                <small class="text-muted">
                                    <i class="feather icon-clock mr-1"></i>
                                                    <?php 
                                                    $remindAt = strtotime($reminder['remind_at']);
                                                    $now = time();
                                                    $diff = $remindAt - $now;
                                                    if ($diff < 3600) {
                                                        echo 'In ' . round($diff / 60) . ' minutes';
                                                    } elseif ($diff < 86400) {
                                                        echo 'In ' . round($diff / 3600) . ' hours';
                                                    } else {
                                                        echo date('M d, Y g:i A', $remindAt);
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                            </td>
                          </tr>
                                <?php endforeach; ?>
                      </tbody>
                    </table>
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
    
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/footer-js.php'; ?>
</body>
  <!-- [Body] end -->
</html>


