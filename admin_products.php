<?php
include 'auth.php';
include 'db.php';

// Ensure only admins can access
if ($_SESSION['role'] !== 'admin') {
    header("Location: user_index.php");
    exit();
}

$result = $conn->query("SELECT * FROM products ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Manage Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --admin-dark: #1e293b;
            --medical-teal: #17a2b8;
            --bg-soft: #f8fafc;
            --danger: #ef4444;
            --warning: #f59e0b;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-soft); margin: 0; display: flex; }

        /* Sidebar Navigation */
        .sidebar { width: 260px; background: var(--admin-dark); height: 100vh; color: white; position: sticky; top: 0; padding: 20px; box-sizing: border-box; }
        .sidebar-brand { font-size: 1.5rem; font-weight: 800; color: var(--medical-teal); margin-bottom: 40px; display: block; text-decoration: none; }
        .nav-group { display: flex; flex-direction: column; gap: 8px; }
        .nav-item { text-decoration: none; color: #94a3b8; padding: 12px 15px; border-radius: 8px; display: flex; align-items: center; gap: 12px; font-weight: 500; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }

        /* Main Content */
        .main-content { flex-grow: 1; padding: 40px; overflow-x: hidden; }
        .inventory-card { background: white; border-radius: 16px; box-shadow: var(--card-shadow); padding: 25px; }

        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }

        .btn-add { background: var(--medical-teal); color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: 0.3s; }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; color: #64748b; font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; vertical-align: middle; }
        
        .img-container { width: 50px; height: 50px; background: #f8fafc; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .img-container img { max-width: 100%; max-height: 100%; object-fit: contain; }

        /* Status Badges */
        .badge { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-danger { background: #fee2e2; color: #b91c1c; } /* For Expired */
        .badge-warning { background: #fef3c7; color: #92400e; } /* For Low Stock */
        .badge-success { background: #dcfce7; color: #166534; } /* For Healthy */

        .btn-edit { color: var(--medical-teal); text-decoration: none; font-weight: 600; margin-right: 15px; }
        .btn-delete { color: var(--danger); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="admin_index.php" class="sidebar-brand"><i class="fas fa-heartbeat"></i> MediCare<span>+</span></a>
    <nav class="nav-group">
        <a href="admin_index.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="admin_products.php" class="nav-item active"><i class="fas fa-pills"></i> Inventory</a>
        <a href="orders_admin.php" class="nav-item"><i class="fas fa-shopping-cart"></i> Manage Orders</a>
        <a href="reports.php" class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Sales Reports</a>
        <a href="logout.php" class="nav-item" style="margin-top:auto; color:#f87171;"><i class="fas fa-power-off"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">
    <div class="header-actions">
        <div>
            <h1 style="margin:0;">Inventory Management</h1>
            <p style="color: #64748b; margin-top: 5px;">Maintain and update your medical stock catalogue.</p>
        </div>
        <a href="admin_add_product.php" class="btn-add"><i class="fas fa-plus"></i> Add New Medicine</a>
    </div>

    <div class="inventory-card">
        <div class="header-actions" style="margin-bottom: 20px;">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="invSearch" placeholder="Search by name or status (e.g. 'expired')..." onkeyup="filterInventory()">
            </div>
        </div>

        <table id="inventoryTable">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Manufacturer</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): 
                    $image_src = $row['image'];
                    if (!filter_var($image_src, FILTER_VALIDATE_URL)) {
                        $image_src = "assets/images/" . $image_src;
                    }
                    
                    // STATUS LOGIC
                    $expiry_timestamp = strtotime($row['expiry_date']);
                    $is_expired = ($expiry_timestamp < time());
                    $is_low = ($row['stock'] <= 5);
                ?>
                    <tr class="inv-row">
                        <td>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="img-container">
                                    <img src="<?php echo $image_src; ?>" alt="Product">
                                </div>
                                <div>
                                    <div style="font-weight: 700;"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">Batch: <?php echo htmlspecialchars($row['batch_no']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color: #64748b;"><?php echo htmlspecialchars($row['manufacturer']); ?></td>
                        <td style="font-weight: 600;"><?php echo $row['stock']; ?></td>
                        <td style="font-weight: 700;">₹<?php echo number_format($row['price'], 2); ?></td>
                        <td style="font-size: 0.85rem;"><?php echo date("d-m-Y", $expiry_timestamp); ?></td>
                        <td>
                            <?php if ($is_expired): ?>
                                <span class="badge badge-danger">Expired</span>
                            <?php elseif ($is_low): ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge badge-success">Healthy</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="admin_edit_product.php?id=<?php echo $row['id']; ?>" class="btn-edit" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="admin_delete_product.php?id=<?php echo $row['id']; ?>" 
                               class="btn-delete" 
                               onclick="return confirm('Delete this product?');" title="Delete"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function filterInventory() {
    let input = document.getElementById('invSearch').value.toLowerCase();
    let rows = document.querySelectorAll('.inv-row');
    
    rows.forEach(row => {
        // Search in the Name column (1st col)
        let name = row.querySelector('td:nth-child(1)').innerText.toLowerCase();
        // Search in the Status column (6th col)
        let status = row.querySelector('td:nth-child(6)').innerText.toLowerCase();
        
        // Show row if keyword matches name OR status
        if (name.includes(input) || status.includes(input)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>

</body>
</html>