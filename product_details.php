<?php
include 'auth.php';
include 'db.php';

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Product Details</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
  <h2><?php echo $product['name']; ?></h2>
  <p><?php echo $product['description']; ?></p>
  <p>Batch: <?php echo $product['batch_no']; ?></p>
  <p>Expiry: <?php echo $product['expiry_date']; ?></p>
  <p>Manufacturer: <?php echo $product['manufacturer']; ?></p>
  <p>Price: ₹<?php echo $product['price']; ?></p>
  <?php if (strtotime($product['expiry_date']) < time()): ?>
    <p style="color:red;"><strong>This medicine has expired and cannot be purchased.</strong></p>
  <?php else: ?>
    <?php if ($product['stock'] <= 5): ?>
      <p style="color:orange;"><strong>Only <?php echo $product['stock']; ?> left in stock!</strong></p>
    <?php endif; ?>
    <a href="cart.php?add=<?php echo $product['id']; ?>" class="btn">Add to Cart</a>
  <?php endif; ?>
</div>
</body>
</html>
