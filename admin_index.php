<?php
include 'auth.php';
include 'db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: user_index.php");
    exit();
}

$current_date = date('Y-m-d');

// --- STATS ---
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];

// Alerts: Low Stock OR Expired/Expiring Soon
$alert_count_query = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock <= 5 OR expiry_date <= DATE_ADD('$current_date', INTERVAL 30 DAY)");
$alert_count = $alert_count_query->fetch_assoc()['count'];

// --- FETCH CRITICAL INVENTORY ---
// Items that are either low in stock OR expiring/expired
$critical_stock = $conn->query("SELECT id, name, stock, expiry_date FROM products 
                                WHERE stock <= 5 
                                OR expiry_date <= DATE_ADD('$current_date', INTERVAL 30 DAY) 
                                ORDER BY expiry_date ASC, stock ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Admin Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --admin-bg: #0f172a;
            --medical-teal: #17a2b8;
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --surface: #ffffff;
            --text-main: #1e293b;
        }

        body { font-family: 'Inter', sans-serif; background: #f1f5f9; margin: 0; display: flex; color: var(--text-main); }

        /* Sidebar Customization */
        .sidebar { width: 260px; background: var(--admin-bg); height: 100vh; position: sticky; top: 0; padding: 30px 20px; box-sizing: border-box; color: #94a3b8; }
        .sidebar-brand { color: white; font-size: 1.5rem; font-weight: 800; text-decoration: none; margin-bottom: 50px; display: block; }
        .sidebar-brand span { color: var(--medical-teal); }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 14px 18px; color: #94a3b8; text-decoration: none; border-radius: 12px; font-weight: 500; margin-bottom: 8px; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.05); color: white; }
        .nav-item.active { border-left: 4px solid var(--medical-teal); border-radius: 4px 12px 12px 4px; }

        /* Content Area */
        .main { flex-grow: 1; padding: 40px; }
        .stats-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 40px; }
        .card { background: var(--surface); padding: 25px; border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }

        .stat-card { display: flex; justify-content: space-between; align-items: center; }
        .stat-info h3 { margin: 0; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; }
        .stat-info p { margin: 5px 0 0 0; font-size: 1.8rem; font-weight: 800; }
        .stat-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }

        /* Table Styling */
        .alert-box { border-top: 4px solid var(--danger); }
        .table-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 0.75rem; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }

        .status-pill { padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .pill-danger { background: #fee2e2; color: #991b1b; }
        .pill-warning { background: #fef3c7; color: #92400e; }

        .action-link { color: var(--medical-teal); text-decoration: none; font-weight: 600; font-size: 0.85rem; }
        .action-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<nav class="sidebar">
    <a href="admin_index.php" class="sidebar-brand">MediCare<span>+</span></a>
    <div class="nav-links">
        <a href="admin_index.php" class="nav-item active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="admin_products.php" class="nav-item"><i class="fas fa-pills"></i> Inventory</a>
        <a href="orders_admin.php" class="nav-item"><i class="fas fa-shopping-bag"></i> Orders</a>
        <a href="reports.php" class="nav-item"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="logout.php" class="nav-item" style="margin-top: 40px; color: #f87171;"><i class="fas fa-power-off"></i> Logout</a>
    </div>
</nav>

<div class="main">
    <div class="header">
        <h1 style="margin:0;">Dashboard Overview</h1>
        <p style="color: #64748b;">Real-time pharmacy analytics and alerts.</p>
    </div>

    <div class="stats-container">
        <div class="card stat-card">
            <div class="stat-info">
                <h3>Total Catalog</h3>
                <p><?php echo $total_products; ?></p>
            </div>
            <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;"><i class="fas fa-pills"></i></div>
        </div>
        <div class="card stat-card">
            <div class="stat-info">
                <h3>Patient Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>
            <div class="stat-icon" style="background: #f0fdf4; color: #15803d;"><i class="fas fa-shopping-cart"></i></div>
        </div>
        <div class="card stat-card">
            <div class="stat-info">
                <h3>Critical Alerts</h3>
                <p><?php echo $alert_count; ?></p>
            </div>
            <div class="stat-icon" style="background: #fef2f2; color: #b91c1c;"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>

    <div class="card alert-box">
        <div class="table-title">
            <i class="fas fa-heartbeat" style="color: var(--danger);"></i> 
            Critical Inventory Issues
        </div>

        <?php if ($critical_stock->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Medicine</th>
                        <th>Stock Level</th>
                        <th>Expiry Date</th>
                        <th>Issue Status</th>
                        <th>Management</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $critical_stock->fetch_assoc()): 
                        $exp_ts = strtotime($row['expiry_date']);
                        $is_expired = ($exp_ts < time());
                        $is_expiring_soon = ($exp_ts < strtotime('+30 days') && !$is_expired);
                        $is_low = ($row['stock'] <= 5);
                    ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td>
                                <span style="color: <?php echo $is_low ? 'var(--danger)' : 'inherit'; ?>;">
                                    <?php echo $row['stock']; ?> units
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', $exp_ts); ?></td>
                            <td>
                                <?php if ($is_expired): ?>
                                    <span class="status-pill pill-danger">Expired</span>
                                <?php elseif ($is_expiring_soon): ?>
                                    <span class="status-pill pill-warning">Expiring Soon</span>
                                <?php endif; ?>

                                <?php if ($is_low): ?>
                                    <span class="status-pill pill-danger">Low Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="admin_edit_product.php?id=<?php echo $row['id']; ?>" class="action-link">
                                    <i class="fas fa-edit"></i> Manage
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align:center; padding: 20px; color: #94a3b8;">
                <i class="fas fa-check-circle" style="font-size: 2rem; display: block; margin-bottom: 10px; color: var(--success);"></i>
                No critical stock issues detected.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>