<?php
include 'db.php';
session_start();

if (isset($_GET['logID'])) {
    $logID = $_GET['logID'];

    $query = "SELECT * FROM visitor_logs WHERE logID = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $logID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $visitor = $result->fetch_assoc();
            echo json_encode($visitor);  // Return the visitor data as JSON, including the visitor ID
        } else {
            echo json_encode(['error' => 'Visitor not found']);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Query preparation failed']);
    }
} else {
    echo json_encode(['error' => 'No logID provided']);
}

$conn->close();
?>
