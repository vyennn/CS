<?php
include 'db.php';
session_start();

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$userRole = $_SESSION['role'];  // Get the role of the logged-in user
$username = $_SESSION['username'];
$userID = $_SESSION['userID'];

// Check if the date range is set
$fromDate = isset($_POST['from_date']) ? $_POST['from_date'] : (isset($_GET['from_date']) ? $_GET['from_date'] : '');
$toDate = isset($_POST['to_date']) ? $_POST['to_date'] : (isset($_GET['to_date']) ? $_GET['to_date'] : '');
$search = isset($_GET['search']) ? $_GET['search'] : '';  // Get the search term

// Query to get the filtered data if both dates are set
$query = "SELECT * FROM visitor_logs WHERE 1=1";
if ($search) {
    $query .= " AND name LIKE '%$search%'";
}
if ($fromDate && $toDate) {
    $query .= " AND date_of_visit BETWEEN '$fromDate' AND '$toDate'";
}

$result = $conn->query($query);

// Export to CSV functionality
if (isset($_POST['export_csv']) && $fromDate && $toDate) {
    $filename = "visitor_logs_$fromDate" . "_to_$toDate.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    // Add header row to CSV
    fputcsv($output, ['Full Name', 'Contact Number', 'Email', 'Destination', 'Purpose of Visit', 'Entry Date', 'Entry Time', 'Exit Time']);

    // Output data rows to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['contact_number'],
            $row['email'],
            $row['destination'],
            $row['purpose_of_visit'],
            date('M d, Y', strtotime($row['date_of_visit'])),
            date('h:i A', strtotime($row['time_in'])),
            $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '---'
        ]);
    }

    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History Reports</title>
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
    }

    .campus {
      color:#117126;
    }

    .sentinel {
      color:black;
      font-weight:bold;
    }

    .section-title { 
      color: #158b33; 
      text-align: center; 
      margin-top: 40px; 
    }

    .date-range-form { 
      display: flex; 
      justify-content: center; 
      gap: 20px; 
      margin-bottom: 30px; 
    }

    .date-range-form input { 
      padding: 12px; 
      border-radius: 6px; 
      border: 1px solid #ccc; 
      font-size: 1rem; 
      width: 200px; 
    }

    .date-range-form button { 
      padding: 12px 25px; 
      background-color: #117126; 
      color: white; 
      border: none; 
      border-radius: 6px; 
      cursor: pointer; 
    }

    .date-range-form button:hover { 
      background-color: #0d5c1f; 
    }

    table { 
      width: 100%; 
      margin-top: 20px; 
      border-collapse: collapse; 
      font-size: 0.95rem; 
      border-radius: 8px; 
      overflow: hidden; 
      box-shadow: 0 0 8px rgba(0, 0, 0, 0.05); 
      text-align: center; 
    }

    th, td { 
      padding: 14px; 
      border-bottom: 1px solid #e0e0e0; 
      text-align: center; 
    }

    th { 
      background: #117126; 
      color: white; 
      text-align: center; 
    }

    td { 
      background-color: #f9f9f9; 
      text-align: center; 
    }

    .export-btn { 
      margin-top: 10px; 
      padding: 8px 7px; 
      background-color: #117126; 
      color: white; 
      border: none; 
      border-radius: 6px; 
      cursor: pointer; 
    }

    .export-btn:hover { 
      background-color: #0d5c1f; 
    }

    .search-export-wrapper {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-form {
      text-align: left;
    }

    .search-form input {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      width: 400px;
    }

    .search-form button {
      padding: 8px 16px;
      background-color: #117126;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

  </style>
</head>
<body>

<div class="sidebar">
  <h2><span class="campus">Campus</span><span class="sentinel">Sentinel</span></h2>
  <?php if ($userRole == 'admin'): ?>
    <a href="dashboard_admin.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="historyReports.php" class="active"><i class="fa-solid fa-calendar-days"></i> History Reports</a>
    <a href="listOfguard.php"><i class="fa-solid fa-rectangle-list"></i> Guards Info</a>
  <?php elseif ($userRole == 'security'): ?>
    <a href="dashboard_security.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="addVisitor.php"><i class="fa-solid fa-user-plus"></i> Add Visitor</a>
    <a href="manageVisitors.php"><i class="fa-solid fa-list-check"></i> Manage Visitors</a>
    <a href="historyReports.php" class="active"><i class="fa-solid fa-calendar-days"></i> History Reports</a>
  <?php endif; ?>
  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-panel">
  <h3 class="section-title"><i class="fas fa-calendar-alt"></i> History | Between Dates Report</h3>

  <!-- Date Range Filter -->
  <div class="date-range-form">
    <form method="POST">
      <label for="from_date">From Date</label>
      <input type="date" name="from_date" value="<?= $fromDate ?>" required>
      <label for="to_date">To Date</label>
      <input type="date" name="to_date" value="<?= $toDate ?>" required>
      <button type="submit">Submit</button>
    </form>
  </div>

  <!-- Display Date Range Selected -->
  <?php if ($fromDate && $toDate): ?>
    <h4 style="text-align:center; color: #333;">Data between: "<strong><?= $fromDate ?></strong> to <strong><?= $toDate ?></strong>"</h4>

    <!-- Search Bar and Export to CSV Button Wrapper -->
    <div class="search-export-wrapper">
      <!-- Search Bar -->
      <div class="search-form">
        <form method="GET" style="display: flex; align-items: center;">
          <!-- Hidden inputs to preserve the date range when searching -->
          <input type="hidden" name="from_date" value="<?= htmlspecialchars($fromDate); ?>">
          <input type="hidden" name="to_date" value="<?= htmlspecialchars($toDate); ?>">

          <input type="text" name="search" placeholder="Search by Full Name" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" style="padding: 8px; border-radius: 6px; border: 1px solid #ccc; width: 400px;">
          <button type="submit" style="background: none; border: none; cursor: pointer; padding: 8px;">
            <i class="fas fa-search" style="color: #117126; font-size: 18px;"></i>
          </button>
        </form>
      </div>

      <!-- Export to CSV Button -->
      <form method="POST" style="text-align:right;">
        <input type="hidden" name="from_date" value="<?= $fromDate ?>">
        <input type="hidden" name="to_date" value="<?= $toDate ?>">
        <button type="submit" name="export_csv" class="export-btn">Export to CSV</button>
      </form>
    </div>

  <?php endif; ?>

  <!-- Data Table -->
  <?php if ($fromDate && $toDate): ?>
    <table>
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Contact Number</th>
          <th>Email</th>
          <th>Destination</th>
          <th>Purpose of Visit</th>
          <th>Entry Date</th>
          <th>Entry Time</th>
          <th>Exit Time</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['contact_number']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['destination']) ?></td>
              <td><?= htmlspecialchars($row['purpose_of_visit']) ?></td>
              <td><?= date('M d, Y', strtotime($row['date_of_visit'])) ?></td>
              <td><?= date('h:i A', strtotime($row['time_in'])) ?></td>
              <td><?= $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '---' ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;">No records found for the selected date range and search term.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align:center; color: #888;">Please select a date range to view the visitor history.</p>
  <?php endif; ?>
</div>

</body>
</html>
