<?php

header('Content-Type: application/json; charset=utf-8');

$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lon = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lon === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing coordinates.']);
    exit;
}

$params = http_build_query([
    'format' => 'jsonv2',
    'lat' => $lat,
    'lon' => $lon,
    'zoom' => 18,
    'addressdetails' => 1,
]);

$url = 'https://nominatim.openstreetmap.org/reverse?' . $params;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'MediFinder/1.0 (contact@medifinder.local)',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Reverse geocoding request failed.', 'details' => $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'Reverse geocoding service error.', 'status' => $httpCode]);
    exit;
}

echo $response;
