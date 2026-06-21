<?php
include 'auth.php';
include 'db.php';

header('Content-Type: application/json');

$user_id = intval($_SESSION['user_id']);

// 1. Get cart items
$stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.price, p.stock 
                        FROM cart c JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$items = [];
while($row = $result->fetch_assoc()) {
    // Basic stock check
    if($row['quantity'] > $row['stock']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock for one of your items.']);
        exit();
    }
    $items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}

if (count($items) === 0) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit();
}

// 2. Transaction Logic
$conn->begin_transaction();
try {
    // Insert Order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, status, order_date) VALUES (?, ?, 'Pending', NOW())");
    $stmt->bind_param("id", $user_id, $total);
    $stmt->execute();
    $order_id = $conn->insert_id;

    foreach ($items as $item) {
        // Insert Items
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt_item->bind_param("iii", $order_id, $item['product_id'], $item['quantity']);
        $stmt_item->execute();

        // Deduct Stock
        $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }

    // Clear Cart
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");

    $conn->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>