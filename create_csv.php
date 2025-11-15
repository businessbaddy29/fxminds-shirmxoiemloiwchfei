<?php
// create_csv.php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['ok'=>false,'err'=>'Method']); exit;
}
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!$payload) { echo json_encode(['ok'=>false,'err'=>'bad json']); exit; }

// expect payload.slip or full body
$slip = $payload['slip'] ?? $payload;

// CSV path (ensure folder writable)
$csvPath = __DIR__ . '/slips.csv';

// prepare row fields (change order as you like)
$serial = $slip['serial'] ?? ($slip['id'] ?? time());
$createdAt = $slip['savedAt'] ?? date('Y-m-d H:i:s');
$row = [
  $serial,
  $createdAt,
  $slip['vehicleNo'] ?? '',
  $slip['rstNo'] ?? '',
  $slip['firmName'] ?? '',
  $slip['firmAddress'] ?? '',
  $slip['firmPhone'] ?? '',
  $slip['item'] ?? '',
  $slip['gross'] ?? '',
  $slip['grossDate'] ?? '',
  $slip['grossTime'] ?? '',
  $slip['tare'] ?? '',
  $slip['tareDate'] ?? '',
  $slip['tareTime'] ?? '',
  $slip['net'] ?? '',
  isset($slip['charges']) ? $slip['charges'] : '',
  isset($slip['amount']) ? $slip['amount'] : '',
  $slip['phone'] ?? '',
];

// ensure file exists and has header
$needHeader = !file_exists($csvPath) || filesize($csvPath) === 0;
$fp = fopen($csvPath, 'a+');
if (!$fp) { echo json_encode(['ok'=>false,'err'=>'cannot open csv']); exit; }
if (flock($fp, LOCK_EX)) {
    if ($needHeader) {
        fputcsv($fp, ['serial','createdAt','vehicleNo','rstNo','firmName','firmAddress','firmPhone','item','gross','grossDate','grossTime','tare','tareDate','tareTime','net','charges','amount','phone']);
    }
    fputcsv($fp, $row);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(['ok'=>true, 'serial'=>$serial]);
} else {
    fclose($fp);
    http_response_code(500);
    echo json_encode(['ok'=>false,'err'=>'lock failed']);
}
