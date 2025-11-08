<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'patient') !== 'pharmacy_owner') {
    header('Location: /CEMO_System/system/auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare('SELECT * FROM pharmacies WHERE owner_user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$pharmacy = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pharmacy) {
    header('Location: /CEMO_System/system/pharmacy_dashboard.php');
    exit;
}

$success = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $address = trim($_POST['address'] ?? '');
    
    if (empty($latitude) || empty($longitude)) {
        $errors[] = 'Please pin your location on the map.';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }
    
    if (!$errors) {
        $stmt = $db->prepare('UPDATE pharmacies SET latitude = ?, longitude = ?, address = ? WHERE id = ?');
        $stmt->bind_param('ddss', $latitude, $longitude, $address, $pharmacy['id']);
        if ($stmt->execute()) {
            $success = 'Location updated successfully.';
            $pharmacy['latitude'] = $latitude;
            $pharmacy['longitude'] = $longitude;
            $pharmacy['address'] = $address;
        } else {
            $errors[] = 'Failed to update location.';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Update Location | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Update pharmacy location" />
    <meta name="keywords" content="pharmacy location, map, address" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/CEMO_System/system/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include 'includes/head-css.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
      .map-container {
        height: 500px;
        width: 100%;
        border-radius: 8px;
        overflow: hidden;
      }
    </style>
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
        $pageTitle = 'Update Location';
        $breadcrumbItems = ['Pharmacy', 'Location'];
        $activeItem = 'Update';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <?php if ($success): ?>
            <div class="col-span-12">
              <div class="alert alert-success">
                <i class="feather icon-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($errors): ?>
            <div class="col-span-12">
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          <?php endif; ?>

          <div class="col-span-12">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Update Pharmacy Location</h5>
              </div>
              <div class="card-body">
                <form method="post">
                  <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2" required><?php echo htmlspecialchars($pharmacy['address']); ?></textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Pin Your Exact Location on Map <span class="text-danger">*</span></label>
                    <div id="locationMap" class="map-container"></div>
                    <small class="text-muted">Click on the map to set your pharmacy location</small>
                    <input type="hidden" name="latitude" id="latitude" required value="<?php echo htmlspecialchars($pharmacy['latitude']); ?>">
                    <input type="hidden" name="longitude" id="longitude" required value="<?php echo htmlspecialchars($pharmacy['longitude']); ?>">
                  </div>
                  <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                      <i class="feather icon-save mr-2"></i>Save Location
                    </button>
                  </div>
                </form>
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
    // Initialize map
    const CITY_CENTER = { lat: 10.5335, lon: 122.8338 };
    const CITY_BOUNDS = L.latLngBounds([ [10.40, 122.70], [10.65, 122.95] ]);
    
    const initLat = <?php echo json_encode((float)$pharmacy['latitude']); ?>;
    const initLon = <?php echo json_encode((float)$pharmacy['longitude']); ?>;
    
    const map = L.map('locationMap').setView([initLat || CITY_CENTER.lat, initLon || CITY_CENTER.lon], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    map.setMaxBounds(CITY_BOUNDS);
    map.options.minZoom = 12;
    map.options.maxBoundsViscosity = 1.0;
    
    let marker = null;
    
    // Set initial marker
    if (initLat && initLon) {
        marker = L.marker([initLat, initLon]).addTo(map);
    }
    
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lon = e.latlng.lng;
        
        if (marker) {
            map.removeLayer(marker);
        }
        
        marker = L.marker([lat, lon]).addTo(map);
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lon;
    });
    </script>
  </body>
  <!-- [Body] end -->
</html>
