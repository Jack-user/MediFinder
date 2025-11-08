<?php
session_start();
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Pharmacy Locator | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Find nearby pharmacies" />
    <meta name="keywords" content="pharmacy locator, find pharmacies, medicine availability" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/CEMO_System/system/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include 'includes/head-css.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
      .map-container {
        height: 600px;
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
        $pageTitle = 'Pharmacy Locator';
        $breadcrumbItems = ['Pharmacies'];
        $activeItem = 'Locator';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6 mb-4">
          <div class="col-span-12">
            <div class="card">
              <div class="card-body">
                <form class="flex items-center gap-2" id="searchForm">
                  <input class="form-control flex-1" type="text" id="medicineQuery" placeholder="Medicine (optional)" value="<?php echo htmlspecialchars($query); ?>" />
                  <button class="btn btn-primary" type="submit">
                    <i class="feather icon-search mr-2"></i>Search
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 xl:col-span-8">
            <div class="card">
              <div class="card-header">
                <h5>Map</h5>
              </div>
              <div class="card-body p-0">
                <div id="map" class="map-container"></div>
              </div>
            </div>
          </div>
          <div class="col-span-12 xl:col-span-4">
            <div class="card">
              <div class="card-header">
                <h5>Nearby Pharmacies</h5>
              </div>
              <div class="card-body">
                <div id="list" class="text-muted small">Allow location to list nearby pharmacies.</div>
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
    const map = L.map('map');
    const list = document.getElementById('list');
    const medicineQuery = document.getElementById('medicineQuery');
    const form = document.getElementById('searchForm');

    // Bago City, Negros Occidental bounds and center
    const CITY_CENTER = { lat: 10.5335, lon: 122.8338 };
    const CITY_BOUNDS = L.latLngBounds([ [10.40, 122.70], [10.65, 122.95] ]);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.setView([CITY_CENTER.lat, CITY_CENTER.lon], 14);
    map.setMaxBounds(CITY_BOUNDS);
    map.options.minZoom = 12;
    map.options.maxBoundsViscosity = 1.0;

    let userMarker = null;

    // Try to use user's location; fall back to city center
    navigator.geolocation.getCurrentPosition((pos) => {
        const { latitude, longitude } = pos.coords;
        const userLatLng = L.latLng(latitude, longitude);
        const clamped = CITY_BOUNDS.contains(userLatLng) ? userLatLng : L.latLng(CITY_CENTER.lat, CITY_CENTER.lon);
        map.setView(clamped, 14);
        if (userMarker) map.removeLayer(userMarker);
        userMarker = L.marker([clamped.lat, clamped.lng], { 
            icon: L.icon({ 
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png', 
                iconSize: [25, 41], 
                iconAnchor: [12, 41] 
            }) 
        }).addTo(map).bindPopup('You are here');
        searchPharmaciesFromDB(clamped.lat, clamped.lng);
    }, () => {
        map.setView([CITY_CENTER.lat, CITY_CENTER.lon], 14);
        searchPharmaciesFromDB(CITY_CENTER.lat, CITY_CENTER.lon);
    }, { enableHighAccuracy: true, timeout: 8000 });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const c = map.getCenter();
        searchPharmaciesFromDB(c.lat, c.lng);
    });

    async function searchPharmaciesFromDB(lat, lon) {
        list.innerHTML = 'Searching pharmacies in database...';
        
        // Clear existing pharmacy markers
        map.eachLayer(l => { 
            if (l instanceof L.Marker && l !== userMarker) { 
                map.removeLayer(l); 
            } 
        });
        
        const medicine = (medicineQuery.value || '').trim();
        const url = `/CEMO_System/system/api/get_pharmacies.php?lat=${lat}&lon=${lon}&radius=2.5${medicine ? '&medicine=' + encodeURIComponent(medicine) : ''}`;
        
        try {
            const res = await fetch(url);
            const json = await res.json();
            renderResults(json.pharmacies || []);
        } catch (error) {
            list.innerHTML = '<div class="text-danger">Error loading pharmacies. Please try again.</div>';
            console.error(error);
        }
    }

    function renderResults(pharmacies) {
        if (pharmacies.length === 0) {
            list.innerHTML = '<div class="text-muted">No pharmacies found in Bago City.</div>';
            return;
        }
        
        let html = '';
        
        pharmacies.forEach(pharmacy => {
            const pct = pharmacy.availability_pct || 0;
            const badge = pct >= 75 ? 'success' : pct >= 50 ? 'warning' : 'secondary';
            const badgeText = pct >= 75 ? 'In Stock' : pct >= 50 ? 'Low Stock' : 'Out of Stock';
            
            let medicineInfo = '';
            if (pharmacy.medicines && pharmacy.medicines.length > 0) {
                pharmacy.medicines.forEach(med => {
                    if (med.available) {
                        medicineInfo += `<div class="small mt-1"><i class="feather icon-check-circle text-success mr-1"></i>${escapeHtml(med.name)}: ${med.stock} units${med.price ? ' - ₱' + med.price.toFixed(2) : ''}</div>`;
                    } else {
                        medicineInfo += `<div class="small mt-1 text-muted"><i class="feather icon-x-circle mr-1"></i>${escapeHtml(med.name)}: Out of stock</div>`;
                    }
                });
            }
            
            html += `
                <div class="border-bottom py-3">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex-1">
                            <div class="font-semibold">${escapeHtml(pharmacy.name)}</div>
                            <div class="text-muted small">${escapeHtml(pharmacy.address || 'Bago City')}</div>
                            <div class="text-muted small">
                                <i class="feather icon-navigation mr-1"></i>${pharmacy.distance_km} km away
                            </div>
                            ${medicineInfo}
                        </div>
                        <span class="badge bg-${badge} ms-2">${badgeText}</span>
                    </div>
                    ${pharmacy.phone ? `<div class="small text-muted mt-1"><i class="feather icon-phone mr-1"></i>${escapeHtml(pharmacy.phone)}</div>` : ''}
                </div>`;
            
            // Add marker to map
            const marker = L.marker([pharmacy.latitude, pharmacy.longitude]).addTo(map);
            let popupContent = `<strong>${escapeHtml(pharmacy.name)}</strong><br/>`;
            popupContent += `<span class="text-muted">${pharmacy.distance_km} km away</span><br/>`;
            popupContent += `<span class="badge bg-${badge}">${badgeText}</span>`;
            if (medicineInfo) {
                popupContent += '<hr/>' + medicineInfo.replace(/<div/g, '<div style="margin-top: 4px;"');
            }
            marker.bindPopup(popupContent);
        });
        
        list.innerHTML = html;
    }

    function escapeHtml(s) { 
        return (s||'').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); 
    }
    </script>
  </body>
  <!-- [Body] end -->
</html>
