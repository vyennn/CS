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

  // Handle visitor check-in
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !isset($_POST['checkout_logID'])) {
      $name = $_POST['name'];
      $email = $_POST['email'];
      $contact = $_POST['contact'];
      $address = $_POST['address'];
      $whomToMeet = $_POST['whom_to_meet'];
      $department = $_POST['department'];
      $reason = $_POST['reason'];
      $date = date('Y-m-d');
      $timeIn = date('Y-m-d H:i:s');

      $campusVisitorID = $_POST['campus_visitor_id'];

      $check = $conn->prepare("SELECT logID FROM visitor_logs WHERE campus_visitor_id = ?");
      $check->bind_param("s", $campusVisitorID);
      $check->execute();
      $check->store_result();

      if ($check->num_rows > 0) {
        $_SESSION['success_message'] = "❌ Error: Campus Visitor ID already exists.";
        $_SESSION['scroll_target'] = 'form';
        header("Location: addVisitor.php");
        exit();
      }

      $sql = "INSERT INTO visitor_logs (security_personnelID, name, contact_number, address, purpose_of_visit, destination, date_of_visit, time_in, IDType, campus_visitor_id, email, whom_to_meet) 
      VALUES (
        '$userID', '$name', '$contact', '$address', '$reason', '$department', '$date', '$timeIn', '$whom', '$campusVisitorID', '$email', '$whomToMeet'
      )";

      $conn->query($sql);
      $_SESSION['success_message'] = "Visitor added! Assigned Campus Visitor ID: <strong>$campusVisitorID</strong>";
      $_SESSION['scroll_target'] = 'form';
      header("Location: addVisitor.php");
      exit();
  }

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
    $_SESSION['scroll_target'] = 'table';
    header("Location: dashboard_security.php");
    exit();
  }


  $today = date('Y-m-d');
  $result = $conn->query("SELECT * FROM visitor_logs WHERE date_of_visit = '$today' AND security_personnelID = $userID ORDER BY time_in DESC");
?>

<!DOCTYPE html>
  <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Add Visitor</title>
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

        .section-title{ 
          color:#158b33; 
          text-align:center; 
          margin-top:40px; 
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

    .form-section { 
      max-width: 900px; 
      margin: 0 auto; 
      padding: 30px 40px; 
      background: #fff; 
      border-radius: 12px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
      display: grid; 
      grid-template-columns: 200px 1fr; 
      gap: 15px 24px; 
      align-items: center; 
    }

    .form-section label { 
      font-weight: bold; 
      color: #333; 
    }
    .form-section input, .form-section textarea { 
      width: 100%; 
      padding: 10px; 
      border-radius: 6px; 
      border: 1px solid #ccc; 
    }

    .form-section textarea { 
      resize: none; 
      height: 80px; 
    }

    .form-actions { 
      grid-column: span 2; 
      display: flex; 
      justify-content: flex-end; 
      margin-top: 10px; 
    }

    .submit-btn { 
      background-color: #117126; 
      color: white; 
      padding: 10px 25px; 
      border: none; 
      border-radius: 6px; 
      font-size: 1rem; 
      font-weight: bold; 
      cursor: pointer; 
      transition: background-color 0.3s ease; 
    }

    .submit-btn:hover { 
      background-color: #0d5c1f; 
    }

    table { 
      width: 100%; 
      max-width: 1000px; 
      margin: 40px auto 0; 
      border-collapse: collapse; 
      font-size: 0.95rem; 
      border-radius: 8px; 
      overflow: hidden; 
      box-shadow: 0 0 8px rgba(0,0,0,0.05); 
      margin-top:10px; 
    }

    th, td { 
      padding: 14px; 
      border-bottom: 1px solid #e0e0e0; 
    }

    th { 
      background: #117126; 
      color: white; 
    }

    td { 
      background-color: #f9f9f9; 
    }

    .visitor-id-wrapper {
      display: flex;
      align-items: center;
    }

    .prefix {
      background-color: #eee;
      padding: 10px;
      border: 1px solid #ccc;
      border-right: none;
      border-radius: 6px 0 0 6px;
      font-weight: bold;
      color: #555;
    }

    .visitor-id-input {
      flex: 1;
      padding: 10px;
      border: 1px solid #ccc;
      border-left: none;
      border-radius: 0 6px 6px 0;
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const visitorIDInput = document.getElementById('visitorID');

      // Autofill "V-" when input is focused
      visitorIDInput.addEventListener('focus', function () {
        if (!visitorIDInput.value.startsWith('V-')) {
          visitorIDInput.value = 'V-';
        }
      });

      // Prevent removing "V-"
      visitorIDInput.addEventListener('input', function () {
        if (!visitorIDInput.value.startsWith('V-')) {
          visitorIDInput.value = 'V-';
        }
      });
    });
  </script>
  </head>
  <body>
    <div class="sidebar">
    <h2><span class="campus">Campus</span><span class="sentinel">Sentinel</span></h2>
      <a href="dashboard_security.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="addVisitor.php" class="active"><i class="fa-solid fa-user-plus"></i> Add Visitor</a>
      <a href="manageVisitors.php"><i class="fa-solid fa-list-check"></i> Manage Visitors</a>
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

    <h3 class="section-title"><i class="fas fa-user-plus"></i> Add Visitor</h3>
    <form method="POST" class="form-section" id="form">
      <label>Visitor ID</label>
      <input type="text"name="campus_visitor_id" id="visitorID" placeholder="V-000" required>
      <label>Full Name</label>
      <input type="text" name="name" required>
      <label>Email Input</label>
      <input type="email" name="email" required>
      <label>Phone Number</label>
      <input type="text" name="contact">
      <label>Address</label>
      <textarea name="address"></textarea>
      <label>Whom to Meet</label>
      <input type="text" name="whom_to_meet">
      <label>Destination</label>
      <input type="text" name="department">
      <label>Reason to Meet</label>
      <input type="text" name="reason">
      <div class="form-actions">
        <button type="submit" class="submit-btn">Add</button>
      </div>
    </form>
  </div>
  </body>
</html>
