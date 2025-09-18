<?php
require __DIR__ . '/../../src/db.php';
header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

try {
  $ok = deleteOrder($id);
  echo json_encode(['ok' => (bool)$ok]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error', 'detail' => $e->getMessage()]);
}
