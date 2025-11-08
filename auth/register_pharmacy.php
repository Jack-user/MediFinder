<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User account info
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    // Pharmacy info
    $pharmacyName = trim($_POST['pharmacy_name'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $licenseNumber = trim($_POST['license_number'] ?? '');
    $licenseType = $_POST['license_type'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $ownerContact = trim($_POST['owner_contact'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $errors[] = 'All user account fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    
    if (empty($pharmacyName) || empty($licenseNumber) || empty($licenseType) || empty($address) || empty($phone) || empty($ownerContact)) {
        $errors[] = 'All pharmacy information fields are required.';
    }
    
    if (empty($latitude) || empty($longitude)) {
        $errors[] = 'Please pin your pharmacy location on the map.';
    }
    
    // Check duplicate email
    if (!$errors) {
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email already registered.';
        }
        $stmt->close();
    }
    
    // Require license details for validation
    if ($licenseNumber === '') {
        $errors[] = 'License/permit number is required.';
    }

    // Require license document upload (PDF/JPG/PNG, <= 5MB)
    $licenseFilePath = null;
    if (!isset($_FILES['license_file']) || $_FILES['license_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload a copy of your license/permit (PDF or Image).';
    } else {
        $allowed = ['application/pdf','image/jpeg','image/png','image/jpg'];
        $mime = mime_content_type($_FILES['license_file']['tmp_name']);
        $size = (int)($_FILES['license_file']['size'] ?? 0);
        if (!in_array($mime, $allowed, true)) {
            $errors[] = 'License file must be a PDF, JPG, or PNG.';
        } elseif ($size > 5 * 1024 * 1024) {
            $errors[] = 'License file must be 5 MB or smaller.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/licenses/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $ext = pathinfo($_FILES['license_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'license_' . time() . '_' . uniqid() . '.' . $ext;
            $licenseFilePath = 'uploads/licenses/' . $fileName;
            if (!move_uploaded_file($_FILES['license_file']['tmp_name'], __DIR__ . '/../' . $licenseFilePath)) {
                $errors[] = 'Failed to save license file.';
            }
        }
    }
    
    if (!$errors) {
        $db->begin_transaction();
        try {
            // Create user account
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, "pharmacy_owner", NOW())');
            $stmt->bind_param('sss', $name, $email, $hash);
            $stmt->execute();
            $userId = $stmt->insert_id;
            $stmt->close();
            
            // Create registration request
            $stmt = $db->prepare('INSERT INTO pharmacy_registrations (user_id, pharmacy_name, business_name, license_number, license_type, license_file_path, address, latitude, longitude, phone, email, owner_name, owner_contact, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")');
            $stmt->bind_param('issssssddsdss', $userId, $pharmacyName, $businessName, $licenseNumber, $licenseType, $licenseFilePath, $address, $latitude, $longitude, $phone, $email, $name, $ownerContact);
            $stmt->execute();
            $stmt->close();
            
            $db->commit();
            $success = true;
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register Pharmacy — MediFinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="/CEMO_System/system/assets/css/style.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        
        .registration-card {
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.6s ease;
            position: relative;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .card-header-custom::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .logo-badge {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .logo-badge img {
            width: 48px;
            height: 48px;
        }
        
        .card-header-custom h3 {
            margin: 0;
            font-weight: 700;
            font-size: 2rem;
            position: relative;
        }
        
        .card-header-custom p {
            margin: 10px 0 0;
            opacity: 0.95;
            position: relative;
        }
        
        .card-body-custom {
            padding: 40px;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 0 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .section-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .section-header h5 {
            margin: 0;
            font-weight: 700;
            color: #1e293b;
        }
        
        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-label .text-danger {
            color: #ef4444 !important;
        }
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 10;
        }
        
        .form-control.with-icon {
            padding-left: 45px;
        }
        
        #locationMap {
            height: 450px;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .map-instructions {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .map-instructions i {
            color: #0ea5e9;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 13px;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            color: #475569;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }
        
        .alert-success h5 {
            font-weight: 700;
            margin-bottom: 15px;
        }
        
        .file-upload-wrapper {
            position: relative;
        }
        
        .file-upload-wrapper input[type="file"] {
            cursor: pointer;
        }
        
        .file-upload-wrapper input[type="file"]::-webkit-file-upload-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e2e8f0;
            z-index: 0;
        }
        
        .progress-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        
        .step-circle.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
        }
        
        .step-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            padding: 10px 20px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
            color: white;
        }
        
        .back-link i {
            margin-right: 8px;
        }
        
        small.text-muted {
            color: #94a3b8 !important;
            font-size: 0.85rem;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <a href="/CEMO_System/system/" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="registration-card">
                    <div class="card-header-custom">
                        <div class="logo-badge">
                            <img src="/CEMO_System/system/assets/img/medifinder-logo.svg" alt="MediFinder">
                        </div>
                        <h3>Register Your Pharmacy</h3>
                        <p>Join our network and help patients find their medicines faster</p>
                    </div>
                    
                    <div class="card-body-custom">
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <h5>Registration Submitted Successfully!</h5>
                                <p>Your pharmacy registration has been submitted for review. Our team will verify your information and you'll receive an email notification once approved.</p>
                                <p class="mb-0"><strong>What's next?</strong> You can login to your account once approved.</p>
                                <a href="/CEMO_System/system/auth/login.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="progress-steps">
                                <div class="progress-step">
                                    <div class="step-circle active">1</div>
                                    <div class="step-label">Account</div>
                                </div>
                                <div class="progress-step">
                                    <div class="step-circle active">2</div>
                                    <div class="step-label">Pharmacy Info</div>
                                </div>
                                <div class="progress-step">
                                    <div class="step-circle active">3</div>
                                    <div class="step-label">Location</div>
                                </div>
                            </div>
                            
                            <?php if ($errors): ?>
                                <div class="alert alert-danger">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                        <div>
                                            <strong>Please correct the following errors:</strong>
                                            <ul class="mb-0 mt-2">
                                                <?php foreach ($errors as $e): ?>
                                                    <li><?php echo htmlspecialchars($e); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" enctype="multipart/form-data" id="registrationForm">
                                <!-- Account Information Section -->
                                <div class="section-header">
                                    <div class="section-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h5>Account Information</h5>
                                </div>
                                
                                <div class="row g-3 mb-5">
                                    <div class="col-md-6">
                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" placeholder="John Doe" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email" placeholder="pharmacy@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="password" placeholder="Minimum 6 characters" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" name="confirm" placeholder="Re-enter password" required>
                                    </div>
                                </div>
                                
                                <!-- Pharmacy Information Section -->
                                <div class="section-header">
                                    <div class="section-icon">
                                        <i class="fas fa-store"></i>
                                    </div>
                                    <h5>Pharmacy Information</h5>
                                </div>
                                
                                <div class="row g-3 mb-5">
                                    <div class="col-md-6">
                                        <label class="form-label">Pharmacy Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="pharmacy_name" placeholder="e.g., Mercury Drug" required value="<?php echo htmlspecialchars($_POST['pharmacy_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Business Name</label>
                                        <input type="text" class="form-control" name="business_name" placeholder="Official business name" value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">License Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="license_number" placeholder="ABC-123456" required value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>">
                                        <small class="text-muted"><i class="fas fa-info-circle"></i> Enter your pharmacy license or permit number</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">License Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="license_type" required>
                                            <option value="">Select license type...</option>
                                            <option value="pharmacy" <?php echo (($_POST['license_type'] ?? '') === 'pharmacy') ? 'selected' : ''; ?>>Pharmacy</option>
                                            <option value="drugstore" <?php echo (($_POST['license_type'] ?? '') === 'drugstore') ? 'selected' : ''; ?>>Drugstore</option>
                                            <option value="clinic" <?php echo (($_POST['license_type'] ?? '') === 'clinic') ? 'selected' : ''; ?>>Clinic</option>
                                            <option value="healthcare_network" <?php echo (($_POST['license_type'] ?? '') === 'healthcare_network') ? 'selected' : ''; ?>>Healthcare Network</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">License Document <span class="text-danger">*</span></label>
                                        <div class="file-upload-wrapper">
                                            <input type="file" class="form-control" name="license_file" id="license_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                        </div>
                                        <small class="text-muted"><i class="fas fa-file-pdf"></i> PDF, JPG or PNG format. Maximum size: 5 MB</small>
                                        
                                        <!-- License Image Preview and OCR -->
                                        <div id="licensePreview" class="mt-3" style="display: none;">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <i class="fas fa-image me-2"></i>License Preview
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <img id="licenseImagePreview" src="" alt="License Preview" class="img-fluid rounded mb-3" style="max-height: 400px; border: 2px solid #e2e8f0;">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-grid gap-2 mb-3">
                                                                <button type="button" class="btn btn-primary" id="btnExtractOCR">
                                                                    <i class="fas fa-magic me-2"></i>Extract Text with OCR
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" id="btnClearOCR" style="display: none;">
                                                                    <i class="fas fa-times me-2"></i>Clear Extraction
                                                                </button>
                                                            </div>
                                                            <div id="ocrStatus" class="alert alert-info" style="display: none;">
                                                                <i class="fas fa-spinner fa-spin me-2"></i><span id="ocrStatusText">Processing...</span>
                                                            </div>
                                                            <div id="extractedText" class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto; display: none;">
                                                                <strong>Extracted Text:</strong>
                                                                <pre id="ocrText" class="mt-2 mb-0 small"></pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Pharmacy Phone <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="phone" placeholder="+63 912 345 6789" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Owner Contact <span class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" name="owner_contact" placeholder="+63 912 345 6789" required value="<?php echo htmlspecialchars($_POST['owner_contact'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <!-- Location Section -->
                                <div class="section-header">
                                    <div class="section-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <h5>Location Details</h5>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Complete Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="address" rows="3" placeholder="Street, Barangay, City, Province" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">Pin Your Exact Location <span class="text-danger">*</span></label>
                                    <div class="map-instructions">
                                        <i class="fas fa-lightbulb me-2"></i>
                                        <strong>Tip:</strong> Click on the map to place a marker at your pharmacy's exact location. This helps patients find you easily!
                                    </div>
                                    <div id="locationMap"></div>
                                    <input type="hidden" name="latitude" id="latitude" required>
                                    <input type="hidden" name="longitude" id="longitude" required>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-info-circle"></i> Current coordinates: 
                                        <span id="coordDisplay" class="fw-semibold">Click on map to set location</span>
                                    </small>
                                </div>
                                
                                <div class="d-grid gap-3 mt-5">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Registration
                                    </button>
                                    <a href="/CEMO_System/system/auth/login.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sign-in-alt me-2"></i>Already have an account? Login
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize map
    const CITY_CENTER = { lat: 10.5335, lon: 122.8338 };
    const CITY_BOUNDS = L.latLngBounds([ [10.40, 122.70], [10.65, 122.95] ]);
    
    const map = L.map('locationMap').setView([CITY_CENTER.lat, CITY_CENTER.lon], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    map.setMaxBounds(CITY_BOUNDS);
    map.options.minZoom = 12;
    map.options.maxBoundsViscosity = 1.0;
    
    let marker = null;
    
    // Set initial location if provided
    const initLat = <?php echo json_encode($_POST['latitude'] ?? null); ?>;
    const initLon = <?php echo json_encode($_POST['longitude'] ?? null); ?>;
    if (initLat && initLon) {
        marker = L.marker([initLat, initLon], {
            icon: L.icon({
                iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41]
            })
        }).addTo(map);
        map.setView([initLat, initLon], 16);
        document.getElementById('latitude').value = initLat;
        document.getElementById('longitude').value = initLon;
        document.getElementById('coordDisplay').textContent = `${initLat.toFixed(6)}, ${initLon.toFixed(6)}`;
    }
    
    // Custom marker icon
    const customIcon = L.icon({
        iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
        shadowSize: [41, 41]
    });
    
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lon = e.latlng.lng;
        
        if (marker) {
            map.removeLayer(marker);
        }
        
        marker = L.marker([lat, lon], { icon: customIcon }).addTo(map);
        marker.bindPopup('<strong>Your Pharmacy Location</strong>').openPopup();
        
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lon;
        document.getElementById('coordDisplay').textContent = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
    });
    
    // Use geolocation
    navigator.geolocation.getCurrentPosition((pos) => {
        const { latitude, longitude } = pos.coords;
        const userLatLng = L.latLng(latitude, longitude);
        if (CITY_BOUNDS.contains(userLatLng)) {
            map.setView([latitude, longitude], 15);
        }
    }, () => {}, { enableHighAccuracy: true });
    
    // Form validation enhancements
    const form = document.getElementById('registrationForm');
    const inputs = form.querySelectorAll('.form-control, .form-select');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
    
    // Password matching validation
    const password = document.querySelector('input[name="password"]');
    const confirm = document.querySelector('input[name="confirm"]');
    
    confirm.addEventListener('input', function() {
        if (password.value !== this.value) {
            this.setCustomValidity('Passwords do not match');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
        }
    });
    
    // File upload feedback and preview
    const fileInput = document.getElementById('license_file');
    const licensePreview = document.getElementById('licensePreview');
    const licenseImagePreview = document.getElementById('licenseImagePreview');
    const btnExtractOCR = document.getElementById('btnExtractOCR');
    const btnClearOCR = document.getElementById('btnClearOCR');
    const ocrStatus = document.getElementById('ocrStatus');
    const ocrStatusText = document.getElementById('ocrStatusText');
    const extractedText = document.getElementById('extractedText');
    const ocrText = document.getElementById('ocrText');
    
    let currentImageData = null;
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            const fileSize = file.size / 1024 / 1024; // in MB
            
            if (fileSize > 5) {
                alert('File size exceeds 5 MB. Please choose a smaller file.');
                this.value = '';
                licensePreview.style.display = 'none';
                return;
            }
            
            // Only show preview for images (not PDFs)
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    licenseImagePreview.src = e.target.result;
                    licensePreview.style.display = 'block';
                    currentImageData = e.target.result;
                    extractedText.style.display = 'none';
                    btnClearOCR.style.display = 'none';
                    ocrStatus.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                // For PDFs, hide preview
                licensePreview.style.display = 'none';
            }
            
                this.classList.add('is-valid');
        } else {
            licensePreview.style.display = 'none';
        }
    });
    
    // OCR Extraction
    btnExtractOCR.addEventListener('click', async function() {
        if (!currentImageData) {
            alert('Please upload an image first.');
            return;
        }
        
        btnExtractOCR.disabled = true;
        btnExtractOCR.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Extracting...';
        ocrStatus.style.display = 'block';
        ocrStatusText.textContent = 'Initializing OCR engine...';
        extractedText.style.display = 'none';
        
        try {
            ocrStatusText.textContent = 'Processing image...';
            
            const { data: { text } } = await Tesseract.recognize(
                currentImageData,
                'eng',
                {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            ocrStatusText.textContent = `Recognizing text... ${Math.round(m.progress * 100)}%`;
                        }
                    }
                }
            );
            
            ocrText.textContent = text;
            extractedText.style.display = 'block';
            ocrStatus.style.display = 'none';
            btnExtractOCR.disabled = false;
            btnExtractOCR.innerHTML = '<i class="fas fa-magic me-2"></i>Extract Text with OCR';
            btnClearOCR.style.display = 'block';
            
            // Auto-fill form fields
            autoFillForm(text);
            
        } catch (error) {
            console.error('OCR Error:', error);
            ocrStatus.className = 'alert alert-danger';
            ocrStatusText.textContent = 'OCR extraction failed. Please try again or enter information manually.';
            btnExtractOCR.disabled = false;
            btnExtractOCR.innerHTML = '<i class="fas fa-magic me-2"></i>Extract Text with OCR';
        }
    });
    
    // Clear OCR results
    btnClearOCR.addEventListener('click', function() {
        extractedText.style.display = 'none';
        btnClearOCR.style.display = 'none';
        ocrText.textContent = '';
    });
    
    // Auto-fill form fields from extracted text
    function autoFillForm(text) {
        const upperText = text.toUpperCase();
        const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        
        // Extract License Number (common patterns: ABC-123456, LTO-123456, etc.)
        const licensePatterns = [
            /(?:LICENSE|PERMIT|LIC\.?|REG\.?)[\s#:]*([A-Z]{2,4}[-]?\d{4,8})/i,
            /([A-Z]{2,4}[-]?\d{4,8})/,
            /(?:NO\.?|NUMBER|#)[\s:]*([A-Z]{0,4}[-]?\d{4,8})/i
        ];
        
        for (const pattern of licensePatterns) {
            const match = text.match(pattern);
            if (match && match[1]) {
                const licenseField = document.querySelector('input[name="license_number"]');
                if (licenseField && !licenseField.value) {
                    licenseField.value = match[1].trim();
                    licenseField.classList.add('is-valid');
                }
                break;
            }
        }
        
        // Extract Pharmacy Name (look for "PHARMACY", "DRUGSTORE", "CLINIC" keywords)
        const pharmacyKeywords = ['PHARMACY', 'DRUGSTORE', 'DRUG STORE', 'CLINIC', 'MEDICAL', 'HEALTH'];
        for (const line of lines) {
            const upperLine = line.toUpperCase();
            for (const keyword of pharmacyKeywords) {
                if (upperLine.includes(keyword) && line.length > 5 && line.length < 100) {
                    const pharmacyField = document.querySelector('input[name="pharmacy_name"]');
                    if (pharmacyField && !pharmacyField.value) {
                        pharmacyField.value = line.trim();
                        pharmacyField.classList.add('is-valid');
                    }
                    break;
                }
            }
        }
        
        // Extract License Type
        if (upperText.includes('PHARMACY')) {
            const licenseTypeField = document.querySelector('select[name="license_type"]');
            if (licenseTypeField && !licenseTypeField.value) {
                licenseTypeField.value = 'pharmacy';
            }
        } else if (upperText.includes('DRUGSTORE') || upperText.includes('DRUG STORE')) {
            const licenseTypeField = document.querySelector('select[name="license_type"]');
            if (licenseTypeField && !licenseTypeField.value) {
                licenseTypeField.value = 'drugstore';
            }
        } else if (upperText.includes('CLINIC')) {
            const licenseTypeField = document.querySelector('select[name="license_type"]');
            if (licenseTypeField && !licenseTypeField.value) {
                licenseTypeField.value = 'clinic';
            }
        }
        
        // Extract Phone Numbers (Philippines format: +63, 09, etc.)
        const phonePatterns = [
            /(?:\+63|0)?[\s-]?(\d{3}[\s-]?\d{3}[\s-]?\d{4})/g,
            /(?:TEL|PHONE|MOBILE|CELL)[\s:]*([\d\s\-+()]+)/i
        ];
        
        for (const pattern of phonePatterns) {
            const matches = text.match(pattern);
            if (matches) {
                const phoneField = document.querySelector('input[name="phone"]');
                if (phoneField && !phoneField.value && matches.length > 0) {
                    let phone = matches[0].replace(/[^\d+]/g, '');
                    if (phone.startsWith('63')) phone = '+' + phone;
                    else if (phone.startsWith('0')) phone = '+63' + phone.substring(1);
                    else if (!phone.startsWith('+')) phone = '+63' + phone;
                    phoneField.value = phone;
                    phoneField.classList.add('is-valid');
                }
                break;
            }
        }
        
        // Extract Address (look for common address keywords)
        const addressKeywords = ['STREET', 'AVE', 'AVENUE', 'ROAD', 'RD', 'BRGY', 'BARANGAY', 'CITY', 'PROVINCE'];
        const addressLines = [];
        for (const line of lines) {
            const upperLine = line.toUpperCase();
            if (addressKeywords.some(keyword => upperLine.includes(keyword)) || 
                /^\d+/.test(line) || 
                (line.length > 10 && line.length < 100)) {
                addressLines.push(line);
            }
        }
        
        if (addressLines.length > 0) {
            const addressField = document.querySelector('textarea[name="address"]');
            if (addressField && !addressField.value) {
                addressField.value = addressLines.join(', ');
                addressField.classList.add('is-valid');
            }
        }
        
        // Extract Owner Name (look for "OWNER", "PROPRIETOR", "NAME" keywords)
        const ownerKeywords = ['OWNER', 'PROPRIETOR', 'REGISTERED OWNER'];
        for (const line of lines) {
            const upperLine = line.toUpperCase();
            for (const keyword of ownerKeywords) {
                if (upperLine.includes(keyword)) {
                    const nameMatch = line.match(new RegExp(keyword + '[\\s:]+(.+)', 'i'));
                    if (nameMatch && nameMatch[1]) {
                        const nameField = document.querySelector('input[name="name"]');
                        if (nameField && !nameField.value) {
                            nameField.value = nameMatch[1].trim();
                            nameField.classList.add('is-valid');
                        }
                    }
                    break;
                }
            }
        }
        
        // Show success message
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success alert-dismissible fade show mt-3';
        successAlert.innerHTML = '<i class="fas fa-check-circle me-2"></i>Form fields have been auto-filled from the license. Please review and correct if needed.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        licensePreview.querySelector('.card-body').appendChild(successAlert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            successAlert.remove();
        }, 5000);
    }
    
    // Form submission animation
    form.addEventListener('submit', function(e) {
        const lat = document.getElementById('latitude').value;
        const lon = document.getElementById('longitude').value;
        
        if (!lat || !lon) {
            e.preventDefault();
            alert('Please pin your pharmacy location on the map before submitting.');
            document.getElementById('locationMap').scrollIntoView({ behavior: 'smooth' });
            return;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        submitBtn.disabled = true;
    });
    
    // Smooth scroll to errors
    <?php if ($errors): ?>
    window.addEventListener('load', function() {
        document.querySelector('.alert-danger').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    <?php endif; ?>
    </script>
</body>
</html>