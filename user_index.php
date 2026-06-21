<?php
include 'auth.php';
include 'db.php';

if ($_SESSION['role'] !== 'customer') {
    header("Location: admin_index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Fetch user details
$stmt = $conn->prepare("SELECT name FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Patient Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --medical-teal: #17a2b8;
            --primary-blue: #007bff;
            --bg-soft: #f4f7f9;
            --text-main: #334155;
            --card-shadow: 0 10px 25px rgba(0,0,0,0.03);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-soft);
            color: var(--text-main);
            margin: 0;
            padding: 0;
        }

        .navbar {
            background: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--medical-teal);
            font-weight: 700;
            font-size: 1.4rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar-circle {
            width: 35px;
            height: 35px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--medical-teal);
        }

        .hero-section {
            background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::after {
            content: "\f471"; /* FontAwesome Stethoscope */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 15rem;
            opacity: 0.1;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
        }

        .menu-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
            box-shadow: var(--card-shadow);
        }

        .menu-card:hover {
            transform: translateY(-8px);
            border-color: var(--medical-teal);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }

        .icon-box {
            width: 50px;
            height: 50px;
            background: rgba(23, 162, 184, 0.1);
            color: var(--medical-teal);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .menu-card h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #1e293b;
        }

        .menu-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.5;
        }

        .logout-card {
            background: #fffafa;
        }
        .logout-card .icon-box { background: #fee2e2; color: #ef4444; }
        .logout-card:hover { border-color: #fca5a5; }

        .badge-verified {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; }
            .hero-section { padding: 25px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="#" class="brand">
        <i class="fas fa-heartbeat"></i> MediCare
    </a>
    <div class="user-profile">
        <span style="font-size: 0.9rem; font-weight: 500; color: #64748b;">Patient Portal</span>
        <div class="avatar-circle">
            <i class="fas fa-user"></i>
        </div>
    </div>
</nav>

<div class="container">
    
    <div class="hero-section">
        <span class="badge-verified"><i class="fas fa-check-circle"></i> Verified Account</span>
        <h1 style="margin: 15px 0 5px 0;">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <p style="margin: 0; opacity: 0.9;">Access your prescriptions, history, and medical records from your central dashboard.</p>
    </div>

    <div class="menu-grid">
        <a href="products.php" class="menu-card">
            <div class="icon-box"><i class="fas fa-pills"></i></div>
            <h3>Browse Medicines</h3>
            <p>Explore our inventory, check availability, and order wellness products.</p>
        </a>

        <a href="cart.php" class="menu-card">
            <div class="icon-box"><i class="fas fa-shopping-cart"></i></div>
            <h3>Shopping Cart</h3>
            <p>View items you've selected for purchase. Review totals and checkout.</p>
        </a>

        <a href="orders.php" class="menu-card">
            <div class="icon-box"><i class="fas fa-box-open"></i></div>
            <h3>Track Orders</h3>
            <p>Check the status of your current deliveries and past purchase history.</p>
        </a>

        <a href="bill_history.php" class="menu-card">
            <div class="icon-box"><i class="fas fa-file-invoice-dollar"></i></div>
            <h3>Billing & Invoices</h3>
            <p>Download digital receipts and view detailed billing records for insurance.</p>
        </a>

        <a href="logout.php" class="menu-card logout-card">
            <div class="icon-box"><i class="fas fa-sign-out-alt"></i></div>
            <h3>Sign Out</h3>
            <p>Safely end your current session and protect your medical data.</p>
        </a>
    </div>

    <footer style="text-align: center; margin-top: 60px; padding-bottom: 40px; color: #94a3b8; font-size: 0.85rem;">
        &copy; <?php echo date('Y'); ?> MediCare+ Pharmacy Systems. <br> 
        <small>Ensuring your health with secure digital management.</small>
    </footer>
</div>

</body>
</html>