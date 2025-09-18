<?php
require __DIR__ . '/../../src/db.php';
header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

try {
  $updated = advanceOrder($id); // returns updated row or null
  if ($updated === null) { http_response_code(404); echo json_encode(['error'=>'Order not found']); exit; }
  echo json_encode(['order' => $updated], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error', 'detail' => $e->getMessage()]);
}
