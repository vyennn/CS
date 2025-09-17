<?php
include 'db.php';
session_start();

date_default_timezone_set('Asia/Manila'); // Set Philippine timezone

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$userRole = $_SESSION['role'];
$username = $_SESSION['username'];
$userID = $_SESSION['userID'];

$sql = "SELECT name FROM users WHERE username = '$username'";
$result = $conn->query($sql);
$fullName = ($result->num_rows > 0) ? $result->fetch_assoc()['name'] : $username;

// Current time to determine shift
$currentHour = date('H'); // Current hour (24-hour format)

// Find guards that are currently on duty based on their shift time
$onDutyGuardsQuery = "SELECT personnel_name, shift_schedule, contactNumber FROM security_personnel 
                      WHERE (shift_schedule = 'Day Shift' AND $currentHour >= 6 AND $currentHour < 18) 
                      OR (shift_schedule = 'Night Shift' AND ($currentHour >= 18 OR $currentHour < 6))";
$onDutyGuards = $conn->query($onDutyGuardsQuery);

// Handle deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $conn->query("DELETE FROM visitor_logs WHERE logID = $deleteId");
    $_SESSION['success_message'] = "Visitor successfully deleted!";
    $_SESSION['table_visible'] = true;
    header('Location: dashboard_admin.php');
    exit();
}

$guards = $conn->query("SELECT personnel_name, shift_schedule, contactNumber FROM security_personnel");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guards Info</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { 
        box-sizing: border-box; 
    }

    html, body {
      margin: 0; 
      height: 100%; 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6fb;
    }

    body { 
        display: flex;
        overflow: hidden; 
    }

    .sidebar {
      width: 250px; 
      background: #fff; 
      border-right: 1px solid #ddd;
      padding: 20px; 
      height: 100vh; 
      position: fixed;
    }

    .sidebar h2 { 
        text-align: center; 
        color: #117126; 
        margin-bottom: 30px; 
    }

    .sidebar a {
      display: block; 
      padding: 12px 20px; 
      color: #333;
      text-decoration: none; 
      border-radius: 6px; 
      margin-bottom: 5px;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: #e8f5e9; 
      color: #117126;
    }

    .main-panel {
      margin-left: 250px; 
      padding: 40px 60px;
      width: calc(100% - 250px); 
      overflow-y: auto; 
      position: relative;
    }

    .campus {
   color:#117126;
}

.sentinel {
   color:black;
   font-weight:bold;
}

    .greeting {
      background-color: #158b33; 
      color: white;
      padding: 30px; 
      border-radius: 10px; 
      text-align: center;
    }

    .greeting h2 { 
        margin: 0; 
        font-size: 2.3rem; 
    }

    .cards {
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px; 
      margin: 20px 0;
    }

    .card {
      background: linear-gradient(200deg, #198754, #28a745);
      color: white; 
      padding: 20px; 
      border-radius: 12px;
      display: flex; 
      flex-direction: column; 
      justify-content: space-between;
      cursor: pointer;
    }

    .card h4 { 
        margin: 0; 
        font-size: 1rem;
    }

    .card .count { 
        font-size: 2.5rem; 
        font-weight: bold; 
        margin: 10px 0; 
    }

    .popup-notification {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color:rgb(98, 165, 114);
      color: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      z-index: 9999;
      font-size: 1.2rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      animation: fadeOut 3s ease forwards;
    }

    .popup-notification i { 
        font-size: 3rem; 
        margin-bottom: 10px; 
    }

    @keyframes fadeOut {
      0% { opacity: 1; } 
      80% { opacity: 1; } 
      100% { opacity: 0; visibility: hidden; }
    }

    .export-btn {
      margin-top: 10x; 
      display: inline-block;
      padding: 10px 15px; 
      background-color: #117126;
      color: white; 
      text-decoration: none; 
      border-radius: 10px;
    }

    .export-btn:hover { 
        background-color: #0d5c1f; 
    }
        
    .modal {
      display: none; 
      position: fixed; 
      z-index: 99999;
      left: 0; top: 0; 
      width: 100%; 
      height: 100%; 
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }

    .modal-content {
      background-color: #fff; 
      margin: 10% auto; 
      padding: 20px;
      border-radius: 10px; 
      width: 80%; 
      max-height: 80vh; 
      overflow-y: auto;
      text-align: center;
    }

    .close {
      color: #aaa; 
      float: right; 
      font-size: 28px; 
      font-weight: bold;
    }

    .close:hover, .close:focus { 
        color: black; 
        text-decoration: none; 
        cursor: pointer; 
    }

    .no-data { 
        text-align: center; 
        color: #888; 
        font-style: italic; 
        margin-top: 10px; 
    }

    .on-duty-section {
      padding: 20px;
      border-radius: 12px;
      background-color: #e8f5e9;
      margin-bottom: 30px;
    }

    .on-duty-section table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-bottom: 30px;
    }

    .on-duty-section th, .on-duty-section td {
      padding: 10px; 
      border: 1px solid #ccc; 
      text-align: left;
    }

    .on-duty-section th {
      background-color: #117126; 
      color: white;
    }

    .on-duty-section td {
      background-color: #fff;
    }

    .modal-title {
      font-size: 1.4rem; 
      font-weight: bold; 
      color: #117126;
      margin: 20px auto 15px; 
      text-align: center;
    }

  </style>
</head>
<body>
    <div class="sidebar">
      <h2><span class="campus">Campus</span><span class="sentinel">Sentinel</span></h2>
      <a href="dashboard_admin.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="historyReports.php"><i class="fa-solid fa-calendar-days"></i> History Reports</a>
      <a href="listOfguard.php" class="active"><i class="fa-solid fa-rectangle-list"></i> Guards Info</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-panel">
   
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="popup-notification">
        <i class="fas fa-check-circle"></i>
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <!-- On Duty Guards Section -->
    <div class="on-duty-section">
        <h3 style="color: #117126; text-align: center;">Guard(s) On Duty</h3>
        <table>
            <thead>
                <tr>
                    <th>Guard Name</th>
                    <th>Shift</th>
                    <th>Contact Number</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($onDutyGuards->num_rows > 0): ?>
                    <?php while ($row = $onDutyGuards->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['personnel_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['shift_schedule']); ?></td>
                        <td><?php echo htmlspecialchars($row['contactNumber']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No guards on duty at the moment.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- List of all guards -->
<div class="guard-section">
  <h3 style="color: #117126; text-align: center;">List of Guards Information</h3>
  <table style="width: 100%; border-collapse: collapse; border: 1px solid #ccc;">
    <thead>
      <tr style="background-color: #e8f5e9; color: #117126;">
        <th style="border: 1px solid #ccc; padding: 10px;">Guard Name</th>
        <th style="border: 1px solid #ccc; padding: 10px;">Shift</th>
        <th style="border: 1px solid #ccc; padding: 10px;">Contact Number</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($guards as $guard): ?>
      <tr>
        <td style="text-align:center; border-left: 1px solid #ccc; border-right: 1px solid #ccc;"><?php echo htmlspecialchars($guard['personnel_name']); ?></td>
        <td style="text-align:center; border-left: 1px solid #ccc; border-right: 1px solid #ccc;"><?php echo htmlspecialchars($guard['shift_schedule']); ?></td>
        <td style="text-align:center; border-left: 1px solid #ccc; border-right: 1px solid #ccc;"><?php echo htmlspecialchars($guard['contactNumber']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

    </div>
</body>
</html>
              