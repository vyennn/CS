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

  // Handle deletion
  if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
      $deleteId = intval($_GET['delete']);
      $conn->query("DELETE FROM visitor_logs WHERE logID = $deleteId");
      $_SESSION['success_message'] = "Visitor successfully deleted!";
      $_SESSION['table_visible'] = true;
      header('Location: dashboard_admin.php');
      exit();
  }

  // Visitor statistics
  $totalVisitors = $conn->query("SELECT COUNT(*) AS total FROM visitor_logs")->fetch_assoc()['total'];
  $today = date('Y-m-d');
  $todayVisitors = $conn->query("SELECT COUNT(*) AS total FROM visitor_logs WHERE date_of_visit = '$today'")->fetch_assoc()['total'];
  $yesterday = date('Y-m-d', strtotime('-1 day'));
  $yesterdayVisitors = $conn->query("SELECT COUNT(*) AS total FROM visitor_logs WHERE date_of_visit = '$yesterday'")->fetch_assoc()['total'];
  $last7Days = date('Y-m-d', strtotime('-7 days'));
  $last7Visitors = $conn->query("SELECT COUNT(*) AS total FROM visitor_logs WHERE date_of_visit BETWEEN '$last7Days' AND '$today'")->fetch_assoc()['total'];

  $recentLogs = $conn->query("SELECT * FROM visitor_logs ORDER BY time_in DESC LIMIT 50");
  $guards = $conn->query("SELECT personnel_name, shift_schedule, contactNumber FROM security_personnel");

  if (isset($_GET['export']) && $_GET['export'] == 'csv') {
      $exportLogs = $conn->query("SELECT * FROM visitor_logs ORDER BY time_in DESC");

      header('Content-Type: text/csv');
      header('Content-Disposition: attachment;filename=all_visitor_logs.csv');

      $output = fopen('php://output', 'w');
      fputcsv($output, ['Visitor Name', 'Contact Number', 'Address', 'Purpose', 'Destination', 'Whom to Meet (ID Type)', 'Date of Visit', 'Time In', 'Time Out']);

      while ($row = $exportLogs->fetch_assoc()) {
          fputcsv($output, [
              $row['name'],
              $row['contact_number'],
              $row['address'],
              $row['purpose_of_visit'],
              $row['destination'],
              $row['IDType'],
              date('F j, Y', strtotime($row['date_of_visit'])),
              date('F j, Y h:i A', strtotime($row['time_in'])),
              $row['time_out'] ? date('F j, Y h:i A', strtotime($row['time_out'])) : ''
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
    <title>Admin Dashboard</title>
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
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px; 
        margin-top: 20px;
        margin-left:80px;
        margin-right:80px;
      }

      .card {
        background: linear-gradient(200deg,rgb(55, 141, 78),rgb(23, 129, 46));
        color: white; 
        padding: 10px; 
        border-radius: 12px;
        display: flex; 
        flex-direction: column; 
        justify-content: space-between;
        cursor: pointer;
        position: relative;
      }

      .card-icon {
      position:absolute;
      top: 50%;
      right: 20px;
      transform: translateY(-50%);
      font-size: 3.5rem;
      color: white;
      opacity: 0.2;
    }

      .card h4 { 
          margin: 0; 
          font-size: 1.2rem;
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

      .modal-title {
        font-size: 1.4rem; 
        font-weight: bold; 
        color: #117126;
        margin: 20px auto 15px; 
        text-align: center;
      }

      .visitor-table th {
        background-color: #117126 !important; 
        color: white;
      }

    </style>
  </head>
  <body>
      <div class="sidebar">
      <h2><span class="campus">Campus</span><span class="sentinel">Sentinel</span></h2>
      <a href="dashboard_admin.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
      <a href="historyReports.php"><i class="fa-solid fa-calendar-days"></i> History Reports</a>
      <a href="listOfguard.php"><i class="fa-solid fa-rectangle-list"></i> Guards Info</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
      <div class="main-panel">
      <div class="greeting">
          <h2><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
      </div>
      <?php if (isset($_SESSION['success_message'])): ?>
          <div class="popup-notification">
          <i class="fas fa-check-circle"></i>
          <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
          </div>
      <?php endif; ?>

      <h3 style="margin-top: 30px; color: #117126;text-align:center;">Dashboard</h3>

      <div class="cards">
        <div class="card" onclick="showModal('total')">
        <i class="fas fa-user card-icon"></i>
        <h4>Total Visitors</h4>
        <div class="count"><?php echo $totalVisitors; ?></div>
        <h5>View Visitor</h5>
        </div>
        <div class="card" onclick="showModal('today')">
        <i class="fas fa-user-clock card-icon"></i>
        <h4>Today's Visitors</h4>
        <div class="count"><?php echo $todayVisitors; ?></div>
        <h5>View Visitor</h5>
        </div>
        <div class="card" onclick="showModal('yesterday')">
        <i class="fas fa-user-check card-icon"></i>
        <h4>Yesterday's Visitors</h4>
        <div class="count"><?php echo $yesterdayVisitors; ?></div>
        <h5>View Visitor</h5>
        </div>
        <div class="card" onclick="showModal('last7')">
        <i class="fas fa-users card-icon"></i>
        <h4>Last 7 Days' Visitors</h4>
        <div class="count"><?php echo $last7Visitors; ?></div>
        <h5>View Visitor</h5>
        </div>
    </div>

      <div id="modal" class="modal">
          <div class="modal-content">
          <span class="close" onclick="document.getElementById('modal').style.display='none'">&times;</span>
          <div class="modal-title" id="modal-title"></div>
          <div id="modal-body"></div>
          </div>
      </div>
      <br>
    <h4 style="margin-top: 20px; color:rgb(186, 178, 178); text-align:center; font-family: sans-serif;">
       University Log Monitoring System @2025
    </h4>
      </div>
      <script>
      function showModal(filter) {
      const modal = document.getElementById('modal');
      const modalBody = document.getElementById('modal-body');
      const modalTitle = document.getElementById('modal-title');

      const titles = {
          total: 'Total Visitors List',
          today: "Today's Visitors List",
          yesterday: "Yesterday's Visitors List",
          last7: "Last 7 Days' Visitors List"
          
      };

      modalTitle.textContent = titles[filter] || '';

      fetch('fetch_visitors.php?filter=' + filter)
          .then(response => response.text())
          .then(data => {
          modalBody.innerHTML = data.trim() !== '' ? data : '<p class="no-data">No visitors check-in/check-out list available.</p>';
          modal.style.display = 'block';
          });
      }
      </script>
      </body>
  </html>
