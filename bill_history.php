<?php
include 'auth.php';
include 'db.php';

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT id, total, order_date, status 
                        FROM orders 
                        WHERE user_id=? 
                        ORDER BY order_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Bill History</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --medical-teal: #17a2b8;
            --bg-soft: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-soft);
            color: var(--text-dark);
            margin: 0;
        }

        /* Navigation Bar */
        .navbar {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .brand {
            text-decoration: none;
            color: var(--medical-teal);
            font-weight: 800;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-menu { display: flex; gap: 25px; }
        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            transition: 0.3s;
        }
        .nav-link.active { color: var(--medical-teal); }

        /* Content Container */
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .header-box {
            margin-bottom: 30px;
        }

        /* Modern Table Styling */
        .bill-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            border: 1px solid #f1f5f9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 15px 20px;
            border-bottom: 1px solid #edf2f7;
        }

        td {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }

        tr:last-child td { border-bottom: none; }

        .order-number { font-weight: 700; color: var(--medical-teal); }
        .date-text { color: var(--text-muted); font-size: 0.85rem; }
        .amount-text { font-weight: 800; color: var(--text-dark); }

        /* Status Badges */
        .badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .status-pending { background: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }
        .status-completed { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
        .status-accepted { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
        .status-rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }

        /* Action Buttons */
        .btn-group { display: flex; gap: 8px; }
        .btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: 0.2s;
        }
        .btn-view { background: #f1f5f9; color: var(--text-dark); border: 1px solid #e2e8f0; }
        .btn-print { background: var(--medical-teal); color: white; }
        
        .btn-view:hover { background: #e2e8f0; }
        .btn-print:hover { opacity: 0.9; }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="user_index.php" class="brand"><i class="fas fa-heartbeat"></i> MediCare</a>
        <div class="nav-menu">
            <a href="user_index.php" class="nav-link">Dashboard</a>
            <a href="products.php" class="nav-link">Pharmacy</a>
            <a href="orders.php" class="nav-link">My Orders</a>
            <a href="bill_history.php" class="nav-link active">Invoices</a>
        </div>
        <a href="cart.php" class="nav-link" style="color: var(--medical-teal); font-weight: 700;">
            <i class="fas fa-shopping-cart"></i> Cart
        </a>
    </div>
</nav>

<div class="container">
    <div class="header-box">
        <h1 style="margin:0; font-size: 1.8rem;">Billing & Invoices</h1>
        <p style="color: var(--text-muted); margin-top: 5px;">Download or print your medical purchase receipts.</p>
    </div>

    <div class="bill-card">
        <?php if ($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="order-number">ORD-<?php echo $row['id']; ?></td>
                    <td class="date-text">
                        <i class="far fa-calendar-alt"></i> 
                        <?php echo date("d M Y", strtotime($row['order_date'])); ?>
                    </td>
                    <td class="amount-text">₹<?php echo number_format($row['total'], 2); ?></td>
                    <td>
                        <span class="badge status-<?php echo strtolower($row['status']); ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="bill.php?order_id=<?php echo $row['id']; ?>" class="btn btn-view">
                                <i class="far fa-eye"></i> View
                            </a>
                            <a href="bill.php?order_id=<?php echo $row['id']; ?>&print=1" class="btn btn-print" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.2;"></i>
            <p>No billing records found.</p>
            <a href="products.php" style="color: var(--medical-teal); text-decoration: none; font-weight: 600;">Shop Medicines &rarr;</a>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>