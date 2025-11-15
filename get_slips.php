<?php
// get_slips.php
header('Content-Type: application/json; charset=utf-8');
$csvPath = __DIR__ . '/slips.csv';
$from = $_GET['from'] ?? ''; // yyyy-mm-dd
$to = $_GET['to'] ?? '';
$vehicle = strtolower(trim($_GET['vehicle'] ?? ''));

$rows = [];
if (!file_exists($csvPath)) { echo json_encode(['ok'=>true,'slips'=>[]]); exit; }

$fp = fopen($csvPath, 'r');
if (!$fp) { echo json_encode(['ok'=>false,'err'=>'cannot open']); exit; }
// read header
$header = fgetcsv($fp);
while (($data = fgetcsv($fp)) !== false) {
    // map row to assoc
    $r = array_combine($header, $data);
    // filter by date range
    $created = strtotime($r['createdAt'] ?? '');
    if ($from) { $fromT = strtotime($from . ' 00:00:00'); if ($created < $fromT) continue; }
    if ($to)   { $toT = strtotime($to . ' 23:59:59'); if ($created > $toT) continue; }
    if ($vehicle) {
        if (strpos(strtolower($r['vehicleNo'] ?? ''), $vehicle) === false) continue;
    }
    $rows[] = [
      'id' => $r['serial'],
      'createdAt' => $r['createdAt'],
      'vehicleNo' => $r['vehicleNo'],
      'amount' => $r['amount'],
      'filename' => null
    ];
}
fclose($fp);
// sort desc by createdAt
usort($rows, function($a,$b){ return strtotime($b['createdAt']) - strtotime($a['createdAt']); });
echo json_encode(['ok'=>true,'slips'=>$rows]);
