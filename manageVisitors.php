<?php
  include 'db.php';
  session_start();

  date_default_timezone_set('Asia/Manila');

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

  // Fetch all visitors checked in today and sort by Visitor ID in ascending order
  $today = date('Y-m-d');
  $result = $conn->query("SELECT * FROM visitor_logs WHERE date_of_visit = '$today' AND security_personnelID = $userID ORDER BY campus_visitor_id ASC");

  // Handle visitor check-out by logID
  if (isset($_POST['checkout_logID'])) {
    $logID = $_POST['checkout_logID'];
    $timeOut = date('Y-m-d H:i:s');
    $conn->query("UPDATE visitor_logs SET time_out = '$timeOut' WHERE logID = $logID");

    // Fetch the visitor ID
    $visitorQuery = $conn->query("SELECT campus_visitor_id FROM visitor_logs WHERE logID = $logID");
    $visitor = $visitorQuery->fetch_assoc();
    $visitorID = htmlspecialchars($visitor['campus_visitor_id']); // Safely encode the visitor ID

    // Update the success message with Visitor ID
    $_SESSION['success_message'] = "Visitor with ID <strong>$visitorID</strong> successfully checked out!";
    header("Location: manageVisitors.php");
    exit();
  }

  // Handle visitor information update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_logID'])) {
    // Update visitor data
    $logID = $_POST['update_logID'];
    $campusVisitorID = $_POST['campus_visitor_id'];  // Updated Visitor ID
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $whom = $_POST['whom_to_meet'];
    $department = $_POST['department'];
    $reason = $_POST['reason'];

    $updateQuery = "UPDATE visitor_logs SET name = '$name', email = '$email', contact_number = '$contact', address = '$address', whom_to_meet = '$whom', destination = '$department', purpose_of_visit = '$reason', campus_visitor_id = '$campusVisitorID' WHERE logID = '$logID'";

    if ($conn->query($updateQuery)) {
      $_SESSION['success_message'] = "Visitor's information updated successfully!";
    } else {
      $_SESSION['success_message'] = "Error updating visitor's information!";
    }

    header("Location: manageVisitors.php");
    exit();
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Visitors</title>
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
      scroll-behavior: smooth; 
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
    
    .popup-notification {
      position: fixed;
      top: 50%; 
      left: 50%; 
      transform: translate(-50%, -50%); 
      background-color: rgb(98, 165, 114); 
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
      animation: fadeOut 5s ease forwards; 
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

    .update-btn { 
      background-color: #28a745; 
      color: white; 
      padding: 8px 15px; 
      border: none; 
      border-radius: 6px; 
      cursor: pointer; 
    }

    .checkout-btn { 
      background-color: #dc3545; 
      color: white; 
      padding: 8px 15px; 
      border: none; 
      border-radius: 6px; 
      cursor: pointer; 
    }

    .popup-modal { 
      display: none; 
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%; 
      background-color: rgba(0, 0, 0, 0.5); 
      z-index: 9999; 
      align-items: center; 
      justify-content: center; 
    }

    .modal-content { 
      background-color: #e1f4e2; 
      padding: 20px; 
      border-radius: 12px; 
      width: 100%; 
      max-width: 700px; 
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); 
      transition: transform 0.3s ease-in-out; 
      margin: 50px auto; 
      position: relative; 
      background: #fff; 
      border: 2px solidrgb(255, 255, 255); 
      color: #117126; 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
      display: flex; 
      flex-direction: column; 
    }
    
    .close { 
      position: absolute; 
      top: 10px; 
      right: 10px; 
      font-size: 30px; 
      font-weight: bold; 
      color: #117126; 
      cursor: pointer; 
    }

    .close:hover, .close:focus { 
      color: #d9534f; 
      text-decoration: none; 
    }

    .form-section { 
      display: flex; 
      flex-direction: column; 
      gap: 0px; 
      margin-bottom: 1px; 
      max-width: 900px; 
    }

    .form-section label { 
      font-weight: bold; 
      color: #117126; 
      font-size: 1rem; 
      margin-bottom: 10px; 
      text-align:left; 
    }

    .form-section input, .form-section textarea { 
      width: 100%; 
      padding: 5px; 
      border-radius: 6px; 
      border: 2px solid #ccc; 
      font-size: 1rem; 
      margin-bottom: 10px; 
      box-sizing: border-box; 
      transition: all 0.3s ease; 
      background-color:rgb(255, 255, 255); 
    }

    .submit-btn { 
      background-color: #117126; 
      color: white; 
      padding: 14px 30px; 
      border: none; 
      border-radius: 6px; 
      font-size: 1rem; 
      font-weight: bold; 
      cursor: pointer; 
      transition: background-color 0.3s ease; 
      width: 100%; 
    }

    .submit-btn:hover { 
      background-color: #0d5c1f; 
    }

  </style>
