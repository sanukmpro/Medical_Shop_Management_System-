<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_index.php");
        } else {
            header("Location: user_index.php");
        }
        exit();
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare | Pharmacy Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #007bff;
            --medical-teal: #17a2b8;
            --soft-bg: #f4f7f9;
            --text-dark: #334155;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--soft-bg);
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .login-card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            text-align: center;
        }

        .brand-logo {
            color: var(--medical-teal);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        h2 {
            color: var(--text-dark);
            margin-bottom: 8px;
            font-weight: 600;
        }

        p.subtitle {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .input-group {
            text-align: left;
            margin-bottom: 20px;
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
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--medical-teal);
            box-shadow: 0 0 0 3px rgba(23, 162, 184, 0.1);
        }

        .btn-login {
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
        }

        .btn-login:hover {
            background: #138496;
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

        .footer-links {
            margin-top: 25px;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">
        <i class="fas fa-heartbeat"></i>
    </div>
    <h2>MediCare Portal</h2>
    <p class="subtitle">Pharmacy Management System</p>

    <?php if(isset($_GET['registered'])): ?>
        <div class="alert alert-success">Account created! Please sign in.</div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="name@pharmacy.com" required>
        </div>
        
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div class="footer-links">
        New to the system? <a href="register.php">Create Account</a>
    </div>
</div>

</body>
</html>