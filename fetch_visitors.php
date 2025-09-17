<?php
include 'db.php';
date_default_timezone_set('Asia/Manila');

$filter = $_GET['filter'] ?? '';
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$last7 = date('Y-m-d', strtotime('-7 days'));

switch ($filter) {
    case 'today':
        $sql = "SELECT * FROM visitor_logs WHERE date_of_visit = '$today'";
        break;
    case 'yesterday':
        $sql = "SELECT * FROM visitor_logs WHERE date_of_visit = '$yesterday'";
        break;
    case 'last7':
        $sql = "SELECT * FROM visitor_logs WHERE date_of_visit BETWEEN '$last7' AND '$today'";
        break;
    case 'total':
    default:
        $sql = "SELECT * FROM visitor_logs";
        break;
}

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Pass the filter value along with the export button link
    echo "<button onclick=\"window.location.href='export_visitors.php?filter=$filter'\" class='export-btn'><i class='fas fa-file-csv'></i> Export CSV</button><br><br>";
    echo "<table border='1' style='width:100%; border-collapse:collapse;'>
            <thead style='background-color: blue; color: white;'>
                <tr>
                    <th>Visitor Name</th>
                    <th>Purpose</th>
                    <th>Destination</th>
                    <th>Date of Visit</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>" . htmlspecialchars($row['purpose_of_visit']) . "</td>
                <td>" . htmlspecialchars($row['destination']) . "</td>
                <td>" . (!empty($row['date_of_visit']) ? date('F j, Y', strtotime($row['date_of_visit'])) : '') . "</td>
                <td>" . (!empty($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : '') . "</td>
                <td>" . (!empty($row['time_out']) ? date('h:i A', strtotime($row['time_out'])) : '') . "</td>
                <td><a href='dashboard_security.php?delete=" . $row['logID'] . "' style='color:red;'><i class='fas fa-trash'></i></a></td>
              </tr>";
    }

    echo "</tbody></table>";
} else {
    echo "<p class='no-data'>No visitors check-in/check-out list available.</p>";
}
?>
