<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';

$dbName = "360cams";
$conn = db_connect($dbName);

$response = ["status" => "error", "message" => "Icon deletion failed."];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["iconID"])) {
    $iconID = intval($_POST["iconID"]);
    
    $conn->begin_transaction();
    
    try {
        // 1) Find the images (ID_Image) linked to this icon
        //    In the Conn_img_icon table where ID_Icon = ?

        $stmt = $conn->prepare("
            SELECT ID_Image 
            FROM Conn_img_icon
            WHERE ID_Icon = ?
        ");
        $stmt->bind_param("i", $iconID);
        $stmt->execute();
        $res = $stmt->get_result();

        $imageIDs = [];
        while ($row = $res->fetch_assoc()) {
            $imageIDs[] = $row["ID_Image"];
        }
        $stmt->close();

        // 2) Delete the icon-image relationship records (Conn_img_icon)
        $stmt = $conn->prepare("
            DELETE FROM Conn_img_icon
            WHERE ID_Icon = ?
        ");
        $stmt->bind_param("i", $iconID);
        $stmt->execute();
        $stmt->close();

        // 3) Delete this icon from the Icon table
        $stmt = $conn->prepare("
            DELETE FROM Icons
            WHERE ID_Icon = ?
        ");
        $stmt->bind_param("i", $iconID);
        $stmt->execute();
        $stmt->close();

        // 4) For each image: delete from the Images table and remove the file from the server
        foreach ($imageIDs as $imgID) {
            // a) Get the file path
            $stmt = $conn->prepare("
                SELECT File_Path
                FROM Images
                WHERE ID_Image = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $imgID);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r->num_rows > 0) {
                $pathRow = $r->fetch_assoc();
                $imgPath = $pathRow["File_Path"];
            } else {
                $imgPath = null; // Not found
            }
            $stmt->close();

            // b) Delete the image from the Images table
            $stmt = $conn->prepare("
                DELETE FROM Images
                WHERE ID_Image = ?
            ");
            $stmt->bind_param("i", $imgID);
            $stmt->execute();
            $stmt->close();

            // c) Delete the file from the server
            if ($imgPath) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imgPath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }

        // Complete the transaction successfully
        $conn->commit();
        
        $response["status"]  = "success";
        $response["message"] = "Icon and all linked images successfully deleted.";
    } catch (Exception $e) {
        // Roll back the operation in case of an error
        $conn->rollback();
        $response["message"] = "Transaction failed: " . $e->getMessage();
    }
} else {
    $response["message"] = "Invalid request: iconID missing.";
}

echo json_encode($response);
exit;
?>
