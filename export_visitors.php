<?php
include 'db.php';

// Capture the filter from the URL parameter
$filter = $_GET['filter'] ?? '';
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$last7 = date('Y-m-d', strtotime('-7 days'));

switch ($filter) {
    case 'today':
        $sql = "SELECT * FROM visitor_logs WHERE date_of_visit = '$today'";
        $filename = "todays_visitors.csv";
        break;
    case 'yesterday':
        $sql = "SELECT * FROM visitor_logs WHERE date_of_visit = '$yesterday'";
        $filename = "yesterdays_visitors.csv";
        break;
    case 'last7':
        $sql = "SELECT * FROM visitor_logs WHERE date_of_visit BETWEEN '$last7' AND '$today'";
        $filename = "last7days_visitors.csv";
        break;
    case 'total':
    default:
        $sql = "SELECT * FROM visitor_logs";
        $filename = "all_visitors.csv";
        break;
}

$result = $conn->query($sql);

header('Content-Type: text/csv');
header("Content-Disposition: attachment;filename=$filename");

$output = fopen('php://output', 'w');
fputcsv($output, ['Visitor Name', 'Purpose', 'Destination', 'Date of Visit', 'Time In', 'Time Out']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['name'],
        $row['purpose_of_visit'],
        $row['destination'],
        date('F j, Y', strtotime($row['date_of_visit'])),
        date('h:i A', strtotime($row['time_in'])),
        $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : ''
    ]);
}
fclose($output);
exit();
?>
