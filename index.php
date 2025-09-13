<?php
// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db.php';

// Start session
session_start();

// Get current hour (24-hour format)
$currentHour = date('H');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $selectedRole = $_POST['role'];

    if (empty($selectedRole)) {
        $error = "Please select a role.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Verify the hashed password
    if (password_verify($password, $user['password'])) {

        if ($selectedRole !== $user['role']) {
            $error = "Incorrect role selected for this account.";
        } else {
            // Security personnel shift check (keep your existing code)
            if ($user['role'] == 'security') {
                $guardShiftQuery = "SELECT shift_schedule FROM security_personnel WHERE personnel_name = ?";
                $stmt2 = $conn->prepare($guardShiftQuery);
                $stmt2->bind_param("s", $username);
                $stmt2->execute();
                $shiftResult = $stmt2->get_result();
                $shiftData = $shiftResult->fetch_assoc();

                if ($shiftData['shift_schedule'] == 'Night Shift' && $currentHour >= 6 && $currentHour < 18) {
                    $error = "You cannot log in during the day if you are on a night shift.";
                } elseif ($shiftData['shift_schedule'] == 'Day Shift' && ($currentHour >= 18 || $currentHour < 6)) {
                    $error = "You cannot log in during the night if you are on a day shift.";
                }
            }

            if (!isset($error)) {
                $_SESSION['userID'] = $user['userID'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] == 'admin') {
                    header('Location: dashboard_admin.php');
                } else {
                    header('Location: dashboard_security.php');
                }
                exit();
            }
        }

    } else {
        $error = "Invalid username or password.";
    }
} else {
    $error = "Invalid username or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - LogTrack</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            display: flex;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            width: 900px;
            max-width: 100%;
            overflow: hidden;
        }

        .left, .right {
            flex: 1;
            padding: 40px;
        }

        .left {
            background-color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .left h1 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .left h2 {
            font-size: 2.5rem;
            margin-bottom: 25px;
        }

        .log {
            color: blue;
        }

        .track {
            color: black;
            font-weight: bold;
        }

        .left p {
            margin-bottom: 15px;
        }

        .roles {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .role-box {
            padding: 15px 20px;
            border-radius: 12px;
            border: none;
            background-color: #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .role-box i {
            font-size: 1.4rem;
        }

        .role-box span {
            font-size: 1rem;
        }

        .role-box.active {
            background-color: blue;
            color: white;
        }

        .right {
            background-color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 40px 60px 40px 40px;
        }

        form label {
            display: block;
            margin-bottom: 5px;
            margin-right:10px;
            font-size: 1rem;
            color: #333;
        }

        form input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }

        form button {
            padding: 12px 30px;
            background-color: blue;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
        }

        form button:hover {
            background-color: #003ecb;
        }

        .form-wrapper {
            width: 100%;
            max-width: 300px;
        }


        .right a {
            margin-top: 10px;
            text-align: center;
            font-size: 0.9rem;
            color: blue;
            text-decoration: none;
        }

        .right a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="left">
        <h1>Login to</h1>
        <h2><span class="log">Log</span><span class="track">Track</span></h2>
        <p>Please select your role</p>
        <div class="roles">
            <div class="role-box" id="adminRole">
                <i class="fas fa-user-tie"></i>
                <span>Admin</span>
            </div>
            <div class="role-box" id="securityRole">
                <i class="fas fa-user-shield"></i>
                <span>Security Personnel</span>
            </div>
        </div>
    </div>
    <div class="right">
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form action="index.php" method="POST" style="display: flex; flex-direction: column;">
        <input type="hidden" name="role" id="roleInput">
        
        <label for="username">Username</label>
        <input type="text" name="username" id="username" placeholder="Type your username" required />
        
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Type your password" required />
        
        <a href="forgot_password.php" style="margin-top: -10px; margin-bottom: 20px; font-size: 0.9rem; color: blue; text-decoration: none;">Forgot your password?</a>
        
        <div style="display: flex; justify-content: center;">
         <button type="submit" style="padding: 12px 30px;">Login</button>
        </div>

    </form>
</div>

</div>

<script>
    const adminRole = document.getElementById('adminRole');
    const securityRole = document.getElementById('securityRole');
    const roleInput = document.getElementById('roleInput');

    adminRole.addEventListener('click', () => {
        adminRole.classList.add('active');
        securityRole.classList.remove('active');
        roleInput.value = 'admin';
    });

    securityRole.addEventListener('click', () => {
        securityRole.classList.add('active');
        adminRole.classList.remove('active');
        roleInput.value = 'security';
    });
</script>
</body>
</html>
