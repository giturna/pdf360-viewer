<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';

$dbName = "360cams";
$conn = db_connect($dbName);

$response = ["status" => "error", "message" => "Project deletion failed."];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["projectID"])) {
    $projectID = intval($_POST["projectID"]);

    $conn->begin_transaction();
    
    try {
        // 1) Find the PDF records belonging to the project (ID_PDF, File_Path)
        $stmt = $conn->prepare("
            SELECT ID_PDF, File_Path 
            FROM PDFs 
            WHERE ID_Project = ?
        ");
        $stmt->bind_param("i", $projectID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $pdfIDs = [];
        $pdfFilePaths = [];
        while ($row = $result->fetch_assoc()) {
            $pdfIDs[]       = $row['ID_PDF'];
            $pdfFilePaths[] = $row['File_Path'];
        }
        $stmt->close();
        
        // 2) Get the IDs of the icons linked to these PDFs
        // Icons in the Icons table matching the ID_PDF
        if (count($pdfIDs) > 0) {
            $inList = implode(',', $pdfIDs); // like "85, 86, ..."
            
            // a) Get the icons
            $stmt = $conn->prepare("
                SELECT ID_Icon 
                FROM Icons
                WHERE ID_PDF IN ($inList)
            ");
            $stmt->execute();
            $res = $stmt->get_result();
            $iconIDs = [];
            while ($r = $res->fetch_assoc()) {
                $iconIDs[] = $r["ID_Icon"];
            }
            $stmt->close();
            
            // b) Delete the Conn_img_icon records for these icons
            if (count($iconIDs) > 0) {
                $inIcons = implode(',', $iconIDs);
                // Icon–Image relationship
                $stmt = $conn->prepare("
                    DELETE FROM Conn_img_icon 
                    WHERE ID_Icon IN ($inIcons)
                ");
                $stmt->execute();
                $stmt->close();
            }
            
            // c) Delete the icons
            $stmt = $conn->prepare("
                DELETE FROM Icons 
                WHERE ID_PDF IN ($inList)
            ");
            $stmt->execute();
            $stmt->close();
        }
        
        // 3) Delete the PDF records belonging to the project
        $stmt = $conn->prepare("
            DELETE FROM PDFs 
            WHERE ID_Project = ?
        ");
        $stmt->bind_param("i", $projectID);
        $stmt->execute();
        $stmt->close();
        
        // 4) Delete the PDF files from the server
        foreach ($pdfFilePaths as $pdfPath) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $pdfPath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // 5) Get the ID and File_Path information of the images belonging to the project
        $stmt = $conn->prepare("
            SELECT ID_Image, File_Path 
            FROM Images 
            WHERE ID_Project = ?
        ");
        $stmt->bind_param("i", $projectID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $imgIDs = [];
        $imgFilePaths = [];
        while ($row = $result->fetch_assoc()) {
            $imgIDs[]       = $row['ID_Image'];
            $imgFilePaths[] = $row['File_Path'];
        }
        $stmt->close();
        
        // 6) Delete the icon–image (Conn_img_icon) records linked to these images
        if (count($imgIDs) > 0) {
            $inImgs = implode(',', $imgIDs);
            $stmt = $conn->prepare("
                DELETE FROM Conn_img_icon 
                WHERE ID_Image IN ($inImgs)
            ");
            $stmt->execute();
            $stmt->close();
        }
        
        // 7) Delete the image records (Images table)
        $stmt = $conn->prepare("
            DELETE FROM Images 
            WHERE ID_Project = ?
        ");
        $stmt->bind_param("i", $projectID);
        $stmt->execute();
        $stmt->close();
        
        // 8) Delete the image files from the server
        foreach ($imgFilePaths as $imgPath) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imgPath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // 9) Finally, delete the project from the Projects table
        $stmt = $conn->prepare("
            DELETE FROM Projects 
            WHERE ID_Project = ?
        ");
        $stmt->bind_param("i", $projectID);
        $stmt->execute();
        $stmt->close();
        
        // Transaction commit
        $conn->commit();
        
        $response["status"] = "success";
        $response["message"] = "Project and all related files successfully deleted.";
    } catch (Exception $e) {
        $conn->rollback();
        $response["message"] = "Transaction failed: " . $e->getMessage();
    }
} else {
    $response["message"] = "Invalid request: projectID missing.";
}

echo json_encode($response);
exit;
?>
