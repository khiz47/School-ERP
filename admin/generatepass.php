<?php
//Instead of manually inserting SQL, run this once to create the super admin account:
require 'includes/conn.php';

$email = "box11players@gmail.com";
$password = "#Development@123";
$username = "superadmin";
$phone = 9999999999;
$status = "active";

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (role_id, branch_id, username, email, password, phone, status) VALUES (?, ?, ?, ?, ?, ?, ?)");

$role_id   = 1; // Admin role
$branch_id = 1; // Default branch

$stmt->bind_param("iisssss", $role_id, $branch_id, $username, $email, $hash, $phone, $status);


if ($stmt->execute()) {
    echo "✅ Admin inserted successfully!";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
