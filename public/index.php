<?php

session_start();

require __DIR__ . '/../src/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $client = $_POST['client'] ?? '';
  $object = $_POST['object'] ?? '';
  $material = $_POST['material'] ?? '';
  $weight = (int) ($_POST['weight'] ?? 0);

  $price = estimatePrice($material, $weight);

  if ($price === null) {
      echo "Invalid input. Please try again.<br>";
    } else {
      $order = [
        'id' => $_SESSION['next_id']++,
        'client' => htmlspecialchars($client),
        'object_name' => htmlspecialchars($object),
        'material' => $material,
        'est_weight_g' => $weight,
        'price_jpy' => $price,
        'status' => 'requested',
        'created_at' => date('Y-m-d H:i:s')
      ];

      $_SESSION['orders'][] = $order;
    }
}
?>

<form method="POST" action="">
  <label>Client Name:
    <input type="text" name="client">
  </label><br>

  <label>Object Name:
    <input type="text" name="object">
  </label><br>

  <label>Material:
    <select name="material">
      <option value="PLA">PLA</option>
      <option value="ABS">ABS</option>
      <option value="Aluminum">Aluminum</option>
      <option value="Titanium">Titanium</option>
    </select>
  </label><br>

  <label>Weight (grams):
    <input type="number" name="weight">
  </label><br>

  <button type="submit">Create Order</button>
</form>


<?php if (!empty($_SESSION['orders'])): ?>
  <h2>All Orders</h2>
  <table border="1" cellpadding="5">
    <tr>
      <th>ID</th><th>Client</th><th>Object</th><th>Material</th><th>Weight</th><th>Price</th><th>Status</th><th>Created</th>
    </tr>
    <?php foreach ($_SESSION['orders'] as $order): ?>
      <tr>
        <td><?= $order['id'] ?></td>
        <td><?= $order['client'] ?></td>
        <td><?= $order['object_name'] ?></td>
        <td><?= $order['material'] ?></td>
        <td><?= $order['est_weight_g'] ?></td>
        <td><?= formatJPY($order['price_jpy']) ?></td>
        <td><?= $order['status'] ?></td>
        <td><?= $order['created_at'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
