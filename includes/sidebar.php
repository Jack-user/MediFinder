<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['user_role'] ?? 'patient';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

function navActive(string $path, string $currentPath): string {
  return strpos($currentPath, $path) === 0 ? ' active' : '';
}
?>
<style>
  .pc-sidebar.white-theme {
    background: #ffffff;
    color: #000000;
    box-shadow: 4px 0 24px rgba(15, 23, 42, 0.06);
  }
  .pc-sidebar.white-theme .navbar-wrapper {
    background: transparent;
  }
  .pc-sidebar.white-theme .sidebar-brand {
    display: flex;
    align-items: center;
    padding: 24px 18px 16px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.18);
  }
  .pc-sidebar.white-theme .sidebar-brand .sidebar-title {
    font-weight: 600;
    font-size: 1.15rem;
    color: #0f172a;
  }
  .pc-sidebar.white-theme .pc-navbar {
    padding: 18px 18px 32px;
  }
  .pc-sidebar.white-theme .pc-item {
    margin-bottom: 6px;
  }
  .pc-sidebar.white-theme .pc-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    color: #0f172a;
    font-weight: 500;
    transition: background 0.2s ease, color 0.2s ease;
    justify-content: flex-start;
  }
  .pc-sidebar.white-theme .pc-link:hover {
    background: rgba(13, 110, 253, 0.08);
    color: #0d6efd;
  }
  .pc-sidebar.white-theme .pc-item.active > .pc-link,
  .pc-sidebar.white-theme .pc-link.active {
    background: rgba(13, 110, 253, 0.14);
    color: #0d6efd;
    font-weight: 600;
  }
  .pc-sidebar.white-theme .pc-micon {
    width: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: inherit;
  }
  .pc-sidebar.white-theme .pc-caption label {
    font-size: 0.72rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #94a3b8;
    margin: 16px 0 8px;
    display: inline-block;
  }
</style>
<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar white-theme">
  <div class="navbar-wrapper">
    <div class="sidebar-brand">
      <a href="/medi/dashboard.php" class="d-flex align-items-center text-decoration-none">
        <span class="sidebar-title">MediFinder</span>
      </a>
    </div>
    <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
      <ul class="pc-navbar">
        <li class="pc-item pc-caption">
          <label>Navigation</label>
        </li>
        <?php if ($isLoggedIn): ?>
          <?php if ($role === 'pharmacy_owner'): ?>
            <li class="pc-item">
              <a href="/medi/pharmacy/index.php" class="pc-link<?php echo navActive('/medi/pharmacy/index.php', $currentPath); ?>">
                <span class="pc-micon">
                  <i class="feather icon-home"></i>
                </span>
                <span class="pc-mtext">Pharmacy</span>
              </a>
            </li>
            <li class="pc-item">
              <a href="/medi/pharmacy_inventory.php" class="pc-link<?php echo navActive('/medi/pharmacy_inventory.php', $currentPath); ?>">
                <span class="pc-micon">
                  <i class="feather icon-package"></i>
                </span>
                <span class="pc-mtext">Inventory</span>
              </a>
            </li>
            <li class="pc-item">
              <a href="/medi/pharmacy_location.php" class="pc-link<?php echo navActive('/medi/pharmacy_location.php', $currentPath); ?>">
                <span class="pc-micon">
                  <i class="feather icon-map-pin"></i>
                </span>
                <span class="pc-mtext">Location</span>
              </a>
            </li>
          <?php elseif ($role === 'admin'): ?>
            <li class="pc-item">
              <a href="/medi/admin/pharmacy_approvals.php" class="pc-link<?php echo navActive('/medi/admin/pharmacy_approvals.php', $currentPath); ?>">
                <span class="pc-micon">
                  <i class="feather icon-shield"></i>
                </span>
                <span class="pc-mtext">Approvals</span>
              </a>
            </li>
            <li class="pc-item">
              <a href="/medi/admin/index.php" class="pc-link<?php echo navActive('/medi/admin/index.php', $currentPath); ?>">
                <span class="pc-micon">
                  <i class="feather icon-activity"></i>
                </span>
                <span class="pc-mtext">Dashboard</span>
              </a>
            </li>
          <?php else: ?>
            <li class="pc-item">
              <a href="/medi/patient/index.php" class="pc-link<?php echo navActive('/medi/patient/index.php', $currentPath); ?>">
                <span class="pc-micon">
                  <i class="feather icon-home"></i>
                </span>
                <span class="pc-mtext">Dashboard</span>
              </a>
            </li>
          <?php endif; ?>
        <?php else: ?>
          <li class="pc-item">
            <a href="/medi/index.php" class="pc-link<?php echo navActive('/medi/index.php', $currentPath); ?>">
              <span class="pc-micon">
                <i class="feather icon-home"></i>
              </span>
              <span class="pc-mtext">Home</span>
            </a>
          </li>
        <?php endif; ?>
        
        <?php if ($isLoggedIn && $role === 'patient'): ?>
          <li class="pc-item pc-caption">
            <label>Patient Tools</label>
          </li>
          <li class="pc-item">
            <a href="/medi/prescription.php" class="pc-link<?php echo navActive('/medi/prescription.php', $currentPath); ?>">
              <span class="pc-micon">
                <i class="feather icon-camera"></i>
              </span>
              <span class="pc-mtext">Upload Prescription</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/manual.php" class="pc-link<?php echo navActive('/medi/manual.php', $currentPath); ?>">
              <span class="pc-micon">
                <i class="feather icon-edit"></i>
              </span>
              <span class="pc-mtext">Manual Input</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/locator.php" class="pc-link<?php echo navActive('/medi/locator.php', $currentPath); ?>">
              <span class="pc-micon">
                <i class="feather icon-map"></i>
              </span>
              <span class="pc-mtext">Find Pharmacies</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/reminders.php" class="pc-link<?php echo navActive('/medi/reminders.php', $currentPath); ?>">
              <span class="pc-micon">
                <i class="feather icon-bell"></i>
              </span>
              <span class="pc-mtext">Reminders</span>
            </a>
          </li>
        <?php endif; ?>
        
        <li class="pc-item pc-caption">
          <label>Account</label>
        </li>
        <?php if ($isLoggedIn): ?>
          <li class="pc-item">
            <a href="/medi/auth/logout.php" class="pc-link text-danger-500">
              <span class="pc-micon">
                <i class="feather icon-log-out"></i>
              </span>
              <span class="pc-mtext">Logout</span>
            </a>
          </li>
        <?php else: ?>
          <li class="pc-item">
            <a href="/medi/auth/login.php" class="pc-link<?php echo navActive('/medi/auth/login.php', $currentPath); ?>">
              <span class="pc-micon">
                <i class="feather icon-log-in"></i>
              </span>
              <span class="pc-mtext">Login</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/auth/register_pharmacy.php" class="pc-link<?php echo navActive('/medi/auth/register_pharmacy.php', $currentPath); ?>">
              <span class="pc-micon">
                <i class="feather icon-briefcase"></i>
              </span>
              <span class="pc-mtext">Register Pharmacy</span>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<!-- [ Sidebar Menu ] end -->
