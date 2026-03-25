<?php
include 'config.php';

// Data user admin
$nama = 'Wilson Gurning';
$email = 'sijambur@gmail.com';
$username = 'Keyuser1';
$password = 'pass123'; // Password plain text
$hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash password
$role = 'admin';
$wh_id = 'WH01';
$project = 'ASSETS001';

// Query untuk menambahkan user admin
$query = "INSERT INTO data_username (nama, email, username, password, role, wh_id, project)
          VALUES ('$nama', '$email', '$username', '$hashed_password', '$role', '$wh_id', '$project')";

if ($conn->query($query) === TRUE) {
    echo "User berhasil ditambahkan!";
} else {
    echo "Error: " . $query . "<br>" . $conn->error;
}

$conn->close();
?>