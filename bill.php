<?php
include 'auth.php';
include 'db.php';

$order_id = intval($_GET['order_id']);
$print = isset($_GET['print']) ? true : false;

// Fetch order + items
$stmt = $conn->prepare("SELECT o.id, o.total, o.order_date, o.status, u.name, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id=u.id 
                        WHERE o.id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

$items = $conn->prepare("SELECT p.name, oi.quantity, p.price 
                         FROM order_items oi 
                         JOIN products p ON oi.product_id=p.id 
                         WHERE oi.order_id=?");
$items->bind_param("i", $order_id);
$items->execute();
$res = $items->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Invoice #<?php echo $order['id']; ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <?php if ($print): ?>
    <script>window.onload = function(){ window.print(); }</script>
  <?php endif; ?>
</head>
<body>
<div class="container">
  <h2>Invoice #<?php echo $order['id']; ?></h2>
  <p><strong>Customer:</strong> <?php echo $order['name']; ?> (<?php echo $order['email']; ?>)</p>
  <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
  <p><strong>Status:</strong> <?php echo $order['status']; ?></p>

  <table border="1" width="100%" cellpadding="8" cellspacing="0">
    <tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>
    <?php while($item = $res->fetch_assoc()): 
      $subtotal = $item['price'] * $item['quantity']; ?>
      <tr>
        <td><?php echo $item['name']; ?></td>
        <td><?php echo $item['quantity']; ?></td>
        <td>₹<?php echo $item['price']; ?></td>
        <td>₹<?php echo $subtotal; ?></td>
      </tr>
    <?php endwhile; ?>
  </table>

  <p><strong>Total: ₹<?php echo $order['total']; ?></strong></p>
</div>
</body>
</html>
