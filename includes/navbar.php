<?php
$isLoggedIn = isset($_SESSION['user_id']);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/medi/<?php echo $isLoggedIn ? ((($_SESSION['user_role'] ?? 'patient') === 'pharmacy_owner') ? 'pharmacy_dashboard.php' : (($_SESSION['user_role'] ?? 'patient') === 'admin' ? 'admin/pharmacy_approvals.php' : 'dashboard.php')) : 'index.php'; ?>">
                <img src="/medi/assets/img/medifinder-logo.svg" alt="MediFinder" width="36" height="36" class="me-2">
                <strong>MediFinder</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if ($isLoggedIn): ?>
                        <!-- <li class="nav-item"><a class="nav-link" href="/medi/dashboard.php">Dashboard</a></li> -->
                    <?php endif; ?>
                    <!-- <li class="nav-item"><a class="nav-link" href="/medi/prescription.php">Upload</a></li>
                    <li class="nav-item"><a class="nav-link" href="/medi/manual.php">Manual</a></li>
                    <li class="nav-item"><a class="nav-link" href="/medi/locator.php">Pharmacies</a></li> -->
                    <?php if ($isLoggedIn): ?>
                        <?php if (($_SESSION['user_role'] ?? 'patient') === 'pharmacy_owner'): ?>
                            <li class="nav-item"><a class="nav-link" href="/medi/pharmacy_dashboard.php">Pharmacy</a></li>
                        <?php elseif (($_SESSION['user_role'] ?? 'patient') === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="/medi/admin/pharmacy_approvals.php">Admin</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="/medi/dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="/medi/reminders.php">Reminders</a></li>
                        <li class="nav-item"><a class="btn btn-outline-danger ms-lg-2" href="/medi/auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/medi/auth/register_pharmacy.php">Register Pharmacy</a></li>
                        <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="/medi/auth/login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>