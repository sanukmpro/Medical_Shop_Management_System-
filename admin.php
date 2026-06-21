<?php
include 'auth.php';
include 'db.php';

if ($_SESSION['role'] != 'admin') {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['desc'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $batch = $_POST['batch'];
    $expiry = $_POST['expiry'];
    $manu = $_POST['manufacturer'];

    $stmt = $conn->prepare("INSERT INTO products (name,description,price,stock,batch_no,expiry_date,manufacturer) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("ssdiiss", $name,$desc,$price,$stock,$batch,$expiry,$manu);
    $stmt->execute();
}
?>
<div class="container">
  <h2>Admin Dashboard</h2>
  <form method="POST">
    <input type="text" name="name" placeholder="Medicine Name" required>
    <input type="text" name="desc" placeholder="Description">
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <input type="number" name="stock" placeholder="Stock" required>
    <input type="text" name="batch" placeholder="Batch No">
    <input type="date" name="expiry" placeholder="Expiry Date">
    <input type="text" name="manufacturer" placeholder="Manufacturer">
    <button type="submit" class="btn">Add Medicine</button>
  </form>
</div>
