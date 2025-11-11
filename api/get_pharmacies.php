<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 10.5335;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : 122.8338;
$radius = isset($_GET['radius']) ? (float)$_GET['radius'] : 2.5; // km
$medicine = isset($_GET['medicine']) ? trim($_GET['medicine']) : '';
$pharmacyName = isset($_GET['pharmacy']) ? trim($_GET['pharmacy']) : '';

$isMedicineSearch = $medicine !== '';
$isPharmacySearch = !$isMedicineSearch && $pharmacyName !== '';

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

$minLatSearch = $lat - $latRange;
$maxLatSearch = $lat + $latRange;
$minLonSearch = $lon - $lonRange;
$maxLonSearch = $lon + $lonRange;

$pharmacyTerm = '%' . strtolower($pharmacyName ?: '') . '%';

$inventorySummaryJoin = "
    LEFT JOIN (
        SELECT 
            pharmacy_id,
            SUM(quantity) AS total_stock_units,
            SUM(CASE WHEN quantity > 0 THEN 1 ELSE 0 END) AS items_in_stock
        FROM pharmacy_inventory
        GROUP BY pharmacy_id
    ) inv_summary ON inv_summary.pharmacy_id = p.id
";

if ($isMedicineSearch) {
    $searchTerm = '%' . strtolower($medicine) . '%';

    $sql = "
        SELECT 
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
            )) AS distance_km,
            m.id AS medicine_id,
            m.name AS medicine_name,
            COALESCE(inv.quantity, 0) AS stock_quantity,
            inv.price AS medicine_price,
            COALESCE(inv_summary.items_in_stock, 0) AS items_in_stock,
            COALESCE(inv_summary.total_stock_units, 0) AS total_stock_units
        FROM pharmacies p
        INNER JOIN pharmacy_inventory inv ON inv.pharmacy_id = p.id
        INNER JOIN medicines m ON m.id = inv.medicine_id
        $inventorySummaryJoin
        WHERE p.is_active = 1
            AND p.latitude BETWEEN ? AND ?
            AND p.longitude BETWEEN ? AND ?
            AND LOWER(p.name) LIKE ?
            AND (LOWER(m.name) LIKE ? OR LOWER(m.generic_name) LIKE ?)
        HAVING distance_km <= ?
        ORDER BY distance_km ASC, stock_quantity DESC
        LIMIT 50
    ";

    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'dddddddsssd',
        $lat,
        $lon,
        $lat,
        $minLatSearch,
        $maxLatSearch,
        $minLonSearch,
        $maxLonSearch,
        $pharmacyTerm,
        $searchTerm,
        $searchTerm,
        $radius
    );
} else {
    $sql = "
        SELECT 
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
            )) AS distance_km,
            COALESCE(inv_summary.items_in_stock, 0) AS items_in_stock,
            COALESCE(inv_summary.total_stock_units, 0) AS total_stock_units
        FROM pharmacies p
        $inventorySummaryJoin
        WHERE p.is_active = 1
            AND p.latitude BETWEEN ? AND ?
            AND p.longitude BETWEEN ? AND ?
            AND LOWER(p.name) LIKE ?
        HAVING distance_km <= ?
        ORDER BY distance_km ASC
        LIMIT 50
    ";

    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'dddddddsd',
        $lat,
        $lon,
        $lat,
        $minLatSearch,
        $maxLatSearch,
        $minLonSearch,
        $maxLonSearch,
        $pharmacyTerm,
        $radius
    );
}

$stmt->execute();
$result = $stmt->get_result();

$pharmacies = [];
$currentPharmacyId = null;
$currentPharmacy = null;

while ($row = $result->fetch_assoc()) {
    if ($currentPharmacyId !== (int)$row['id']) {
        if ($currentPharmacy) {
            $pharmacies[] = $currentPharmacy;
        }

        $currentPharmacy = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'phone' => $row['phone'],
            'distance_km' => round((float)$row['distance_km'], 2),
            'medicines' => [],
            'items_in_stock' => isset($row['items_in_stock']) ? (int)$row['items_in_stock'] : 0,
            'total_stock_units' => isset($row['total_stock_units']) ? (int)$row['total_stock_units'] : 0
        ];

        $currentPharmacyId = (int)$row['id'];
    }

    if ($isMedicineSearch && isset($row['medicine_id']) && $row['medicine_id']) {
        $currentPharmacy['medicines'][] = [
            'id' => (int)$row['medicine_id'],
            'name' => $row['medicine_name'],
            'stock' => isset($row['stock_quantity']) ? (int)$row['stock_quantity'] : 0,
            'price' => isset($row['medicine_price']) && $row['medicine_price'] !== null
                ? (float)$row['medicine_price']
                : null,
            'available' => isset($row['stock_quantity']) ? ((int)$row['stock_quantity'] > 0) : false
        ];
    }
}

