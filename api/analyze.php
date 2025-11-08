<?php
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$text = isset($body['text']) ? strtolower($body['text']) : '';

// Simple rule-based extractor and recommender (placeholder for AI model)
$catalog = [
  'paracetamol' => ['use' => 'Pain relief, fever', 'alternatives' => ['Acetaminophen', 'Ibuprofen']],
  'amoxicillin' => ['use' => 'Antibiotic', 'alternatives' => ['Co-amoxiclav', 'Cefalexin']],
  'ibuprofen' => ['use' => 'Anti-inflammatory, pain', 'alternatives' => ['Naproxen', 'Diclofenac']],
  'cetirizine' => ['use' => 'Allergy relief', 'alternatives' => ['Loratadine', 'Fexofenadine']],
  'metformin' => ['use' => 'Blood sugar control', 'alternatives' => ['Glimepiride', 'Sitagliptin']],
  'omeprazole' => ['use' => 'Acid reflux, GERD', 'alternatives' => ['Pantoprazole', 'Esomeprazole']],
];

$items = [];
foreach ($catalog as $key => $info) {
  if (strpos($text, $key) !== false) {
    $items[] = [
      'name' => ucfirst($key),
      'use' => $info['use'],
      'alternatives' => $info['alternatives'],
    ];
  }
}

// If no direct hits, try fuzzy suggestions by symptoms keywords
if (!$items) {
  $symptoms = [
    'fever' => 'paracetamol',
    'pain' => 'ibuprofen',
    'allergy' => 'cetirizine',
    'cough' => 'dextromethorphan',
    'acid' => 'omeprazole',
  ];
  foreach ($symptoms as $kw => $suggest) {
    if (strpos($text, $kw) !== false) {
      $name = $suggest;
      $info = $catalog[$name] ?? ['use' => 'Suggested by symptom', 'alternatives' => []];
      $items[] = [
        'name' => ucfirst($name),
        'use' => $info['use'],
        'alternatives' => $info['alternatives'],
      ];
    }
  }
}

echo json_encode(['items' => $items]);


