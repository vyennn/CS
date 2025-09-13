<?php
include 'db.php';

$users = [
    ['username' => 'Admin1', 'password' => 'adminpass'],
    ['username' => 'Admin2', 'password' => 'adminpass'],
    ['username' => 'Guard1', 'password' => 'guardpass'],
    ['username' => 'Guard2', 'password' => 'guardpass']
];

foreach ($users as $user) {
    $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $user['username']);
    $stmt->execute();
}

echo "Passwords updated to hashed version successfully.";
$conn->close();
?>
