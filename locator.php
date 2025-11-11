<?php
session_start();
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$searchMode = isset($_GET['mode']) && in_array($_GET['mode'], ['medicine', 'pharmacy'], true) ? $_GET['mode'] : 'medicine';
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
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

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
                <form class="flex flex-wrap items-center gap-2" id="searchForm">
                  <select class="form-select w-auto min-w-[140px]" id="searchMode" data-default-mode="<?php echo htmlspecialchars($searchMode); ?>">
                    <option value="medicine" <?php echo $searchMode === 'medicine' ? 'selected' : ''; ?>>Medicine</option>
                    <option value="pharmacy" <?php echo $searchMode === 'pharmacy' ? 'selected' : ''; ?>>Pharmacy</option>
                  </select>
                  <input class="form-control flex-1 min-w-[200px]" type="text" id="searchQuery" placeholder="Search..." value="<?php echo htmlspecialchars($query); ?>" />
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
    const searchInput = document.getElementById('searchQuery');
    const searchMode = document.getElementById('searchMode');
    const form = document.getElementById('searchForm');
    const DEFAULT_RADIUS_KM = 2.5;

    // Bago City, Negros Occidental bounds and center
    const CITY_CENTER = { lat: 10.5335, lon: 122.8338 };
    const CITY_BOUNDS = L.latLngBounds([[10.40, 122.70], [10.65, 122.95]]);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    map.setView([CITY_CENTER.lat, CITY_CENTER.lon], 14);
    map.setMaxBounds(CITY_BOUNDS);
    map.options.minZoom = 12;
    map.options.maxBoundsViscosity = 1.0;

    let userMarker = null;

    function updatePlaceholder() {
        if (searchMode.value === 'pharmacy') {
            searchInput.placeholder = 'Pharmacy name (e.g., MediPharm)';
        } else {
            searchInput.placeholder = 'Medicine name (e.g., Paracetamol)';
        }
    }

    updatePlaceholder();

    searchMode.addEventListener('change', () => {
        updatePlaceholder();
        if (searchInput.value.trim()) {
            const center = map.getCenter();
            searchPharmaciesFromDB(center.lat, center.lng);
        }
    });

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
        const center = map.getCenter();
        searchPharmaciesFromDB(center.lat, center.lng);
    });

    async function searchPharmaciesFromDB(lat, lon) {
        const mode = searchMode.value === 'pharmacy' ? 'pharmacy' : 'medicine';
        const query = (searchInput.value || '').trim();
        const params = new URLSearchParams({
            lat: Number(lat).toFixed(6),
            lon: Number(lon).toFixed(6),
            radius: DEFAULT_RADIUS_KM
        });

        if (mode === 'medicine' && query) {
            params.append('medicine', query);
        } else if (mode === 'pharmacy' && query) {
            params.append('pharmacy', query);
        }

        list.textContent = mode === 'medicine'
            ? 'Searching medicine availability...'
            : 'Searching pharmacies...';

        try {
            const response = await fetch(`/medi/api/get_pharmacies.php?${params.toString()}`, { cache: 'no-cache' });
            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }
            const data = await response.json();
            syncSearchControls(data.search || {});
            renderResults(data.pharmacies || [], data.search || {});
        } catch (error) {
            console.error(error);
            list.innerHTML = '<div class="text-danger">Error loading pharmacies. Please try again.</div>';
        }
    }

    function syncSearchControls(meta) {
        if (!meta || typeof meta !== 'object') {
            updateQueryString({ type: 'nearby' });
            return;
        }

        const type = meta.type === 'pharmacy' ? 'pharmacy' : (meta.type === 'medicine' ? 'medicine' : 'nearby');
        if (type === 'pharmacy' || type === 'medicine') {
            if (searchMode.value !== type) {
                searchMode.value = type;
            }
            const value = type === 'medicine' ? (meta.medicine || '') : (meta.pharmacy || '');
            if (typeof value === 'string') {
                searchInput.value = value;
            }
        }
        updatePlaceholder();
        updateQueryString(meta);
    }

    function updateQueryString(meta) {
        if (!window.history || !window.history.replaceState) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        if (meta.type === 'medicine' && meta.medicine) {
            params.set('query', meta.medicine);
            params.set('mode', 'medicine');
        } else if (meta.type === 'pharmacy' && meta.pharmacy) {
            params.set('query', meta.pharmacy);
            params.set('mode', 'pharmacy');
        } else {
            params.delete('query');
            params.delete('mode');
        }

        const newQuery = params.toString();
        const newUrl = newQuery ? `${window.location.pathname}?${newQuery}` : window.location.pathname;
        window.history.replaceState({}, '', newUrl);
    }

    function renderResults(pharmacies, meta = {}) {
        const searchType = meta.type || 'nearby';
        const medicineTerm = (meta.medicine || '').trim();
        const pharmacyTerm = (meta.pharmacy || '').trim();
        const queryText = searchType === 'medicine' ? medicineTerm : (searchType === 'pharmacy' ? pharmacyTerm : '');

        map.eachLayer(layer => {
            if (layer instanceof L.Marker && layer !== userMarker) {
                map.removeLayer(layer);
            }
        });

        if (!Array.isArray(pharmacies) || pharmacies.length === 0) {
            const emptyMessage = queryText
                ? (searchType === 'medicine'
                    ? 'No pharmacies found with that medicine in Bago City.'
                    : 'No pharmacies matched that name in Bago City.')
                : 'No pharmacies found in Bago City.';
            list.innerHTML = `<div class="text-muted">${escapeHtml(emptyMessage)}</div>`;
            return;
        }

        let html = '';
        if (queryText) {
            const label = searchType === 'medicine' ? 'medicine' : 'pharmacy';
            html += `<div class="text-muted small mb-2">Showing ${label} results for "${escapeHtml(queryText)}".</div>`;
        }

        pharmacies.forEach(pharmacy => {
            const badgeClass = pharmacy.availability_level ? `bg-${pharmacy.availability_level}` : 'bg-secondary';
            const badgeText = pharmacy.availability_label || 'Availability';
            const availabilityMessage = pharmacy.availability_message ? escapeHtml(pharmacy.availability_message) : '';
            const medicines = Array.isArray(pharmacy.medicines) ? pharmacy.medicines : [];
            const medsToShow = medicines.slice(0, 5);

            const medicineEntries = medsToShow.map(med => {
                const safeName = escapeHtml(med.name || 'Unnamed medicine');
                const stockCount = typeof med.stock === 'number' ? med.stock : Number(med.stock || 0);
                const priceValue = typeof med.price === 'number'
                    ? med.price
                    : (med.price !== null && med.price !== undefined ? Number(med.price) : null);
                const priceText = Number.isFinite(priceValue) ? ` - ₱${Number(priceValue).toFixed(2)}` : '';
                if (med.available) {
                    return {
                        list: `<div class="small mt-1"><i class="feather icon-check-circle text-success mr-1"></i>${safeName}: ${stockCount} unit(s)${priceText}</div>`,
                        popup: `<div>${safeName}: ${stockCount} unit(s)${priceText}</div>`
                    };
                }
                return {
                    list: `<div class="small mt-1 text-muted"><i class="feather icon-x-circle mr-1"></i>${safeName}: Out of stock</div>`,
                    popup: `<div class="text-muted">${safeName}: Out of stock</div>`
                };
            });

            let medicineInfo = medicineEntries.map(entry => entry.list).join('');
            let popupMedicine = medicineEntries.map(entry => entry.popup).join('');

            if (medicines.length > medsToShow.length) {
                const moreCount = medicines.length - medsToShow.length;
                const moreText = `${moreCount} more item(s) in inventory.`;
                medicineInfo += `<div class="small mt-1 text-muted">${escapeHtml(moreText)}</div>`;
                popupMedicine += `<div class="text-muted">${escapeHtml(moreText)}</div>`;
            }

            if (!medicineInfo && searchType === 'medicine') {
                medicineInfo = '<div class="small mt-1 text-muted">No stock details returned for this medicine.</div>';
            }

            const phoneHtml = pharmacy.phone
                ? `<div class="small text-muted mt-1"><i class="feather icon-phone mr-1"></i>${escapeHtml(pharmacy.phone)}</div>`
                : '';

            const distanceText = typeof pharmacy.distance_km === 'number'
                ? `${pharmacy.distance_km} km away`
                : 'Distance unavailable';

            html += `
                <div class="border-bottom py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="font-semibold">${escapeHtml(pharmacy.name || 'Unnamed Pharmacy')}</div>
                            <div class="text-muted small">${escapeHtml(pharmacy.address || 'Bago City')}</div>
                            <div class="text-muted small"><i class="feather icon-navigation mr-1"></i>${escapeHtml(distanceText)}</div>
                            ${availabilityMessage ? `<div class="small mt-1">${availabilityMessage}</div>` : ''}
                            ${medicineInfo}
                        </div>
                        <span class="badge ${badgeClass} ms-2">${escapeHtml(badgeText)}</span>
                    </div>
                    ${phoneHtml}
                </div>
            `;

            if (typeof pharmacy.latitude === 'number' && typeof pharmacy.longitude === 'number') {
                const marker = L.marker([pharmacy.latitude, pharmacy.longitude]).addTo(map);
                let popupContent = `<strong>${escapeHtml(pharmacy.name || 'Pharmacy')}</strong><br/>`;
                popupContent += `<span class="text-muted small">${escapeHtml(distanceText)}</span><br/>`;
                popupContent += `<span class="badge ${badgeClass}">${escapeHtml(badgeText)}</span>`;
                if (availabilityMessage) {
                    popupContent += `<br/><span class="text-muted small">${availabilityMessage}</span>`;
                }
                if (popupMedicine) {
                    popupContent += `<hr/>${popupMedicine}`;
                }
                marker.bindPopup(popupContent);
            }
        });

        list.innerHTML = html;
    }

    function escapeHtml(value) {
        return (value || '').toString().replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            '\'': '&#39;'
        }[char]));
    }
    </script>
  </body>
  <!-- [Body] end -->
</html>

