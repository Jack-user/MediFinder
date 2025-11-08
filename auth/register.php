<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$errors = [];
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
                    header('Location: /medi/auth/register_pharmacy.php');
                } else {
                    header('Location: /medi/patient/');
                }
                exit;
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
<body class="bg-light">
    <div class="container py-5">
        <div class="mx-auto form-auth bg-white p-4 p-md-5 rounded-3 shadow-sm">
            <div class="text-center mb-4">
                <a href="/medi/"><img src="/medi/assets/img/medifinder-logo.svg" width="48" height="48" alt="MediFinder"></a>
                <h3 class="mt-2">Create your MediFinder account</h3>
                <p class="text-secondary">Save reminders and personalize recommendations.</p>
            </div>
            <?php if ($errors): ?>
                <div class="alert alert-danger small">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Account type</label>
                    <select class="form-select" name="role" required>
                        <?php $sel = $_POST['role'] ?? 'patient'; ?>
                        <option value="patient" <?php echo $sel==='patient'?'selected':''; ?>>Client</option>
                        <option value="pharmacy_owner" <?php echo $sel==='pharmacy_owner'?'selected':''; ?>>Pharmacy Owner</option>
                    </select>
                    <div class="form-text">Admins are created via admin panel only.</div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm password</label>
                        <input type="password" class="form-control" name="confirm" required />
                    </div>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-primary btn-lg" type="submit">Create account</button>
                    <a class="btn btn-outline-secondary" href="/medi/auth/login.php">Already have an account? Sign in</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>


