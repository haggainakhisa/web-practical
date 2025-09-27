// --- CONFIG: db_connect.php ---
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "wifi_billing";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>


// --- DATABASE: wifi_billing.sql (run in phpMyAdmin) ---
/*
CREATE DATABASE wifi_billing;
USE wifi_billing;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone VARCHAR(15) UNIQUE,
  username VARCHAR(50),
  password VARCHAR(255),
  package VARCHAR(20),
  expiry DATETIME,
  used_data INT DEFAULT 0,
  balance DECIMAL(10,2) DEFAULT 0.00
);

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  amount DECIMAL(10,2),
  mpesa_code VARCHAR(50),
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
*/


// --- login.html ---
<!DOCTYPE html>
<html>
<head>
  <title>Kina Apartment WiFi Login</title>
  <style>
    body { font-family: Arial; background: #e9f0f7; text-align: center; padding-top: 80px; }
    form { background: white; display: inline-block; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
    input, button { padding: 10px; margin: 10px; width: 90%; }
    h2 { color: #333; }
  </style>
</head>
<body>
  <form action="login.php" method="POST">
    <h2>Welcome to Kina Apartment WiFi</h2>
    <input type="text" name="phone" placeholder="Phone Number" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
  </form>
</body>
</html>


// --- login.php ---
<?php
include 'db_connect.php';
$phone = $_POST['phone'];
$pass = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE phone=?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($pass, $user['password'])) {
    $now = new DateTime();
    $expiry = new DateTime($user['expiry']);
    if ($expiry < $now) {
        echo "<h2>Your subscription expired. Please renew.</h2>";
    } else {
        echo "<h2>Welcome, {$user['username']}</h2><p>Package: {$user['package']} | Expires: {$user['expiry']}</p>";
    }
} else {
    echo "<h3>Invalid credentials.</h3>";
}
?>


// --- register_user.php ---
<?php
include 'db_connect.php';
$phone = $_POST['phone'];
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$package = $_POST['package'];

$hours_map = [
  '2hrs'=>2, '5hrs'=>5, '12hrs'=>12, '24hrs'=>24, '1week'=>168, '1month'=>720
];
$hours = $hours_map[$package];

$expiry = (new DateTime())->modify("+{$hours} hours")->format('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO users (phone, username, password, package, expiry) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $phone, $username, $password, $package, $expiry);
if ($stmt->execute()) echo "User registered for {$package}."; else echo "Error registering user.";
?>


// --- dashboard.php ---
<?php
include 'db_connect.php';
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html>
<head>
<title>Kina WiFi Admin</title>
<style>
  body { font-family: Arial; background: #f9f9f9; padding: 20px; }
  table { border-collapse: collapse; width: 100%; background: white; }
  th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
  th { background: #0077cc; color: white; }
</style>
</head>
<body>
<h2>Kina WiFi Users Dashboard</h2>
<table>
<tr><th>ID</th><th>Username</th><th>Phone</th><th>Package</th><th>Expiry</th><th>Data Used</th><th>Balance</th></tr>
<?php while($row = $result->fetch_assoc()) { ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['username'] ?></td>
<td><?= $row['phone'] ?></td>
<td><?= $row['package'] ?></td>
<td><?= $row['expiry'] ?></td>
<td><?= $row['used_data'] ?> MB</td>
<td>KES <?= $row['balance'] ?></td>
</tr>
<?php } ?>
</table>
</body>
</html>
