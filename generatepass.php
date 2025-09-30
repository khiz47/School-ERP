<?php
require 'includes/conn.php';

// Student account details (you can adjust as needed)
// $fullname = "Super Admin";
// $email    = "admin@school.com";
// $password = "Messedup@123";
// $username = "admin";
// $phone    = "9999999999";
// $status   = "active";
$fullname = "John Doe";
$email    = "parent1@example.com";
$password = "Khi@_2123";
$username = "parent1";
$phone    = "8876599456";
$status   = "active";

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("
    INSERT INTO users (fullname, role_id, branch_id, username, email, password, phone, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$role_id   = 4; // Student role (can be adjusted as needed)
$branch_id = 1; // Default branch

$stmt->bind_param("siisssss", $fullname, $role_id, $branch_id, $username, $email, $hash, $phone, $status);

if ($stmt->execute()) {
    echo "✅ Account inserted successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
