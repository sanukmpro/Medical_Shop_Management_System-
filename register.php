<?php
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
    $role = "customer"; // default role
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        header("Location: login.php?registered=1");
        exit();
    } else {
        $error = "Registration failed! This email may already be in our system.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Create Staff Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --medical-teal: #17a2b8;
            --soft-bg: #f4f7f9;
            --text-dark: #334155;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .register-card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            margin: 20px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            color: var(--medical-teal);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        h2 {
            color: var(--text-dark);
            margin: 0;
            font-weight: 600;
        }

        .subtitle {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
            transition: all 0.2s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--medical-teal);
            box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
        }

        .btn-register {
            width: 100%;
            padding: 13px;
            background: var(--medical-teal);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }

        .btn-register:hover {
            background: #138496;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            text-align: center;
        }

        .footer-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #64748b;
        }

        .footer-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="header-section">
        <div class="brand-logo"><i class="fas fa-prescription-bottle-alt"></i></div>
        <h2>Create Account</h2>
        <p class="subtitle">Join the MediCare Pharmacy Network</p>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="e.g. Dr. John Doe" required>
        </div>

        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="name@pharmacy.com" required>
        </div>

        <div class="input-group">
            <label>Secure Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-register">Register Account</button>
    </form>

    <div class="footer-link">
        Already have an account? <a href="login.php">Log In</a>
    </div>
</div>

</body>
</html>