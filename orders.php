<?php
include 'auth.php';
include 'db.php';

$user_id = intval($_SESSION['user_id']);

/**
 * LOGIC: Cancel Order with Stock Reversal
 * We must add the medicines back to the inventory before deleting the order.
 */
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);

    $conn->begin_transaction();
    try {
        // 1. Verify ownership and 'Pending' status
        $check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'Pending'");
        $check->bind_param("ii", $cancel_id, $user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            
            // 2. Fetch items to revert quantities to the products table
            $items_stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items_stmt->bind_param("i", $cancel_id);
            $items_stmt->execute();
            $items_to_revert = $items_stmt->get_result();

            while ($item = $items_to_revert->fetch_assoc()) {
                $revert_stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $revert_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $revert_stmt->execute();
            }

            // 3. Delete order records
            $del_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $del_items->bind_param("i", $cancel_id);
            $del_items->execute();

            $del_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
            $del_order->bind_param("i", $cancel_id);
            $del_order->execute();

            $conn->commit();
            header("Location: orders.php?msg=Order cancelled and stock updated.");
        } else {
            header("Location: orders.php?msg=Cannot cancel. Order may be processed.");
        }
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: orders.php?msg=Error: " . $e->getMessage());
        exit();
    }
}

// Fetch all orders for current user
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Order History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --medical-teal: #17a2b8;
            --bg-soft: #f4f7f6;
            --text-dark: #2d3436;
            --text-muted: #636e72;
            --danger: #d63031;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-soft); color: var(--text-dark); margin: 0; }

        /* Navbar */
        .navbar { background: white; padding: 1rem 0; box-shadow: 0 2px 20px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 1000; }
        .nav-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .brand { text-decoration: none; color: var(--medical-teal); font-weight: 800; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .nav-menu { display: flex; gap: 25px; }
        .nav-link { text-decoration: none; color: var(--text-muted); font-size: 0.9rem; font-weight: 600; }
        .nav-link.active { color: var(--medical-teal); }

        /* Main Container */
        .container { max-width: 850px; margin: 40px auto; padding: 0 20px; }
        .header-title { font-weight: 800; font-size: 2rem; margin: 0 0 10px 0; }
        .header-subtitle { color: var(--text-muted); margin-bottom: 40px; }

        .alert-box { background: #e3fcf7; color: #00b894; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; border: 1px solid #55efc4; }

        /* Order Card */
        .order-card { background: white; border-radius: 20px; box-shadow: var(--card-shadow); margin-bottom: 30px; overflow: hidden; border: 1px solid #f1f2f6; }
        
        .order-top { background: #fafbfc; padding: 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f2f6; }
        .order-info h4 { margin: 0; font-size: 1.1rem; font-weight: 800; }
        .order-info span { font-size: 0.8rem; color: var(--text-muted); }

        .status-pill { padding: 6px 14px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: #fff9db; color: #f08c00; }
        .status-accepted { background: #e3fafc; color: #0c8599; }
        .status-completed { background: #ebfbee; color: #2b8a3e; }

        /* Medicine List */
        .medicine-list { padding: 25px; }
        .medicine-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #f1f2f6; }
        .medicine-row:last-child { border-bottom: none; }
        .med-name { font-weight: 600; font-size: 0.95rem; flex: 2; }
        .med-qty { color: var(--text-muted); font-size: 0.85rem; flex: 1; text-align: center; }
        .med-price { font-weight: 700; flex: 1; text-align: right; }

        /* Footer */
        .order-bottom { padding: 25px; display: flex; justify-content: space-between; align-items: center; background: #fafbfc; }
        .grand-total { font-size: 1.4rem; font-weight: 800; color: var(--medical-teal); }
        .grand-total small { font-size: 0.7rem; color: var(--text-muted); display: block; text-transform: uppercase; margin-bottom: 4px; }

        .btn-cancel-order { background: white; color: var(--danger); border: 1.5px solid #ffc9c9; padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.85rem; transition: 0.2s; }
        .btn-cancel-order:hover { background: #fff5f5; border-color: var(--danger); }

        .empty-history { text-align: center; padding: 80px 40px; background: white; border-radius: 20px; }
        .empty-history i { font-size: 4rem; color: #f1f2f6; margin-bottom: 20px; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="user_index.php" class="brand"><i class="fas fa-heartbeat"></i> MediCare</a>
        <div class="nav-menu">
            <a href="user_index.php" class="nav-link">Dashboard</a>
            <a href="products.php" class="nav-link">Pharmacy</a>
            <a href="orders.php" class="nav-link active">My Orders</a>
        </div>
        <a href="cart.php" class="nav-link" style="font-weight:700; color: var(--medical-teal);">
            <i class="fas fa-shopping-cart"></i> Cart
        </a>
    </div>
</nav>

<div class="container">
    <h1 class="header-title">Orders History</h1>
    <p class="header-subtitle">Track your medical prescriptions and past deliveries.</p>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert-box"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <?php if ($orders_result->num_rows > 0): ?>
        <?php while($order = $orders_result->fetch_assoc()): 
            $status = htmlspecialchars($order['status']);
            $items_stmt = $conn->prepare("SELECT oi.quantity, p.name, p.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $items_stmt->bind_param("i", $order['id']);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
        ?>
            <div class="order-card">
                <div class="order-top">
                    <div class="order-info">
                        <h4>Reference #<?php echo $order['id']; ?></h4>
                        <span>Placed on <?php echo date("F j, Y • g:i A", strtotime($order['order_date'])); ?></span>
                    </div>
                    <span class="status-pill status-<?php echo strtolower($status); ?>">
                        <?php echo $status; ?>
                    </span>
                </div>

                <div class="medicine-list">
                    <?php while($item = $items_result->fetch_assoc()): ?>
                        <div class="medicine-row">
                            <span class="med-name"><?php echo htmlspecialchars($item['name']); ?></span>
                            <span class="med-qty">x<?php echo $item['quantity']; ?></span>
                            <span class="med-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="order-bottom">
                    <div class="grand-total">
                        <small>Paid Amount</small>
                        ₹<?php echo number_format($order['total'], 2); ?>
                    </div>
                    
                    <?php if ($status == 'Pending'): ?>
                        <a href="orders.php?cancel_id=<?php echo $order['id']; ?>" class="btn-cancel-order" onclick="return confirm('Cancel this order and return medicines to stock?')">
                            Cancel Order
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-history">
            <i class="fas fa-folder-open"></i>
            <h3>No orders yet</h3>
            <p style="color:var(--text-muted);">You haven't purchased any medicines from our pharmacy.</p>
            <a href="products.php" class="btn-cancel-order" style="display:inline-block; margin-top:20px; text-decoration:none; border-color:var(--medical-teal); color:var(--medical-teal);">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>