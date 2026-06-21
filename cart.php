<?php
include 'auth.php';
include 'db.php';

$user_id = intval($_SESSION['user_id']);

// --- LOGIC: Add/Update Item ---
if (isset($_GET['add'])) {
    $pid = intval($_GET['add']);
    $requested_qty = isset($_GET['qty']) ? intval($_GET['qty']) : 1;
    if ($requested_qty < 1) $requested_qty = 1;

    $check = $conn->prepare("SELECT expiry_date, stock FROM products WHERE id=?");
    $check->bind_param("i", $pid);
    $check->execute();
    $prod = $check->get_result()->fetch_assoc();

    if (!$prod || strtotime($prod['expiry_date']) < time()) {
        header("Location: products.php?error=Medicine unavailable or expired");
        exit();
    }

    $cart_check = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $cart_check->bind_param("ii", $user_id, $pid);
    $cart_check->execute();
    $existing = $cart_check->get_result()->fetch_assoc();

    if ($existing) {
        $new_qty = min($existing['quantity'] + $requested_qty, $prod['stock']);
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_qty, $existing['id']);
    } else {
        $final_qty = min($requested_qty, $prod['stock']);
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $pid, $final_qty);
    }
    $stmt->execute();
    header("Location: cart.php"); 
    exit();
}

// --- LOGIC: Remove/Clear ---
if (isset($_GET['remove'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $_GET['remove'], $user_id);
    $stmt->execute();
    header("Location: cart.php");
    exit();
}

if (isset($_GET['clear'])) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: cart.php");
    exit();
}

