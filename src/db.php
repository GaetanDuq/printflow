<?php
function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . __DIR__ . '/../data/printflow.sqlite');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}

// SELECT * FROM orders
function getOrders(): array {
    $stmt = getDB()->query("SELECT * FROM orders ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// INSERT new order
function createOrder(string $client, string $object, string $material, int $weight, int $price): int {
    $stmt = getDB()->prepare("
        INSERT INTO orders (client, object_name, material, est_weight_g, price_jpy, status, created_at)
        VALUES (:client, :object_name, :material, :est_weight_g, :price_jpy, 'requested', :created_at)
    ");
    $stmt->execute([
        ':client' => $client,
        ':object_name' => $object,
        ':material' => $material,
        ':est_weight_g' => $weight,
        ':price_jpy' => $price,
        ':created_at' => date('Y-m-d H:i:s')
    ]);
    return (int) getDB()->lastInsertId();
}

// UPDATE status
function advanceOrder(int $id): ?array {
    $statuses = ['requested','design','printing','qa','completed'];

    // get current status
    $stmt = getDB()->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    if ($current === false) return null;

    $i = array_search($current, $statuses, true);
    $next = ($i === false || $i >= count($statuses) - 1) ? 'completed' : $statuses[$i+1];

    $stmt = getDB()->prepare("UPDATE orders SET status = :next WHERE id = :id");
    $stmt->execute([':next' => $next, ':id' => $id]);

    // return updated row
    $stmt = getDB()->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// DELETE
function deleteOrder(int $id): bool {
    $stmt = getDB()->prepare("DELETE FROM orders WHERE id = ?");
    return $stmt->execute([$id]);
}
