<?php
include 'auth.php';
include 'db.php';

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.price 
                        FROM cart c JOIN products p ON c.product_id=p.id 
                        WHERE c.user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$order_items = [];
while($row = $result->fetch_assoc()) {
    $subtotal = $row['price'] * $row['quantity'];
    $total += $subtotal;
    $order_items[] = $row;
}

$conn->query("INSERT INTO orders (user_id,total) VALUES ($user_id,$total)");
$order_id = $conn->insert_id;

foreach($order_items as $item) {
    $conn->query("INSERT INTO order_items (order_id,product_id,quantity,price) 
                  VALUES ($order_id,{$item['product_id']},{$item['quantity']},{$item['price']})");
    $conn->query("UPDATE products SET stock=stock-{$item['quantity']} WHERE id={$item['product_id']}");
}
$conn->query("DELETE FROM cart WHERE user_id=$user_id");

header("Location: bill.php?order_id=$order_id");
exit();
?>
