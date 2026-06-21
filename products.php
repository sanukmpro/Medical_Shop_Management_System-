<?php
include 'auth.php';
include 'db.php';

// Fetch items that are in stock and NOT expired
$current_date = date('Y-m-d');
$query = "SELECT * FROM products WHERE stock > 0 AND expiry_date > '$current_date'";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Online Pharmacy</title>
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

        /* Navbar & Grid Layout */
        .navbar { background: white; box-shadow: 0 2px 15px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 1000; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; }
        .brand { text-decoration: none; color: var(--medical-teal); font-weight: 800; font-size: 1.5rem; display: flex; align-items: center; gap: 10px; }
        .nav-menu { display: flex; gap: 25px; }
        .nav-link { text-decoration: none; color: var(--text-muted); font-size: 0.9rem; font-weight: 600; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--medical-teal); }
        .cart-pill { background: var(--medical-teal); color: white; padding: 8px 18px; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 8px; }

        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .shop-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; }
        
        /* Product Grid */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; }
        .med-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; transition: transform 0.3s; display: flex; flex-direction: column; }
        .med-card:hover { transform: translateY(-5px); }

        /* IMAGE SECTION FIX */
        .image-wrapper {
            height: 200px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            overflow: hidden;
        }
        .image-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain; /* Prevents stretching */
        }
        .fallback-icon { font-size: 4rem; color: #e2e8f0; }

        .med-content { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .tag { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 4px 10px; border-radius: 50px; margin-bottom: 10px; display: inline-block; }
        .tag-stock { background: #dcfce7; color: #15803d; }
        .tag-low { background: #fee2e2; color: #b91c1c; }

        .med-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 5px 0; min-height: 2.2em; }
        .med-price { font-size: 1.4rem; color: var(--medical-teal); font-weight: 800; margin-bottom: 15px; }
        
        .qty-picker { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; }
        .qty-picker input { width: 50px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center; }

        .card-actions { display: grid; grid-template-columns: 1fr 2fr; gap: 8px; }
        .btn { padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; text-align: center; text-decoration: none; transition: 0.2s; }
        .btn-info { background: #f1f5f9; color: var(--text-muted); }
        .btn-add { background: var(--medical-teal); color: white; }
        .btn-add:hover { background: #138496; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="user_index.php" class="brand"><i class="fas fa-heartbeat"></i> MediCare</a>
        <div class="nav-menu">
            <a href="user_index.php" class="nav-link">Dashboard</a>
            <a href="products.php" class="nav-link active">Pharmacy</a>
            <a href="orders.php" class="nav-link">My Orders</a>
            <a href="bill_history.php" class="nav-link">Invoices</a>
        </div>
        <a href="cart.php" class="cart-pill"><i class="fas fa-shopping-cart"></i> Cart</a>
    </div>
</nav>

<div class="container">
    <div class="shop-header">
        <div>
            <h1>Medical Supplies</h1>
            <p style="color: var(--text-muted);">Genuine medicines verified by our medical staff.</p>
        </div>
        <div style="position: relative; width: 300px;">
            <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: #94a3b8;"></i>
            <input type="text" id="medSearch" placeholder="Search medicines..." onkeyup="filterMedicines()" style="width: 100%; padding: 10px 10px 10px 35px; border-radius: 8px; border: 1px solid #e2e8f0; outline: none;">
        </div>
    </div>

    <div class="product-grid" id="productGrid">
        <?php while($row = $result->fetch_assoc()): 
            $max_stock = $row['stock'];
            
            // --- IMAGE LOGIC START ---
            $raw_img = $row['image'];
            $final_img_url = "";

            if (!empty($raw_img)) {
                if (filter_var($raw_img, FILTER_VALIDATE_URL)) {
                    // It's a full URL (e.g., http://example.com/pill.jpg)
                    $final_img_url = $raw_img;
                } else {
                    // It's a local filename (e.g., pill.jpg)
                    $final_img_url = "assets/images/" . $raw_img;
                }
            }
            // --- IMAGE LOGIC END ---
        ?>
            <div class="med-card" data-name="<?php echo strtolower($row['name']); ?>">
                <div class="image-wrapper">
                    <?php if (!empty($final_img_url)): ?>
                        <img src="<?php echo $final_img_url; ?>" alt="Medicine" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <i class="fas fa-prescription-bottle-alt fallback-icon" style="display:none;"></i>
                    <?php else: ?>
                        <i class="fas fa-prescription-bottle-alt fallback-icon"></i>
                    <?php endif; ?>
                </div>
                
                <div class="med-content">
                    <div>
                        <?php if ($max_stock <= 5): ?>
                            <span class="tag tag-low">Only <?php echo $max_stock; ?> Left</span>
                        <?php else: ?>
                            <span class="tag tag-stock">In Stock</span>
                        <?php endif; ?>
                    </div>

                    <h3 class="med-title"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <div class="med-price">₹<?php echo number_format($row['price'], 2); ?></div>
                    
                    <div class="qty-picker">
                        <span>Quantity:</span>
                        <input type="number" id="qty_<?php echo $row['id']; ?>" value="1" min="1" max="<?php echo $max_stock; ?>">
                    </div>

                    <div class="card-actions">
                        <a href="product_details.php?id=<?php echo $row['id']; ?>" class="btn btn-info">Info</a>
                        <button class="btn btn-add" onclick="addToCart(<?php echo $row['id']; ?>, <?php echo $max_stock; ?>)">
                            <i class="fas fa-cart-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
function filterMedicines() {
    let input = document.getElementById('medSearch').value.toLowerCase();
    let cards = document.getElementsByClassName('med-card');
    for (let card of cards) {
        let name = card.getAttribute('data-name');
        card.style.display = name.includes(input) ? "flex" : "none";
    }
}

function addToCart(pid, max) {
    let q = document.getElementById('qty_'+pid).value;
    if (parseInt(q) > max) { alert("Insufficient stock!"); return; }
    window.location.href = "cart.php?add=" + pid + "&qty=" + q;
}
</script>

</body>
</html>