// --- FETCH DISPLAY DATA ---
$stmt = $conn->prepare("SELECT c.id, p.name, p.price, c.quantity, p.expiry_date, p.image 
                        FROM cart c JOIN products p ON c.product_id=p.id 
                        WHERE c.user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$cart_items = [];
while($row = $result->fetch_assoc()) {
    if (strtotime($row['expiry_date']) < time()) {
        $conn->query("DELETE FROM cart WHERE id=".$row['id']);
        continue;
    }
    $cart_items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Your Shopping Cart</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --medical-teal: #17a2b8;
            --medical-dark: #138496;
            --bg-soft: #f4f7f6;
            --text-dark: #2d3436;
            --text-muted: #636e72;
            --danger: #d63031;
            --success: #00b894;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-soft); color: var(--text-dark); margin: 0; line-height: 1.6; }

        /* Navbar Styling */
        .navbar { background: white; padding: 1rem 0; box-shadow: 0 2px 20px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 1000; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .brand { text-decoration: none; color: var(--medical-teal); font-weight: 800; font-size: 1.6rem; display: flex; align-items: center; gap: 12px; }
        .nav-menu { display: flex; gap: 30px; }
        .nav-link { text-decoration: none; color: var(--text-muted); font-size: 0.95rem; font-weight: 600; transition: color 0.2s; }
        .nav-link:hover { color: var(--medical-teal); }
        .cart-indicator { background: #e8f7f9; color: var(--medical-teal); padding: 8px 16px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; }

        /* Main Container */
        .main-wrapper { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .cart-layout { display: flex; gap: 30px; align-items: flex-start; }

        /* Left Side: Cart Items */
        .cart-main { flex: 1; background: white; border-radius: 20px; box-shadow: var(--card-shadow); padding: 35px; }
        .cart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .cart-header h2 { font-weight: 800; font-size: 1.8rem; margin: 0; }
        .btn-clear-all { color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border: 1px solid #dfe6e9; border-radius: 8px; transition: all 0.2s; }
        .btn-clear-all:hover { background: #fff5f5; color: var(--danger); border-color: #fab1a0; }

        /* Table Design */
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th { text-align: left; color: #b2bec3; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding-bottom: 20px; border-bottom: 1px solid #f1f2f6; }
        .cart-item td { padding: 25px 0; border-bottom: 1px solid #f1f2f6; vertical-align: middle; }
        
        .product-cell { display: flex; align-items: center; gap: 20px; }
        .product-img { width: 70px; height: 70px; background: #f9f9f9; border-radius: 12px; object-fit: contain; padding: 5px; border: 1px solid #f1f2f6; }
        .product-details p { margin: 0; font-weight: 700; font-size: 1rem; }
        .product-details span { font-size: 0.8rem; color: var(--text-muted); }

        .qty-box { background: #f4f7f6; padding: 6px 14px; border-radius: 10px; font-weight: 800; color: var(--medical-teal); font-size: 0.9rem; }
        .price-text { font-weight: 700; color: var(--text-dark); }
        .subtotal-text { font-weight: 800; color: var(--text-dark); font-size: 1.05rem; }

        .btn-delete-item { color: #d1d8e0; font-size: 1.2rem; transition: color 0.2s; }
        .btn-delete-item:hover { color: var(--danger); }

        /* Right Side: Sidebar */
        .cart-sidebar { width: 380px; position: sticky; top: 120px; }
        .summary-card { background: white; border-radius: 20px; box-shadow: var(--card-shadow); padding: 30px; }
        .summary-title { font-weight: 800; font-size: 1.3rem; margin-bottom: 25px; border-bottom: 2px solid #f4f7f6; padding-bottom: 15px; }
        .summary-line { display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: 600; color: var(--text-muted); }
        .summary-total { margin-top: 20px; padding-top: 20px; border-top: 2px dashed #f4f7f6; display: flex; justify-content: space-between; font-weight: 800; font-size: 1.5rem; color: var(--medical-teal); }

        .btn-checkout-primary { width: 100%; background: var(--medical-teal); color: white; border: none; padding: 18px; border-radius: 15px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-top: 25px; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; justify-content: center; align-items: center; gap: 10px; box-shadow: 0 10px 20px rgba(23, 162, 184, 0.2); }
        .btn-checkout-primary:hover { background: var(--medical-dark); transform: translateY(-3px); box-shadow: 0 15px 25px rgba(23, 162, 184, 0.3); }
        .btn-checkout-primary:disabled { background: #b2bec3; transform: none; box-shadow: none; cursor: not-allowed; }

        /* Empty & Success States */
        .centered-state { text-align: center; background: white; border-radius: 25px; padding: 80px 40px; box-shadow: var(--card-shadow); width: 100%; max-width: 600px; margin: 60px auto; }
        .centered-state i { font-size: 5rem; margin-bottom: 30px; display: block; }
        .btn-back-shop { display: inline-block; background: var(--medical-teal); color: white; text-decoration: none; padding: 14px 35px; border-radius: 12px; font-weight: 700; margin-top: 25px; transition: 0.2s; }
        .btn-back-shop:hover { background: var(--medical-dark); }

        @media (max-width: 992px) {
            .cart-layout { flex-direction: column; }
            .cart-sidebar { width: 100%; position: static; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="user_index.php" class="brand">
            <i class="fas fa-prescription-bottle-alt"></i> MediCare
        </a>
        <div class="nav-menu">
            <a href="user_index.php" class="nav-link">Dashboard</a>
            <a href="products.php" class="nav-link">Pharmacy</a>
            <a href="orders.php" class="nav-link">My Orders</a>
        </div>
        <div class="cart-indicator">
            <i class="fas fa-shopping-bag"></i> <span id="cart-count"><?php echo count($cart_items); ?></span> items
        </div>
    </div>
</nav>

<div class="main-wrapper" id="cart-wrapper">
    <?php if (count($cart_items) > 0): ?>
        <div class="cart-layout" id="cart-layout-view">
            <div class="cart-main">
                <div class="cart-header">
                    <h2>Cart Review</h2>
                    <a href="cart.php?clear=1" class="btn-clear-all" onclick="return confirm('Remove everything from your cart?')">
                        <i class="fas fa-eraser"></i> Clear All
                    </a>
                </div>

                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product Details</th>
                            <th>Unit Price</th>
                            <th style="text-align: center;">Quantity</th>
                            <th style="text-align: right;">Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): 
                            $img_path = $item['image'];
                            $img_display = (empty($img_path)) ? "https://cdn-icons-png.flaticon.com/512/883/883356.png" : (filter_var($img_path, FILTER_VALIDATE_URL) ? $img_path : "assets/images/" . $img_path);
                        ?>
                        <tr class="cart-item">
                            <td>
                                <div class="product-cell">
                                    <img src="<?php echo $img_display; ?>" class="product-img" alt="medicine">
                                    <div class="product-details">
                                        <p><?php echo htmlspecialchars($item['name']); ?></p>
                                        <span>Exp: <?php echo date('M Y', strtotime($item['expiry_date'])); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="price-text">₹<?php echo number_format($item['price'], 2); ?></td>
                            <td style="text-align: center;"><span class="qty-box"><?php echo $item['quantity']; ?></span></td>
                            <td class="subtotal-text" style="text-align: right;">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td style="text-align: right; padding-left: 20px;">
                                <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn-delete-item" title="Remove">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-sidebar">
                <div class="summary-card">
                    <div class="summary-title">Payment Summary</div>
                    <div class="summary-line">
                        <span>Cart Subtotal</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-line">
                        <span>Delivery Fee</span>
                        <span style="color: var(--success);">Free</span>
                    </div>
                    <div class="summary-total">
                        <span>Order Total</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <button id="checkout-btn" onclick="processCheckout()" class="btn-checkout-primary">
                        Checkout Now <i class="fas fa-arrow-right"></i>
                    </button>
                    
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.75rem; margin-top: 20px;">
                        <i class="fas fa-shield-halved"></i> All orders are verified by pharmacists.
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="centered-state">
            <i class="fas fa-cart-plus" style="color: #dfe6e9;"></i>
            <h2 style="font-weight: 800; font-size: 2rem; margin-bottom: 10px;">Your cart is empty</h2>
            <p style="color: var(--text-muted);">Please add some medicines to your cart to proceed with the checkout.</p>
            <a href="products.php" class="btn-back-shop">Browse Pharmacy</a>
        </div>
    <?php endif; ?>
</div>

<script>
function processCheckout() {
    const btn = document.getElementById('checkout-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Securing Order...';

    fetch('process_checkout.php', { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cart-wrapper').innerHTML = `
                <div class="centered-state">
                    <i class="fas fa-check-double" style="color: var(--success);"></i>
                    <h2 style="font-weight: 800; font-size: 2.2rem; margin-bottom: 15px; color: var(--text-dark);">Order Confirmed!</h2>
                    <p style="color: var(--text-muted); font-size: 1.1rem;">Thank you! Your order <strong>#${data.order_id}</strong> has been received and is currently being processed by our warehouse.</p>
                    <div style="margin-top: 40px; display: flex; gap: 15px; justify-content: center;">
                        <a href="products.php" class="btn-back-shop">Keep Shopping</a>
                        <a href="orders.php" class="btn-back-shop" style="background: #2d3436;">My Orders</a>
                    </div>
                </div>
            `;
            document.getElementById('cart-count').innerText = "0";
        } else {
            alert("Checkout failed: " + data.message);
            btn.disabled = false;
            btn.innerHTML = 'Checkout Now <i class="fas fa-arrow-right"></i>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Server error. Please verify process_checkout.php exists.");
        btn.disabled = false;
        btn.innerHTML = 'Checkout Now <i class="fas fa-arrow-right"></i>';
    });
}
</script>

</body>
</html>