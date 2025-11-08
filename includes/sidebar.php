<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['user_role'] ?? 'patient';
?>
<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="m-header flex items-center py-4 px-6 h-header-height">
      <a href="/medi/dashboard.php" class="b-brand flex items-center gap-3">
        <!-- ========   Change your logo from here   ============ -->
        <img src="/medi/assets/img/medifinder-logo.svg" class="img-fluid logo logo-lg" alt="MediFinder" style="width: 32px; height: 32px;" />
        <img src="/medi/assets/img/medifinder-logo.svg" class="img-fluid logo logo-sm" alt="MediFinder" style="width: 32px; height: 32px;" />
        <span class="font-semibold text-lg">MediFinder</span>
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
              <a href="/medi/pharmacy/index.php" class="pc-link">
                <span class="pc-micon">
                  <i data-feather="store"></i>
                </span>
                <span class="pc-mtext">Pharmacy</span>
              </a>
            </li>
            <li class="pc-item">
              <a href="/medi/pharmacy_inventory.php" class="pc-link">
                <span class="pc-micon">
                  <i data-feather="package"></i>
                </span>
                <span class="pc-mtext">Inventory</span>
              </a>
            </li>
            <li class="pc-item">
              <a href="/medi/pharmacy_location.php" class="pc-link">
                <span class="pc-micon">
                  <i data-feather="map-pin"></i>
                </span>
                <span class="pc-mtext">Location</span>
              </a>
            </li>
          <?php elseif ($role === 'admin'): ?>
            <li class="pc-item">
              <a href="/medi/admin/pharmacy_approvals.php" class="pc-link">
                <span class="pc-micon">
                  <i data-feather="shield"></i>
                </span>
                <span class="pc-mtext">Approvals</span>
              </a>
            </li>
            <li class="pc-item">
              <a href="/medi/admin/index.php" class="pc-link">
                <span class="pc-micon">
                  <i data-feather="home"></i>
                </span>
                <span class="pc-mtext">Dashboard</span>
              </a>
            </li>
          <?php else: ?>
            <li class="pc-item">
              <a href="/medi/patient/index.php" class="pc-link">
                <span class="pc-micon">
                  <i data-feather="home"></i>
                </span>
                <span class="pc-mtext">Dashboard</span>
              </a>
            </li>
          <?php endif; ?>
        <?php else: ?>
          <li class="pc-item">
            <a href="/medi/index.php" class="pc-link">
              <span class="pc-micon">
                <i data-feather="home"></i>
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
            <a href="/medi/prescription.php" class="pc-link">
              <span class="pc-micon">
                <i data-feather="camera"></i>
              </span>
              <span class="pc-mtext">Upload Prescription</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/manual.php" class="pc-link">
              <span class="pc-micon">
                <i data-feather="edit"></i>
              </span>
              <span class="pc-mtext">Manual Input</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/locator.php" class="pc-link">
              <span class="pc-micon">
                <i data-feather="map"></i>
              </span>
              <span class="pc-mtext">Find Pharmacies</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/reminders.php" class="pc-link">
              <span class="pc-micon">
                <i data-feather="bell"></i>
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
                <i data-feather="log-out"></i>
              </span>
              <span class="pc-mtext">Logout</span>
            </a>
          </li>
        <?php else: ?>
          <li class="pc-item">
            <a href="/medi/auth/login.php" class="pc-link">
              <span class="pc-micon">
                <i data-feather="log-in"></i>
              </span>
              <span class="pc-mtext">Login</span>
            </a>
          </li>
          <li class="pc-item">
            <a href="/medi/auth/register_pharmacy.php" class="pc-link">
              <span class="pc-micon">
                <i data-feather="store"></i>
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
