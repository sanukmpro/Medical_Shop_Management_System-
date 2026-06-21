<?php
include 'auth.php';
include 'db.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: user_index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $batch = $_POST['batch_no'];
    $expiry = $_POST['expiry_date'];
    $manufacturer = $_POST['manufacturer'];

    // --- IMAGE LOGIC ---
    $image = "placeholder.png"; 

    if (!empty($_POST['image_url'])) {
        $image = $_POST['image_url'];
    } elseif (!empty($_FILES['image_file']['name'])) {
        $target_dir = "assets/images/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES["image_file"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_file)) {
            $image = $file_name;
        }
    }

    $stmt = $conn->prepare("INSERT INTO products 
        (name, description, price, stock, batch_no, expiry_date, manufacturer, image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiisss", $name, $description, $price, $stock, $batch, $expiry, $manufacturer, $image);
    $stmt->execute();

    header("Location: admin_products.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Add New Medicine</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --admin-dark: #1e293b;
            --medical-teal: #17a2b8;
            --bg-soft: #f8fafc;
            --card-shadow: 0 4px 20px rgba(0,0,0,0.05);
            --border-color: #e2e8f0;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-soft); margin: 0; display: flex; }

        /* Sidebar Navigation (Consistent with Dashboard) */
        .sidebar { width: 260px; background: var(--admin-dark); height: 100vh; color: white; position: sticky; top: 0; padding: 20px; box-sizing: border-box; }
        .sidebar-brand { font-size: 1.5rem; font-weight: 800; color: var(--medical-teal); margin-bottom: 40px; display: block; text-decoration: none; }
        .nav-group { display: flex; flex-direction: column; gap: 8px; }
        .nav-item { text-decoration: none; color: #94a3b8; padding: 12px 15px; border-radius: 8px; display: flex; align-items: center; gap: 12px; font-weight: 500; transition: 0.3s; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }

        /* Main Content Area */
        .main-content { flex-grow: 1; padding: 40px; }
        .form-card { background: white; border-radius: 16px; box-shadow: var(--card-shadow); padding: 40px; max-width: 800px; margin: 0 auto; }

        .form-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px; }
        .form-header h2 { margin: 0; color: var(--admin-dark); }

        /* Form Inputs */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
        input[type="text"], input[type="number"], input[type="date"], textarea {
            width: 100%; padding: 12px; border: 1.5px solid var(--border-color); border-radius: 10px; box-sizing: border-box; font-family: inherit; font-size: 0.9rem; transition: 0.2s;
        }
        input:focus, textarea:focus { outline: none; border-color: var(--medical-teal); box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1); }

        /* Image Selection Box */
        .image-box { background: #f8fafc; padding: 25px; border: 2px dashed #cbd5e1; border-radius: 12px; margin: 25px 0; }
        .image-box h4 { margin: 0 0 15px 0; color: var(--medical-teal); font-size: 0.9rem; }
        .divider { display: flex; align-items: center; text-align: center; margin: 15px 0; color: #94a3b8; font-size: 0.75rem; font-weight: 800; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid #e2e8f0; }
        .divider:not(:empty)::before { margin-right: 15px; }
        .divider:not(:empty)::after { margin-left: 15px; }

        .flex-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* Buttons */
        .btn-group { display: flex; gap: 15px; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 25px; }
        .btn { padding: 14px 28px; border-radius: 10px; font-weight: 700; cursor: pointer; border: none; font-size: 0.95rem; text-decoration: none; transition: 0.3s; }
        .btn-submit { background: var(--medical-teal); color: white; }
        .btn-submit:hover { background: #138496; transform: translateY(-2px); }
        .btn-cancel { background: #f1f5f9; color: #64748b; }
        .btn-cancel:hover { background: #e2e8f0; }
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
    <div class="form-card">
        <div class="form-header">
            <h2><i class="fas fa-plus-circle" style="color: var(--medical-teal);"></i> Add New Medicine</h2>
            <p style="color: #64748b; font-size: 0.9rem; margin: 5px 0 0 0;">Enter the details of the new stock to be added to the catalog.</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Medicine Name</label>
                <input type="text" name="name" placeholder="e.g. Paracetamol 500mg" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Enter dosage, composition, or medical use details..." required></textarea>
            </div>

            <div class="image-box">
                <h4><i class="fas fa-image"></i> Product Image Selection</h4>
                <div class="form-group">
                    <label style="font-weight: 500;">Option A: Paste Internet Image URL</label>
                    <input type="text" name="image_url" placeholder="https://example.com/medicine.jpg">
                </div>
                
                <div class="divider">OR</div>
                
                <div class="form-group">
                    <label style="font-weight: 500;">Option B: Upload Local File</label>
                    <input type="file" name="image_file" accept="image/*" style="border:none; padding-left:0;">
                </div>
            </div>

            <div class="flex-row">
                <div class="form-group">
                    <label>Price per Unit (₹)</label>
                    <input type="number" step="0.01" name="price" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" placeholder="e.g. 100" required>
                </div>
            </div>

            <div class="flex-row">
                <div class="form-group">
                    <label>Batch Number</label>
                    <input type="text" name="batch_no" placeholder="e.g. BTCH-9021" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="date" name="expiry_date" required>
                </div>
            </div>

            <div class="form-group">
                <label>Manufacturer</label>
                <input type="text" name="manufacturer" placeholder="e.g. Cipla Ltd. / GSK" required>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-submit">Save Product</button>
                <a href="admin_products.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</main>

</body>
</html>