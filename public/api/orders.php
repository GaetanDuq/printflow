<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$orders = $_SESSION['orders'] ?? [];

// You can transform/rename fields here later if you want.
echo json_encode(['orders' => $orders], JSON_UNESCAPED_UNICODE);
