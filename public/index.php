<?php
session_start();

if (!isset($_SESSION['orders'])) $_SESSION['orders'] = [];
if (!isset($_SESSION['next_id'])) $_SESSION['next_id'] = 1;

require __DIR__ . '/../src/functions.php';

// keep these around so the form can keep previous values
$lastClient   = $_POST['client']   ?? '';
$lastObject   = $_POST['object']   ?? '';
$lastMaterial = $_POST['material'] ?? '';
$lastWeight   = $_POST['weight']   ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1) ADVANCE status
  if (isset($_POST['advance_id'])) {
    $id = (int) $_POST['advance_id'];
    foreach ($_SESSION['orders'] as &$order) {
      if ($order['id'] === $id) {
        $order['status'] = nextStatus($order['status']);
        break;
      }
    }
    unset($order);

  // Delete order
    } elseif (isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    $_SESSION['orders'] = array_values( // reindex after unset
      array_filter($_SESSION['orders'], fn($o) => $o['id'] !== $id)
    );
    }
    // Create order
    elseif (isset($_POST['client'], $_POST['material'], $_POST['weight'])) {

    $client   = trim($_POST['client'] ?? '');
    $object   = trim($_POST['object'] ?? '');
    $material = $_POST['material'] ?? '';
    $weight   = (int)($_POST['weight'] ?? 0);

    // validate first
    if (!isValidMaterial($material)) $errors[] = "Invalid material selected.";
    if (!isValidWeight($weight))     $errors[] = "Weight must be greater than 0.";

    // compute price (only if inputs look sane)
    $price = estimatePrice($material, $weight);
    if ($price === null) $errors[] = "Price could not be calculated.";

    if (empty($errors)) {
      $order = [
        'id'           => $_SESSION['next_id']++,
        // store raw values; escape later when printing
        'client'       => $client,
        'object_name'  => $object,
        'material'     => $material,
        'est_weight_g' => $weight,
        'price_jpy'    => $price,
        'status'       => 'requested',
        'created_at'   => date('Y-m-d H:i:s')
      ];
      $_SESSION['orders'][] = $order;

      // clear the sticky form values after success
      $lastClient = $lastObject = $lastMaterial = $lastWeight = '';
    }
  }
}
?>

<?php if (!empty($errors)): ?>
  <div class="error" style="color:#b00020;">
    <?php foreach ($errors as $e): ?>
      <div><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>


<form method="POST" action="">
  <label>Client Name:
    <input type="text" name="client" value="<?= htmlspecialchars($lastClient) ?>">
  </label><br>

  <label>Object Name:
    <input type="text" name="object" value="<?= htmlspecialchars($lastObject) ?>">
  </label><br>

  <label>Material:
    <select name="material">
      <?php foreach (getMaterials() as $name => $multiplier): ?>
        <option value="<?= $name ?>" <?= ($lastMaterial === $name ? 'selected' : '') ?>>
          <?= $name ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label><br>

  <label>Weight (grams):
    <input type="number" name="weight" value="<?= htmlspecialchars((string)$lastWeight) ?>">
  </label><br>

  <button type="submit">Create Order</button>
</form>



<?php if (!empty($_SESSION['orders'])): ?>
    <h2>Dashboard</h2>
  <p>
    Total Orders: <?= totalOrders($_SESSION['orders']) ?> |
    Total Revenue: <?= formatJPY(totalRevenue($_SESSION['orders'])) ?> |
    Requested: <?= countByStatus($_SESSION['orders'], 'requested') ?> |
    Design: <?= countByStatus($_SESSION['orders'], 'design') ?> |
    Printing: <?= countByStatus($_SESSION['orders'], 'printing') ?> |
    QA: <?= countByStatus($_SESSION['orders'], 'qa') ?> |
    Completed: <?= countByStatus($_SESSION['orders'], 'completed') ?>
  </p>


  <h2>All Orders</h2>
  <table border="1" cellpadding="5">
    <tr>
      <th>ID</th><th>Client</th><th>Object</th><th>Material</th><th>Weight</th><th>Price</th><th>Status</th><th>Created</th><th>Actions</th>
    </tr>
    <?php foreach ($_SESSION['orders'] as $order): ?>
      <tr>
        <td><?= $order['id'] ?></td>
        <td><?= htmlspecialchars($order['client']) ?></td>
        <td><?= htmlspecialchars($order['object_name']) ?></td>
        <td><?= $order['material'] ?></td>
        <td><?= $order['est_weight_g'] ?></td>
        <td><?= formatJPY($order['price_jpy']) ?></td>
        <td><?= $order['status'] ?></td>
        <td><?= $order['created_at'] ?></td>
        <td>
          <?php if ($order['status'] !== 'completed'): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="advance_id" value="<?= $order['id'] ?>">
              <button type="submit">Advance</button>
            </form>
          <?php else: ?>
            —
          <?php endif; ?>

          <form method="POST" style="display:inline"
              onsubmit="return confirm('Delete this order?');">
          <input type="hidden" name="delete_id" value="<?= $order['id'] ?>">
          <button type="submit">Delete</button>
        </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
