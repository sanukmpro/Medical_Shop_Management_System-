<?php
include 'auth.php';
include 'db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: user_index.php");
    exit();
}

// Get the filter type from URL (default to 'daily')
$filter = isset($_GET['range']) ? $_GET['range'] : 'daily';
$title_text = "Daily Sales Breakdown";
$group_by = "DATE(o.order_date)";
$format = "%d %M, %Y";

if ($filter === 'monthly') {
    $title_text = "Monthly Sales Breakdown";
    $group_by = "DATE_FORMAT(o.order_date, '%Y-%m')";
    $format = "%M %Y";
} elseif ($filter === 'yearly') {
    $title_text = "Yearly Sales Breakdown";
    $group_by = "YEAR(o.order_date)";
    $format = "%Y";
}

/**
 * UPDATED QUERY: 
 * We join orders (o), users (u), order_items (oi), and products (p)
 * to get the full detail of every transaction.
 */
$query = "SELECT 
            DATE_FORMAT(o.order_date, '$format') as period,
            u.name as customer_name,
            p.name as medicine_name,
            oi.quantity,
            (p.price * oi.quantity) as line_total,
            o.order_date
          FROM orders o
          JOIN users u ON o.user_id = u.id
          JOIN order_items oi ON o.id = oi.order_id
          JOIN products p ON oi.product_id = p.id
          ORDER BY o.order_date DESC";

$sales_result = $conn->query($query);

// Inventory Alerts (Stay the same)
$low_stock = $conn->query("SELECT name, stock FROM products WHERE stock < 5 ORDER BY stock ASC");
$expired = $conn->query("SELECT name, expiry_date FROM products WHERE expiry_date < CURDATE() ORDER BY expiry_date ASC");

// Total Revenue
$total_revenue = $conn->query("SELECT SUM(total) as total FROM orders")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Reports & Analytics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --admin-dark: #1e293b;
            --medical-teal: #17a2b8;
            --bg-soft: #f8fafc;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-soft); margin: 0; display: flex; }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--admin-dark); height: 100vh; color: white; position: sticky; top: 0; padding: 20px; box-sizing: border-box; }
        .sidebar-brand { font-size: 1.5rem; font-weight: 800; color: var(--medical-teal); margin-bottom: 40px; display: block; text-decoration: none; }
        .nav-group { display: flex; flex-direction: column; gap: 8px; }
        .nav-item { text-decoration: none; color: #94a3b8; padding: 12px 15px; border-radius: 8px; display: flex; align-items: center; gap: 12px; font-weight: 500; transition: 0.3s; }
        .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }

        .main-content { flex-grow: 1; padding: 40px; }

        /* Report Controls */
        .controls-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .filter-btns { display: flex; background: #e2e8f0; padding: 5px; border-radius: 12px; gap: 5px; }
        .filter-link { text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; color: #64748b; transition: 0.2s; }
        .filter-link.active { background: white; color: var(--medical-teal); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        .btn-download { background: var(--medical-teal); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }

        /* Tables & Cards */
        .report-card { background: white; border-radius: 16px; box-shadow: var(--card-shadow); padding: 25px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; padding: 12px 0; border-bottom: 2px solid #f8fafc; }
        td { padding: 15px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }

        .total-banner { background: var(--medical-teal); color: white; padding: 30px; border-radius: 16px; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; }

        @media print {
            .sidebar, .controls-row, .btn-download { display: none !important; }
            .main-content { padding: 0; }
            .report-card { box-shadow: none; border: 1px solid #eee; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="admin_index.php" class="sidebar-brand"><i class="fas fa-heartbeat"></i> MediCare<span>+</span></a>
    <nav class="nav-group">
        <a href="admin_index.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="admin_products.php" class="nav-item"><i class="fas fa-pills"></i> Inventory</a>
        <a href="orders_admin.php" class="nav-item"><i class="fas fa-shopping-cart"></i> Manage Orders</a>
        <a href="reports.php" class="nav-item active"><i class="fas fa-file-invoice-dollar"></i> Sales Reports</a>
        <a href="logout.php" class="nav-item" style="margin-top:auto; color:#f87171;"><i class="fas fa-power-off"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="controls-row">
        <div>
            <h1 style="margin:0; font-size: 1.8rem;">Detailed Sales Report</h1>
            <p style="color: #64748b; margin-top: 5px;">Itemized transaction history for healthcare products.</p>
        </div>
        
        <div style="display: flex; gap: 15px;">
            <div class="filter-btns">
                <a href="reports.php?range=daily" class="filter-link <?php echo $filter == 'daily' ? 'active' : ''; ?>">Daily</a>
                <a href="reports.php?range=monthly" class="filter-link <?php echo $filter == 'monthly' ? 'active' : ''; ?>">Monthly</a>
                <a href="reports.php?range=yearly" class="filter-link <?php echo $filter == 'yearly' ? 'active' : ''; ?>">Yearly</a>
            </div>
            <button onclick="window.print()" class="btn-download">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="total-banner">
        <i class="fas fa-wallet" style="font-size: 2.5rem; opacity: 0.6;"></i>
        <div>
            <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Total System Revenue</span>
            <h2 style="margin:0; font-size: 2.2rem;">₹<?php echo number_format($total_revenue, 2); ?></h2>
        </div>
    </div>

    <div class="report-card">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-receipt" style="color: var(--medical-teal);"></i> Itemized Sales: <?php echo $title_text; ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Date / Period</th>
                    <th>Customer</th>
                    <th>Medicine</th>
                    <th>Qty</th>
                    <th style="text-align: right;">Total Sale</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sales_result->num_rows > 0): ?>
                    <?php while($row = $sales_result->fetch_assoc()): ?>
                    <tr>
                        <td style="color: #64748b; font-size: 0.85rem;"><?php echo $row['period']; ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                        <td><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">x<?php echo $row['quantity']; ?></span></td>
                        <td style="text-align: right; font-weight: 700; color: var(--medical-teal);">₹<?php echo number_format($row['line_total'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">No sales data found for this period.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
        <div class="report-card" style="border-top: 4px solid #f59e0b;">
            <h3 style="font-size: 1rem;"><i class="fas fa-exclamation-triangle"></i> Low Stock Warning</h3>
            <?php while($row = $low_stock->fetch_assoc()): ?>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                    <span><?php echo htmlspecialchars($row['name']); ?></span>
                    <strong style="color: #c2410c;"><?php echo $row['stock']; ?> left</strong>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="report-card" style="border-top: 4px solid #ef4444;">
            <h3 style="font-size: 1rem;"><i class="fas fa-calendar-times"></i> Expiry Alert</h3>
            <?php while($row = $expired->fetch_assoc()): ?>
                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9;">
                    <span><?php echo htmlspecialchars($row['name']); ?></span>
                    <strong style="color: #b91c1c;"><?php echo date("M Y", strtotime($row['expiry_date'])); ?></strong>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

</body>
</html>