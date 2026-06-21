<?php
include 'auth.php';
include 'db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: user_index.php");
    exit();
}

// --- 1. HANDLE DELETE SINGLE ORDER ---
if (isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM order_items WHERE order_id = $order_id");
        $conn->query("DELETE FROM orders WHERE id = $order_id");
        $conn->commit();
        header("Location: orders_admin.php?msg=Order Deleted");
    } catch (Exception $e) {
        $conn->rollback();
        die("Error deleting order: " . $e->getMessage());
    }
    exit();
}

// --- 2. HANDLE CLEAR ALL ORDERS ---
if (isset($_GET['clear_all'])) {
    $conn->query("DELETE FROM order_items");
    $conn->query("DELETE FROM orders");
    $conn->query("ALTER TABLE orders AUTO_INCREMENT = 1");
    header("Location: orders_admin.php?msg=All Orders Cleared");
    exit();
}

// --- 3. HANDLE STATUS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    header("Location: orders_admin.php");
    exit();
}

$orders = $conn->query("SELECT o.id, u.name, u.email, o.total, o.order_date, o.status 
                        FROM orders o 
                        JOIN users u ON o.user_id=u.id 
                        ORDER BY o.order_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Manage Customer Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --admin-dark: #1e293b;
            --medical-teal: #17a2b8;
            --bg-soft: #f8fafc;
            --danger: #ef4444;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-soft); margin: 0; display: flex; }

        /* Sidebar Navigation */
        .sidebar { width: 260px; background: var(--admin-dark); height: 100vh; color: white; position: sticky; top: 0; padding: 20px; box-sizing: border-box; }
        .sidebar-brand { font-size: 1.5rem; font-weight: 800; color: var(--medical-teal); margin-bottom: 40px; display: block; text-decoration: none; }
        .nav-group { display: flex; flex-direction: column; gap: 8px; }
        .nav-item { text-decoration: none; color: #94a3b8; padding: 12px 15px; border-radius: 8px; display: flex; align-items: center; gap: 12px; font-weight: 500; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }

        /* Content Area */
        .main-content { flex-grow: 1; padding: 40px; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }

        .btn-clear { background: #fee2e2; color: var(--danger); border: 1px solid #fecaca; padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.85rem; transition: 0.2s; }
        .btn-clear:hover { background: var(--danger); color: white; }

        /* Order Card */
        .order-card { background: white; border-radius: 16px; box-shadow: var(--card-shadow); padding: 25px; margin-bottom: 25px; border: 1px solid #f1f5f9; }
        .order-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; }
        
        .user-info h4 { margin: 0; color: var(--admin-dark); font-size: 1.1rem; }
        .user-info p { margin: 4px 0 0 0; color: #64748b; font-size: 0.85rem; }

        .order-meta { text-align: right; }
        .order-id { display: block; font-weight: 800; color: var(--medical-teal); font-size: 0.9rem; }
        .order-date { font-size: 0.8rem; color: #94a3b8; }

        /* Table */
        .item-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .item-table th { text-align: left; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; padding: 10px 0; border-bottom: 2px solid #f8fafc; }
        .item-table td { padding: 12px 0; border-bottom: 1px solid #f8fafc; font-size: 0.9rem; }

        /* Status & Form */
        .action-row { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 15px 20px; border-radius: 12px; margin-top: 15px; }
        
        .status-select { padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-weight: 600; outline: none; }
        .status-select:focus { border-color: var(--medical-teal); }

        .btn-update { background: var(--medical-teal); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-update:hover { background: #138496; }

        .btn-invoice { background: white; color: #64748b; border: 1.5px solid #e2e8f0; padding: 8px 15px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .btn-invoice:hover { border-color: var(--medical-teal); color: var(--medical-teal); }

        .btn-delete { color: #94a3b8; text-decoration: none; font-size: 0.85rem; transition: 0.2s; }
        .btn-delete:hover { color: var(--danger); }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="admin_index.php" class="sidebar-brand"><i class="fas fa-heartbeat"></i> MediCare<span>+</span></a>
    <nav class="nav-group">
        <a href="admin_index.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="admin_products.php" class="nav-item"><i class="fas fa-pills"></i> Inventory</a>
        <a href="orders_admin.php" class="nav-item active"><i class="fas fa-shopping-cart"></i> Manage Orders</a>
        <a href="reports.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Sales Reports</a>
        <a href="logout.php" class="nav-item" style="margin-top:auto; color:#f87171;"><i class="fas fa-power-off"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="header-actions">
        <div>
            <h1 style="margin:0;">Customer Orders</h1>
            <p style="color: #64748b; margin-top: 5px;">Review patient prescriptions and update fulfillment status.</p>
        </div>
        <a href="orders_admin.php?clear_all=1" class="btn-clear" onclick="return confirm('DANGER: This will permanently erase ALL order history. Continue?')">
            <i class="fas fa-trash-alt"></i> Purge All Records
        </a>
    </div>

    <?php if($orders->num_rows > 0): ?>
        <?php while($order = $orders->fetch_assoc()): ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($order['name']); ?></h4>
                        <p><i class="far fa-envelope"></i> <?php echo htmlspecialchars($order['email']); ?></p>
                    </div>
                    <div class="order-meta">
                        <span class="order-id">Order #<?php echo $order['id']; ?></span>
                        <span class="order-date"><?php echo date("M d, Y • h:i A", strtotime($order['order_date'])); ?></span>
                        <a href="orders_admin.php?delete=<?php echo $order['id']; ?>" class="btn-delete" onclick="return confirm('Permanently remove this order?')">
                            <i class="fas fa-times"></i> Remove Record
                        </a>
                    </div>
                </div>

                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Quantity</th>
                            <th style="text-align: right;">Unit Price</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $items = $conn->prepare("SELECT p.name, oi.quantity, p.price FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
                        $items->bind_param("i", $order['id']);
                        $items->execute();
                        $res = $items->get_result();
                        while($item = $res->fetch_assoc()):
                            $sub = $item['price'] * $item['quantity'];
                        ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;">₹<?php echo number_format($item['price'], 2); ?></td>
                            <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($sub, 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="action-row">
                    <div>
                        <span style="color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Total Billing</span>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--medical-teal);">₹<?php echo number_format($order['total'], 2); ?></div>
                    </div>

                    <form method="POST" style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        
                        <a href="bill.php?order_id=<?php echo $order['id']; ?>&print=1" class="btn-invoice" target="_blank">
                            <i class="fas fa-print"></i> Invoice
                        </a>

                        <select name="status" class="status-select">
                            <option value="Pending" <?php if($order['status']=='Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Accepted" <?php if($order['status']=='Accepted') echo 'selected'; ?>>Accepted</option>
                            <option value="Rejected" <?php if($order['status']=='Rejected') echo 'selected'; ?>>Rejected</option>
                            <option value="Completed" <?php if($order['status']=='Completed') echo 'selected'; ?>>Completed</option>
                        </select>
                        
                        <button type="submit" class="btn-update">Update Status</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 80px; background: white; border-radius: 20px; border: 2px dashed #e2e8f0;">
            <i class="fas fa-inbox" style="font-size: 4rem; color: #e2e8f0; margin-bottom: 20px;"></i>
            <h3 style="color: #94a3b8;">No current orders found</h3>
            <p style="color: #cbd5e1;">New patient orders will appear here automatically.</p>
        </div>
    <?php endif; ?>
</main>

</body>
</html>