<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /medi/auth/login.php');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$userId = (int) $_SESSION['user_id'];
$errors = [];
$successMessage = null;

$stmt = $db->prepare('SELECT id, name, email, role, created_at, password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: /medi/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        if (!$errors) {
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $stmt->bind_param('si', $email, $userId);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = 'Email address is already in use by another account.';
            }
            $stmt->close();
        }

        if (!$errors) {
            $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $stmt->bind_param('ssi', $name, $email, $userId);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $successMessage = 'Profile details updated successfully.';
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
            }
            $stmt->close();
        }
    } elseif ($action === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'All password fields are required.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Your current password is incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (!$errors) {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $userId);
            if ($stmt->execute()) {
                $successMessage = 'Password updated successfully.';
            } else {
                $errors[] = 'Failed to update password. Please try again.';
            }
            $stmt->close();
        }
    } else {
        $errors[] = 'Unknown action.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — MediFinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/medi/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f8fafc;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 55vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 80px 0 120px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.3;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
            max-width: 1080px;
            width: 100%;
            overflow: hidden;
            margin: -180px auto 60px;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 50px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .profile-body {
            padding: 40px 50px 50px;
            background: white;
        }

        .nav-pills .nav-link {
            border-radius: 14px;
            font-weight: 600;
            color: #475569;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .form-label {
            font-weight: 600;
            color: #334155;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
        }

        .section-title {
            font-weight: 700;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>
    <section class="hero-section text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7 hero-content">
                    <h1 class="display-5 fw-bold">Your MediFinder Profile</h1>
                    <p class="lead mt-3 mb-0">Manage your personal information, update security settings, and keep your account in sync across devices.</p>
                </div>
            </div>
        </div>
    </section>

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
            </div>
            <div>
                <h2 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                <small class="d-block mt-2 text-white-50 text-uppercase">
                    <?php echo htmlspecialchars(str_replace('_', ' ', $user['role'])); ?>
                </small>
            </div>
            <div class="ms-auto">
                <?php if (($user['role'] ?? 'patient') === 'pharmacy_owner'): ?>
                    <a href="/medi/pharmacy_dashboard.php" class="btn btn-light text-primary fw-semibold me-2">
                        <i class="fas fa-store me-2"></i>Pharmacy Dashboard
                    </a>
                <?php elseif (($user['role'] ?? 'patient') === 'admin'): ?>
                    <a href="/medi/admin/pharmacy_approvals.php" class="btn btn-light text-primary fw-semibold me-2">
                        <i class="fas fa-shield-check me-2"></i>Admin Panel
                    </a>
                <?php else: ?>
                    <a href="/medi/dashboard.php" class="btn btn-light text-primary fw-semibold me-2">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                <?php endif; ?>
                <a href="/medi/auth/logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
        <div class="profile-body">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="list-group list-group-flush nav nav-pills flex-column" id="profileTabs" role="tablist">
                        <button class="list-group-item list-group-item-action nav-link active" id="details-tab" data-bs-toggle="pill" data-bs-target="#details" type="button" role="tab">
                            <i class="fas fa-id-badge me-2 text-primary"></i> Account Details
                        </button>
                        <button class="list-group-item list-group-item-action nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                            <i class="fas fa-lock me-2 text-primary"></i> Security
                        </button>
                    </div>
                    <div class="mt-4 p-3 bg-light rounded-3">
                        <h6 class="section-title mb-2">Account Summary</h6>
                        <p class="mb-1"><strong>Member since:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($user['created_at']))); ?></p>
                        <p class="mb-0"><strong>Role:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $user['role']))); ?></p>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="tab-content" id="profileTabContent">
                        <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <h4 class="section-title mb-3">Profile Information</h4>
                            <form method="post" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="profile">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                            <h4 class="section-title mb-3">Password & Security</h4>
                            <form method="post" novalidate>
                                <input type="hidden" name="action" value="password">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" required>
                                    <small class="text-muted">Must be at least 6 characters long.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const errorMessages = <?php echo json_encode($errors); ?>;
            const successMessage = <?php echo json_encode($successMessage); ?>;

            if (successMessage) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: successMessage,
                    confirmButtonColor: '#667eea'
                });
            } else if (errorMessages.length) {
                const htmlList = `<ul class="text-start mb-0">${errorMessages.map(msg => `<li>${msg}</li>`).join('')}</ul>`;
                Swal.fire({
                    icon: 'error',
                    title: 'Please check your input',
                    html: htmlList,
                    confirmButtonColor: '#667eea'
                });
            }
        });
    </script>
</body>
</html>