if ($currentPharmacy) {
    $pharmacies[] = $currentPharmacy;
}

$stmt->close();

// Hydrate medicines when searching by pharmacy name (top 5 by stock)
if (!$isMedicineSearch && $pharmacyName !== '' && !empty($pharmacies)) {
    $pharmacyIds = array_column($pharmacies, 'id');
    $placeholders = implode(',', array_fill(0, count($pharmacyIds), '?'));

    if ($placeholders) {
        $sqlMedicines = "
            SELECT 
                inv.pharmacy_id,
                m.id AS medicine_id,
                m.name AS medicine_name,
                inv.quantity,
                inv.price
            FROM pharmacy_inventory inv
            INNER JOIN medicines m ON m.id = inv.medicine_id
            WHERE inv.pharmacy_id IN ($placeholders)
            ORDER BY inv.quantity DESC, m.name ASC
            LIMIT 200
        ";

        $stmtMedicines = $db->prepare($sqlMedicines);

        $types = str_repeat('i', count($pharmacyIds));
        $bindParams = [$types];
        foreach ($pharmacyIds as $index => $pharmacyId) {
            $bindParams[] = &$pharmacyIds[$index];
        }
        call_user_func_array([$stmtMedicines, 'bind_param'], $bindParams);

        $stmtMedicines->execute();
        $medResult = $stmtMedicines->get_result();

        $medicineMap = [];
        while ($row = $medResult->fetch_assoc()) {
            $medicineMap[(int)$row['pharmacy_id']][] = [
                'id' => (int)$row['medicine_id'],
                'name' => $row['medicine_name'],
                'stock' => (int)$row['quantity'],
                'price' => $row['price'] !== null ? (float)$row['price'] : null,
                'available' => (int)$row['quantity'] > 0
            ];
        }

        $stmtMedicines->close();

        foreach ($pharmacies as &$pharmacy) {
            if (isset($medicineMap[$pharmacy['id']])) {
                $pharmacy['medicines'] = array_slice($medicineMap[$pharmacy['id']], 0, 5);
            }
        }
        unset($pharmacy);
    }
}

foreach ($pharmacies as &$pharmacy) {
    $availableCount = 0;
    $totalStockForSelection = 0;

    foreach ($pharmacy['medicines'] as $med) {
        if (!empty($med['available'])) {
            $availableCount++;
            $totalStockForSelection += isset($med['stock']) ? (int)$med['stock'] : 0;
        }
    }

    if ($availableCount > 0) {
        $pharmacy['availability_pct'] = $totalStockForSelection >= 50 ? 90 : ($totalStockForSelection >= 20 ? 75 : 60);
        $pharmacy['availability_level'] = $totalStockForSelection >= 20 ? 'success' : 'warning';
        $pharmacy['availability_label'] = $isMedicineSearch ? 'In Stock' : 'Medicines Available';
        $pharmacy['availability_message'] = sprintf(
            '%d medicine(s) available - %d unit(s) on hand.',
            $availableCount,
            $totalStockForSelection
        );
    } else {
        if ($pharmacy['items_in_stock'] > 0 && !$isMedicineSearch) {
            $pharmacy['availability_pct'] = 65;
            $pharmacy['availability_level'] = 'success';
            $pharmacy['availability_label'] = 'Medicines Available';
            $pharmacy['availability_message'] = sprintf(
                '%d medicine(s) recorded in stock.',
                $pharmacy['items_in_stock']
            );
        } else {
            $pharmacy['availability_pct'] = 0;
            $pharmacy['availability_level'] = 'secondary';
            $pharmacy['availability_label'] = $isMedicineSearch ? 'Out of Stock' : 'Inventory Unknown';
            $pharmacy['availability_message'] = $isMedicineSearch
                ? 'Currently out of stock for this medicine.'
                : 'No active inventory recorded.';
        }
    }

    $pharmacy['available_medicine_count'] = $availableCount;
    if ($totalStockForSelection > 0) {
        $pharmacy['total_stock_units'] = $totalStockForSelection;
    }
    $pharmacy['search_type'] = $isMedicineSearch ? 'medicine' : ($pharmacyName !== '' ? 'pharmacy' : 'nearby');
}
unset($pharmacy);

echo json_encode([
    'pharmacies' => $pharmacies,
    'search' => [
        'medicine' => $medicine,
        'pharmacy' => $pharmacyName,
        'type' => $isMedicineSearch ? 'medicine' : ($pharmacyName !== '' ? 'pharmacy' : 'nearby')
    ]
]);


