<?php
session_start();
require_once __DIR__ . '/includes/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: /medi/auth/login.php'); exit; }
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $time = trim($_POST['remind_at'] ?? '');
    if ($title !== '' && $time !== '') {
        $stmt = $db->prepare('INSERT INTO reminders (user_id, title, remind_at) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $userId, $title, $time);
        $stmt->execute();
        $stmt->close();
        header('Location: reminders.php');
        exit;
    }
}

$reminders = [];
$res = $db->prepare('SELECT id, title, remind_at FROM reminders WHERE user_id = ? ORDER BY remind_at ASC');
$res->bind_param('i', $userId);
$res->execute();
$r = $res->get_result();
while ($row = $r->fetch_assoc()) { $reminders[] = $row; }
$res->close();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Reminders | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Manage your medicine reminders" />
    <meta name="keywords" content="medicine reminders, notifications, health" />
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
        $pageTitle = 'Reminders';
        $breadcrumbItems = ['Reminders'];
        $activeItem = 'Manage';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 xl:col-span-5">
            <div class="card">
              <div class="card-header">
                <h5>Create a reminder</h5>
              </div>
              <div class="card-body">
                <form method="post">
                  <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" placeholder="e.g., Take Paracetamol 500mg" required />
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Remind at</label>
                    <input type="datetime-local" class="form-control" name="remind_at" required />
                  </div>
                  <button class="btn btn-primary w-100" type="submit">
                    <i class="feather icon-save mr-2"></i>Save reminder
                  </button>
                </form>
                <div class="text-muted small mt-3">
                  <i class="feather icon-bell mr-1"></i>Browser notifications will prompt at the scheduled time.
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 xl:col-span-7">
            <div class="card">
              <div class="card-header">
                <h5>Your reminders</h5>
              </div>
              <div class="card-body">
                <?php if (empty($reminders)): ?>
                  <div class="text-center py-4 text-muted">
                    <i class="feather icon-bell-off text-[48px] mb-3 opacity-25"></i>
                    <p class="mb-0">No reminders yet.</p>
                  </div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <tbody>
                        <?php foreach ($reminders as $rm): ?>
                          <tr>
                            <td>
                              <div class="flex items-center gap-3">
                                <i class="feather icon-bell text-primary-500"></i>
                                <div>
                                  <h6 class="mb-1"><?php echo htmlspecialchars($rm['title']); ?></h6>
                                  <small class="text-muted">
                                    <i class="feather icon-clock mr-1"></i>
                                    <?php echo htmlspecialchars($rm['remind_at']); ?>
                                  </small>
                                </div>
                              </div>
                            </td>
                            <td class="text-end">
                              <button class="btn btn-sm btn-outline-success" onclick="scheduleReminder(<?php echo (int)$rm['id']; ?>, '<?php echo htmlspecialchars($rm['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($rm['remind_at'], ENT_QUOTES); ?>')">
                                <i class="feather icon-play mr-1"></i>Schedule
                              </button>
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

    <script>
    async function ensurePermission() {
        if (!('Notification' in window)) return false;
        if (Notification.permission === 'granted') return true;
        const res = await Notification.requestPermission();
        return res === 'granted';
    }
    function scheduleReminder(id, title, when) {
        ensurePermission().then((ok) => {
            if (!ok) { alert('Enable notifications to schedule reminders.'); return; }
            const ts = new Date(when).getTime();
            const now = Date.now();
            const delay = Math.max(0, ts - now);
            setTimeout(() => {
                new Notification('MediFinder Reminder', { body: title, icon: '/medi/assets/img/medifinder-logo.svg' });
            }, delay);
            alert('Reminder scheduled in this browser. Keep this tab open.');
        });
    }
    </script>
  </body>
  <!-- [Body] end -->
</html>
