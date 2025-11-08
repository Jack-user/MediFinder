<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 10.5335;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : 122.8338;
$radius = isset($_GET['radius']) ? (float)$_GET['radius'] : 2.5; // km
$medicine = isset($_GET['medicine']) ? trim($_GET['medicine']) : '';

// Bago City bounds check
$minLat = 10.40;
$maxLat = 10.65;
$minLon = 122.70;
$maxLon = 122.95;

if ($lat < $minLat || $lat > $maxLat || $lon < $minLon || $lon > $maxLon) {
    $lat = 10.5335;
    $lon = 122.8338;
}

// Calculate bounding box for nearby search
$latRange = $radius / 111.0; // roughly 111 km per degree
$lonRange = $radius / (111.0 * cos(deg2rad($lat)));

if ($medicine) {
    // Search with medicine filter
    $sql = "SELECT 
        p.id,
        p.name,
        p.address,
        p.latitude,
        p.longitude,
        p.phone,
        m.id AS medicine_id,
        m.name AS medicine_name,
        COALESCE(inv.quantity, 0) AS stock_quantity,
        inv.price AS medicine_price,
        (6371 * acos(
            cos(radians(?)) * cos(radians(p.latitude)) * 
            cos(radians(p.longitude) - radians(?)) + 
            sin(radians(?)) * sin(radians(p.latitude))
        )) AS distance_km
    FROM pharmacies p
    LEFT JOIN medicines m ON (LOWER(m.name) LIKE ? OR LOWER(m.generic_name) LIKE ?)
    LEFT JOIN pharmacy_inventory inv ON inv.pharmacy_id = p.id AND inv.medicine_id = m.id
    WHERE p.is_active = 1
        AND p.latitude BETWEEN ? AND ?
        AND p.longitude BETWEEN ? AND ?
        AND (LOWER(m.name) LIKE ? OR LOWER(m.generic_name) LIKE ?)
    HAVING distance_km <= ?
    ORDER BY distance_km ASC
    LIMIT 20";
    
    $searchTerm = '%' . strtolower($medicine) . '%';
    $minLatSearch = $lat - $latRange;
    $maxLatSearch = $lat + $latRange;
    $minLonSearch = $lon - $lonRange;
    $maxLonSearch = $lon + $lonRange;
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('dddsddssssd', $lat, $lon, $lat, $searchTerm, $searchTerm, 
        $minLatSearch, $maxLatSearch, $minLonSearch, $maxLonSearch, 
        $searchTerm, $searchTerm, $radius);
} else {
    // Search all pharmacies
    $sql = "SELECT 
        p.id,
        p.name,
        p.address,
        p.latitude,
        p.longitude,
        p.phone,
        (6371 * acos(
            cos(radians(?)) * cos(radians(p.latitude)) * 
            cos(radians(p.longitude) - radians(?)) + 
            sin(radians(?)) * sin(radians(p.latitude))
        )) AS distance_km
    FROM pharmacies p
    WHERE p.is_active = 1
        AND p.latitude BETWEEN ? AND ?
        AND p.longitude BETWEEN ? AND ?
    HAVING distance_km <= ?
    ORDER BY distance_km ASC
    LIMIT 20";
    
    $minLatSearch = $lat - $latRange;
    $maxLatSearch = $lat + $latRange;
    $minLonSearch = $lon - $lonRange;
    $maxLonSearch = $lon + $lonRange;
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('dddddddd', $lat, $lon, $lat, 
        $minLatSearch, $maxLatSearch, $minLonSearch, $maxLonSearch, $radius);
}

$stmt->execute();
$result = $stmt->get_result();

$pharmacies = [];
$currentPharmacyId = null;
$currentPharmacy = null;

while ($row = $result->fetch_assoc()) {
    if ($currentPharmacyId !== $row['id']) {
        // Save previous pharmacy if exists
        if ($currentPharmacy) {
            $pharmacies[] = $currentPharmacy;
        }
        
        // Start new pharmacy
        $currentPharmacy = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'phone' => $row['phone'],
            'distance_km' => round((float)$row['distance_km'], 2),
            'medicines' => []
        ];
        $currentPharmacyId = $row['id'];
    }
    
    // Add medicine info if searching for specific medicine
    if ($medicine && isset($row['medicine_id']) && $row['medicine_id']) {
        $currentPharmacy['medicines'][] = [
            'id' => (int)$row['medicine_id'],
            'name' => $row['medicine_name'],
            'stock' => (int)$row['stock_quantity'],
            'price' => $row['medicine_price'] ? (float)$row['medicine_price'] : null,
            'available' => (int)$row['stock_quantity'] > 0
        ];
    }
}

// Don't forget the last pharmacy
if ($currentPharmacy) {
    $pharmacies[] = $currentPharmacy;
}

$stmt->close();

// Calculate availability percentage if medicine is specified
if ($medicine) {
    foreach ($pharmacies as &$pharmacy) {
        $hasStock = false;
        $totalStock = 0;
        foreach ($pharmacy['medicines'] as $med) {
            if ($med['available']) {
                $hasStock = true;
                $totalStock += $med['stock'];
            }
        }
        // Calculate availability based on stock
        if ($hasStock) {
            if ($totalStock > 50) {
                $pharmacy['availability_pct'] = 90;
            } elseif ($totalStock > 20) {
                $pharmacy['availability_pct'] = 70;
            } elseif ($totalStock > 10) {
                $pharmacy['availability_pct'] = 60;
            } else {
                $pharmacy['availability_pct'] = 50;
            }
        } else {
            $pharmacy['availability_pct'] = 0;
        }
    }
    unset($pharmacy);
} else {
    // If no medicine specified, show 50% as default (unknown)
    foreach ($pharmacies as &$pharmacy) {
        $pharmacy['availability_pct'] = 50;
    }
    unset($pharmacy);
}

echo json_encode(['pharmacies' => $pharmacies]);

