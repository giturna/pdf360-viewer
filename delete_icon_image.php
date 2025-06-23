<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';
$conn = db_connect("360cams");

$response = ["status" => "error", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["imageID"]) && isset($_POST["iconID"])) {
    $imageID = intval($_POST["imageID"]);
    $iconID  = intval($_POST["iconID"]);

    $conn->begin_transaction();
    try {
        // 1) Delete the record of this relationship from the "Conn_img_icon" table
        $stmt = $conn->prepare("DELETE FROM Conn_img_icon WHERE ID_Icon = ? AND ID_Image = ?");
        $stmt->bind_param("ii", $iconID, $imageID);
        $stmt->execute();
        $stmt->close();

        // 2) get the File_Path from the "Images" table
        $stmt = $conn->prepare("SELECT File_Path FROM Images WHERE ID_Image = ?");
        $stmt->bind_param("i", $imageID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $filePath = $row["File_Path"];
        } else {
            throw new Exception("Image not found in DB.");
        }
        $stmt->close();

        // 3) delete the image from the "Images" table
        $stmt = $conn->prepare("DELETE FROM Images WHERE ID_Image = ?");
        $stmt->bind_param("i", $imageID);
        $stmt->execute();
        $stmt->close();

        // 4) delete the file from the server
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $conn->commit();
        $response["status"] = "success";
        $response["message"] = "The image was deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $response["message"] = "Transaction failed: " . $e->getMessage();
    }
} else {
    $response["message"] = "Invalid request: missing imageID or iconID.";
}

echo json_encode($response);
exit;
?>
