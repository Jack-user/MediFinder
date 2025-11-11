<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Redirect to home if accessed directly (not via POST from modal)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /medi/index.php');
    exit;
}

$errors = [];
$successMessage = null;
$successRedirect = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $role = $_POST['role'] ?? 'patient';

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    // Restrict allowed roles from this public form
    $allowedRoles = ['patient', 'pharmacy_owner'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'patient';
    }

    if (!$errors) {
        // Check duplicate
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email already registered.';
        }
        $stmt->close();

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->bind_param('ssss', $name, $email, $hash, $role);
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = $role;
                // Route user based on role
                if ($role === 'pharmacy_owner') {
                    $successRedirect = '/medi/auth/register_pharmacy.php';
                    $successMessage = 'Account created! Continue your pharmacy registration.';
                } else {
                    $successRedirect = '/medi/patient/';
                    $successMessage = 'Account created successfully! Redirecting to your dashboard.';
                }
            } else {
                $errors[] = 'Registration failed. Try again.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account — MediFinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="/medi/assets/css/style.css" rel="stylesheet" />
</head>
<body>
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 15px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") no-repeat center/cover;
            pointer-events: none;
        }

        .registration-card {
            position: relative;
            background: #fff;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 920px;
            width: 100%;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: #fff;
            padding: 45px 40px 35px;
            text-align: center;
            position: relative;
        }

        .card-header-custom::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .logo-badge {
            width: 80px;
            height: 80px;
            background: #fff;
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 35px rgba(0,0,0,0.22);
            margin-bottom: 20px;
        }

        .logo-badge img {
            width: 48px;
            height: 48px;
        }

        .card-header-custom h3 {
            font-weight: 700;
            font-size: 2.1rem;
            margin-bottom: 10px;
            position: relative;
        }

        .card-header-custom p {
            font-size: 1rem;
            opacity: 0.95;
            position: relative;
            max-width: 560px;
            margin: 0 auto;
        }

        .card-body-custom {
            padding: 40px 45px 45px;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 35px;
            position: relative;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 22px;
            left: 12%;
            right: 12%;
            height: 2px;
            background: #e2e8f0;
        }

        .progress-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .step-circle.active {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.45);
        }

        .step-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 16px;
            margin-bottom: 22px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .section-header h5 {
            margin: 0;
            font-weight: 700;
            color: #1e293b;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.25s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.12);
            transform: translateY(-1px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            border: none;
            border-radius: 14px;
            padding: 15px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 35px rgba(102, 126, 234, 0.55);
        }

        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #475569;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 16px;
            border: none;
            padding: 18px 20px;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.92);
            text-decoration: none;
            margin-bottom: 24px;
            padding: 10px 18px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.14);
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-4px);
        }

        @media (max-width: 767px) {
            body {
                padding: 24px 12px;
            }

            .registration-card {
                border-radius: 24px;
            }

            .card-body-custom {
                padding: 32px 24px 35px;
            }

            .progress-steps::before {
                left: 18%;
                right: 18%;
            }
        }
    </style>
    <div class="registration-layout" style="width: 100%; max-width: 960px;">
        <a href="/medi/auth/login.php   " class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
        <div class="registration-card">
            <div class="card-header-custom">
                <div class="logo-badge">
                    <img src="/medi/assets/img/medifinder-logo.svg" alt="MediFinder">
                </div>
                <h3>Create Your MediFinder Account</h3>
                <p>Manage reminders, save favorite pharmacies, and get personalized medicine alerts.</p>
            </div>
            <div class="card-body-custom">
                <div class="progress-steps">
                    <div class="progress-step">
                        <div class="step-circle active">1</div>
                        <div class="step-label">Profile</div>
                    </div>
                    <div class="progress-step">
                        <div class="step-circle active">2</div>
                        <div class="step-label">Account</div>
                    </div>
                    <div class="progress-step">
                        <div class="step-circle active">3</div>
                        <div class="step-label">Finish</div>
                    </div>
                </div>
                <?php if ($errors): ?>
                    <div class="alert alert-danger d-none" role="alert">
                        <strong>Please fix the following:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" novalidate>
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <h5>Basic Information</h5>
                    </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Jane Dela Cruz" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
                        </div>
                    </div>

                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-briefcase-medical"></i>
                        </div>
                        <h5>Account Type</h5>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Choose how you’ll use MediFinder <span class="text-danger">*</span></label>
                        <select class="form-select" name="role" id="roleSelect" required>
                            <?php $sel = $_POST['role'] ?? 'patient'; ?>
                            <option value="patient" <?php echo $sel==='patient'?'selected':''; ?>>Client — find medicines & set reminders</option>
                            <option value="pharmacy_owner" <?php echo $sel==='pharmacy_owner'?'selected':''; ?>>Pharmacy Owner — list your pharmacy</option>
                        </select>
                        <small class="text-muted d-block mt-2">Need an admin account? Contact support — admins are created internally.</small>
                    </div>

                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h5>Security</h5>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" placeholder="Minimum 6 characters" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirm" placeholder="Re-type password" required />
                        </div>
                    </div>
                    <div class="d-grid gap-3 mt-5">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="fas fa-paper-plane me-2"></i>Create Account
                        </button>
                        <a class="btn btn-outline-secondary" href="/medi/auth/login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>Already registered? Sign in
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" integrity="sha512-Bx1wTuK0vGdgJo+d5QNt/hJik7l2c3hUlY0wyK8fAwIMv0YYemAgFJBFA8QgJFK0tJdVvLv/MxJ7N7x+Yp9aQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        const roleSelect = document.getElementById('roleSelect');
        if (roleSelect) {
            roleSelect.addEventListener('change', function () {
                if (this.value === 'pharmacy_owner') {
                    window.location.href = '/medi/auth/register_pharmacy.php';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const successMessage = <?php echo json_encode($successMessage); ?>;
            const successRedirect = <?php echo json_encode($successRedirect); ?>;
            const errorMessages = <?php echo json_encode($errors ? array_map('htmlspecialchars', $errors) : []); ?>;

            if (successMessage) {
                Swal.fire({
                    icon: 'success',
                    title: 'Registration complete',
                    text: successMessage,
                    confirmButtonColor: '#667eea',
                    timer: 2200,
                    timerProgressBar: true,
                }).then(() => {
                    window.location.href = successRedirect || '/medi/';
                });
            } else if (errorMessages.length) {
                const htmlList = `<ul class="text-start mb-0">${errorMessages.map(msg => `<li>${msg}</li>`).join('')}</ul>`;
                Swal.fire({
                    icon: 'error',
                    title: 'Please check the form',
                    html: htmlList,
                    confirmButtonColor: '#667eea'
                });
            }
        });
    </script>
    </body>
    </html>