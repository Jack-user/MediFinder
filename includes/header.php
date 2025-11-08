<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$role = $_SESSION['user_role'] ?? 'patient';
?>
<!-- [ Header Topbar ] start -->
<header class="pc-header">
  <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">
    <!-- [Mobile Media Block] start -->
    <div class="me-auto pc-mob-drp">
      <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
        <!-- ======= Menu collapse Icon ===== -->
        <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
          <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide">
            <i data-feather="menu"></i>
          </a>
        </li>
        <li class="pc-h-item pc-sidebar-popup lg:hidden">
          <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse">
            <i data-feather="menu"></i>
          </a>
        </li>
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" aria-expanded="false">
            <i data-feather="search"></i>
          </a>
          <div class="dropdown-menu pc-h-dropdown drp-search">
            <form class="px-2 py-1">
              <input type="search" class="form-control !border-0 !shadow-none" placeholder="Search here. . ." />
            </form>
          </div>
        </li>
      </ul>
    </div>
    <!-- [Mobile Media Block end] -->
    <!-- <div class="ms-auto">
      <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" aria-expanded="false">
            <i data-feather="sun"></i>
          </a>
          <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
            <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
              <i data-feather="moon"></i>
              <span>Darks</span>
            </a>
            <a href="#!" class="dropdown-item" onclick="layout_change('light')">
              <i data-feather="sun"></i>
              <span>Light</span>
            </a>
            <a href="#!" class="dropdown-item" onclick="layout_change_default()">
              <i data-feather="settings"></i>
              <span>Default</span>
            </a>
          </div> -->
        </li>
        <?php if ($isLoggedIn): ?>
        <li class="dropdown pc-h-item">
          <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" aria-expanded="false">
            <i data-feather="bell"></i>
          </a>
          <div class="dropdown-menu dropdown-notification dropdown-menu-end pc-h-dropdown p-2">
            <div class="dropdown-header flex items-center justify-between py-4 px-5">
              <h5 class="m-0">Notifications</h5>
              <a href="#!" class="btn btn-link btn-sm">Mark all read</a>
            </div>
            <div class="dropdown-body header-notification-scroll relative py-4 px-5"
              style="max-height: calc(100vh - 215px)">
              <p class="text-span mb-3">No new notifications</p>
            </div>
          </div>
        </li>
        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">
            <i data-feather="user"></i>
          </a>
          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">
            <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
              <div class="flex mb-1 items-center">
                <div class="shrink-0">
                  <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-primary-500 font-bold">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                  </div>
                </div>
                <div class="grow ms-3">
                  <h6 class="mb-1 text-white"><?php echo htmlspecialchars($userName); ?></h6>
                  <span class="text-white text-sm"><?php echo htmlspecialchars($userEmail ?: 'User'); ?></span>
                </div>
              </div>
            </div>
            <div class="dropdown-body py-4 px-5">
              <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 225px)">
                <a href="/medi/dashboard.php" class="dropdown-item">
                  <span>
                    <svg class="pc-icon text-muted me-2 inline-block">
                      <use xlink:href="#custom-setting-outline"></use>
                    </svg>
                    <span>Dashboard</span>
                  </span>
                </a>
                <a href="/medi/auth/logout.php" class="dropdown-item">
                  <span>
                    <svg class="pc-icon text-muted me-2 inline-block">
                      <use xlink:href="#custom-lock-outline"></use>
                    </svg>
                    <span>Logout</span>
                  </span>
                </a>
                <div class="grid my-3">
                  <a href="/medi/auth/logout.php" class="btn btn-primary flex items-center justify-center">
                    <svg class="pc-icon me-2 w-[22px] h-[22px]">
                      <use xlink:href="#custom-logout-1-outline"></use>
                    </svg>
                    Logout
                  </a>
                </div>
              </div>
            </div>
          </div>
        </li>
        <?php else: ?>
        <li class="pc-h-item">
          <a href="/medi/auth/login.php" class="btn btn-primary btn-sm">Login</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</header>
<!-- [ Header ] end -->

