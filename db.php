<?php
include 'db.php'; // include your database connection

// Pre-set users
$users = [
    ['username' => 'Admin1', 'password' => 'adminpass', 'role' => 'admin'],
    ['username' => 'Admin2', 'password' => 'adminpass', 'role' => 'admin'],
    ['username' => 'Guard1', 'password' => 'guardpass', 'role' => 'security'],
    ['username' => 'Guard2', 'password' => 'guardpass', 'role' => 'security']
];

foreach ($users as $user) {
    // Hash the password
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    
    // Prepare SQL to prevent injection
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user['username'], $hashed_password, $user['role']);
    
    if ($stmt->execute()) {
        echo "User {$user['username']} created successfully.<br>";
    } else {
        echo "Error creating user {$user['username']}: " . $stmt->error . "<br>";
    }
    
    $stmt->close();
}

$conn->close();
?>
