<?php
// public/index.php

require __DIR__ . '/../src/functions.php';
require __DIR__ . '/../src/db.php';

// ---- sticky form values + errors ----
$errors       = [];
$lastClient   = $_POST['client']   ?? '';
$lastObject   = $_POST['object']   ?? '';
$lastMaterial = $_POST['material'] ?? '';
$lastWeight   = $_POST['weight']   ?? '';

// ---- POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1) ADVANCE
  if (isset($_POST['advance_id'])) {
    $id = (int) $_POST['advance_id'];
    try { advanceOrder($id); } catch (Throwable $e) {}

  // 2) DELETE
  } elseif (isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    try { deleteOrder($id); } catch (Throwable $e) {}

  // 3) CREATE
  } elseif (isset($_POST['client'], $_POST['material'], $_POST['weight'])) {
    $client   = trim($_POST['client'] ?? '');
    $object   = trim($_POST['object'] ?? '');
    $material = $_POST['material'] ?? '';
    $weight   = (int) ($_POST['weight'] ?? 0);

    if ($client === '')  { $errors[] = "Client is required."; }
    if ($object === '')  { $errors[] = "Object name is required."; }
    if (!isValidMaterial($material)) { $errors[] = "Invalid material selected."; }
    if (!isValidWeight($weight))     { $errors[] = "Weight must be greater than 0."; }

    $price = null;
    if (empty($errors)) {
      $price = estimatePrice($material, $weight);
      if ($price === null) $errors[] = "Price could not be calculated.";
    }

    if (empty($errors)) {
      try {
        createOrder($client, $object, $material, $weight, $price);
        // clear sticky values on success
        $lastClient = $lastObject = $lastMaterial = $lastWeight = '';
      } catch (Throwable $e) {
        $errors[] = "Database error while creating order.";
      }
    }
  }
}

// ---- fetch orders for render ----
$orders = getOrders();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>PrintFlow — Orders</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui, sans-serif; margin:16px}
    .error{color:#b00020; margin:8px 0}
    .badge{display:inline-block; padding:2px 6px; border-radius:6px; background:#eef; border:1px solid #ccd}
    table{border-collapse:collapse; width:100%; max-width:1100px}
    th,td{border:1px solid #ddd; padding:8px; text-align:left}
    th{background:#fafafa}
    .actions form{display:inline}
    .hint{color:#666; font-size:12px}
  </style>
</head>
<body>
  <h1>PrintFlow — Orders</h1>
  <p class="hint">Tip: open <a href="/board.php">/board.php</a> to view the Vue Kanban board.</p>

  <?php if (!empty($errors)): ?>
    <div class="error">
      <?php foreach ($errors as $e): ?>
        <div><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h2>Create Order</h2>
  <form method="POST" action="" style="margin-bottom:16px">
    <div>
      <label>Client:
        <input type="text" name="client" value="<?= htmlspecialchars($lastClient) ?>">
      </label>
    </div>
    <div>
      <label>Object Name:
        <input type="text" name="object" value="<?= htmlspecialchars($lastObject) ?>">
      </label>
    </div>
    <div>
      <label>Material:
        <select name="material">
          <?php foreach (getMaterials() as $name => $multiplier): ?>
            <option value="<?= $name ?>" <?= ($lastMaterial === $name ? 'selected' : '') ?>>
              <?= $name ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <div>
      <label>Weight (grams):
        <input type="number" name="weight" value="<?= htmlspecialchars((string)$lastWeight) ?>">
      </label>
    </div>
    <button type="submit">Create Order</button>
  </form>

  <?php if (!empty($orders)): ?>
    <h2>Dashboard</h2>
    <p>
      Total Orders: <?= totalOrders($orders) ?> |
      Total Revenue: <?= formatJPY(totalRevenue($orders)) ?> |
      Requested: <span class="badge"><?= countByStatus($orders, 'requested') ?></span> |
      Design: <span class="badge"><?= countByStatus($orders, 'design') ?></span> |
      Printing: <span class="badge"><?= countByStatus($orders, 'printing') ?></span> |
      QA: <span class="badge"><?= countByStatus($orders, 'qa') ?></span> |
      Completed: <span class="badge"><?= countByStatus($orders, 'completed') ?></span>
    </p>

    <h2>All Orders</h2>
    <table>
      <tr>
        <th>ID</th>
        <th>Client</th>
        <th>Object</th>
        <th>Material</th>
        <th>Weight (g)</th>
        <th>Price</th>
        <th>Status</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><?= $order['id'] ?></td>
          <td><?= htmlspecialchars($order['client']) ?></td>
          <td><?= htmlspecialchars($order['object_name']) ?></td>
          <td><?= $order['material'] ?></td>
          <td><?= (int)$order['est_weight_g'] ?></td>
          <td><?= formatJPY((int)$order['price_jpy']) ?></td>
          <td><?= $order['status'] ?></td>
          <td><?= $order['created_at'] ?></td>
          <td class="actions">
            <?php if ($order['status'] !== 'completed'): ?>
              <form method="POST">
                <input type="hidden" name="advance_id" value="<?= $order['id'] ?>">
                <button type="submit">Advance</button>
              </form>
            <?php endif; ?>
            <form method="POST" onsubmit="return confirm('Delete this order?');">
              <input type="hidden" name="delete_id" value="<?= $order['id'] ?>">
              <button type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No orders yet. Create your first one above.</p>
  <?php endif; ?>
</body>
</html>