</head>
<body>
  <div class="sidebar">
  <h2><span class="campus">Campus</span><span class="sentinel">Sentinel</span></h2>
    <a href="dashboard_security.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="addVisitor.php"><i class="fa-solid fa-user-plus"></i> Add Visitor</a>
    <a href="manageVisitors.php" class="active"><i class="fa-solid fa-list-check"></i> Manage Visitors</a>
    <a href="historyReports.php"><i class="fa-solid fa-calendar-days"></i> History Reports</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <div class="main-panel">
    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="popup-notification">
        <i class="fas fa-check-circle"></i>
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
      </div>
    <?php endif; ?>

    <h3 class="section-title"><i class="fas fa-users"></i> Visitors Checked In Today</h3>
    <table>
      <thead>
        <tr>
          <th>Visitor ID</th>
          <th>Visitor Name</th>
          <th>Purpose</th>
          <th>Destination</th>
          <th>Date of Visit</th>
          <th>Time In</th>
          <th>Time Out</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['campus_visitor_id']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['purpose_of_visit']) ?></td>
              <td><?= htmlspecialchars($row['destination']) ?></td>
              <td><?= date('M d, Y', strtotime($row['date_of_visit'])) ?></td>
              <td><?= date('h:i A', strtotime($row['time_in'])) ?></td>
              <td><?= $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '---' ?></td>
              <td>
                <?php if (!$row['time_out']): ?>
                  <button class="update-btn" onclick="openUpdateModal(<?= $row['logID'] ?>)">Update</button>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="checkout_logID" value="<?= $row['logID'] ?>">
                    <button type="submit" class="checkout-btn">Check-out</button>
                  </form>
                <?php else: ?>
                  <span style="color:green; font-weight:bold;">✔ Checked out</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;">No visitors checked in today.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal for Update -->
  <div id="updateVisitorModal" class="popup-modal">
    <div class="modal-content">
      <span class="close" onclick="closeUpdateModal()">&times;</span>
      <h2 style="text-align:center;">Update Visitor Information</h2>
      <form method="POST" id="updateVisitorForm">
        <input type="hidden" name="update_logID" id="update_logID">
        
        <!-- Form Sections for Input Fields -->
        <div class="form-section">
          <div class="form-group">
            <label for="update_visitorID">Visitor ID</label>
            <input type="text" name="campus_visitor_id" id="update_visitorID" required>
          </div>

          <div class="form-group">
            <label for="update_name">Full Name</label>
            <input type="text" name="name" id="update_name" required>
          </div>

          <div class="form-group">
            <label for="update_email">Email</label>
            <input type="email" name="email" id="update_email" required>
          </div>

          <div class="form-group">
            <label for="update_contact">Phone Number</label>
            <input type="text" name="contact" id="update_contact">
          </div>

          <div class="form-group">
            <label for="update_address">Address</label>
            <textarea name="address" id="update_address"></textarea>
          </div>

          <div class="form-group">
            <label for="update_whom_to_meet">Whom to Meet</label>
            <input type="text" name="whom_to_meet" id="update_whom_to_meet">
          </div>

          <div class="form-group">
            <label for="update_department">Destination</label>
            <input type="text" name="department" id="update_department">
          </div>

          <div class="form-group">
            <label for="update_reason">Reason to Meet</label>
            <input type="text" name="reason" id="update_reason">
          </div>

          <div class="form-actions">
            <button type="submit" class="submit-btn">Update</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Open the update modal and populate it with the visitor data
    function openUpdateModal(logID) {
      fetch('get_visitor_details.php?logID=' + logID)
        .then(response => response.json())
        .then(data => {
          document.getElementById('update_logID').value = data.logID;
          document.getElementById('update_visitorID').value = data.campus_visitor_id;
          document.getElementById('update_name').value = data.name;
          document.getElementById('update_email').value = data.email;
          document.getElementById('update_contact').value = data.contact_number;
          document.getElementById('update_address').value = data.address;
          document.getElementById('update_whom_to_meet').value = data.whom_to_meet;
          document.getElementById('update_department').value = data.destination;
          document.getElementById('update_reason').value = data.purpose_of_visit;

          document.getElementById('updateVisitorModal').style.display = 'flex';
        })
        .catch(error => console.error('Error:', error));
    }

    // Close the modal
    function closeUpdateModal() {
      document.getElementById('updateVisitorModal').style.display = 'none';
    }

    // Submit the form using AJAX or normal submit
    document.getElementById('updateVisitorForm').addEventListener('submit', function(e) {
      e.preventDefault();
      this.submit(); // Normally submit or use AJAX
    });
  </script>
</body>
</html>
