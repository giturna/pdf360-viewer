<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';

$dbName = "360cams";
$conn = db_connect($dbName);

$response = ["status" => "error", "message" => "Invalid request"];

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    // Get the PDF belonging to the project
    $stmt = $conn->prepare("SELECT ID_Project, Name, Erstellungsdatum FROM Projects");
    $stmt->execute();
    $result = $stmt->get_result();

    $projects = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $projects[] = [
                "projectID" => $row["ID_Project"],
                "projectName" => $row["Name"],
                "erstellungsdatum" => $row["Erstellungsdatum"]
            ];
        }
        $response["status"] = "success";
        $response["projects"] = $projects;
    }else{
        $response["message"] = "No Project found.";
    }
    $stmt->close();
}

echo json_encode($response);
exit;
?>