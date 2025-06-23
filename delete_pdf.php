<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';

$conn = db_connect("360cams");
$response = ["status"=>"error","message"=>"PDF deletion failed!"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pdfID"])) {
    $pdfID = intval($_POST["pdfID"]);

    $conn->begin_transaction();
    try {
        // 1) Get the file path of this PDF
        $stmt = $conn->prepare("
            SELECT File_Path 
            FROM PDFs
            WHERE ID_PDF = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $pdfID);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0) {
            throw new Exception("PDF not found or already deleted.");
        }
        $row = $res->fetch_assoc();
        $pdfFilePath = $row["File_Path"];
        $stmt->close();

        // 2) Find the icons linked to this PDF
        $stmt = $conn->prepare("
            SELECT ID_Icon
            FROM Icons
            WHERE ID_PDF = ?
        ");
        $stmt->bind_param("i", $pdfID);
        $stmt->execute();
        $resIcons = $stmt->get_result();

        $iconIDs = [];
        while ($iconRow = $resIcons->fetch_assoc()) {
            $iconIDs[] = $iconRow["ID_Icon"];
        }
        $stmt->close();

        if (count($iconIDs) > 0) {
            // 2A) Find which images these icons are using
            $inIcons = implode(',', $iconIDs); // like "12,14,15"

            // Fetch all imageIDs (separate SELECT):
            $stmt = $conn->prepare("
                SELECT DISTINCT ID_Image 
                FROM Conn_img_icon
                WHERE ID_Icon IN ($inIcons)
            ");
            $stmt->execute();
            $resImg = $stmt->get_result();

            $imageIDs = [];
            while ($imgRow = $resImg->fetch_assoc()) {
                $imageIDs[] = $imgRow["ID_Image"];
            }
            $stmt->close();

            // 2B) Delete the records of these icons from Conn_img_icon
            $stmt = $conn->prepare("
                DELETE FROM Conn_img_icon
                WHERE ID_Icon IN ($inIcons)
            ");
            $stmt->execute();
            $stmt->close();

            // 2C) Delete these icons from the Icons table
            $stmt = $conn->prepare("
                DELETE FROM Icons
                WHERE ID_PDF = ?
            ");
            $stmt->bind_param("i", $pdfID);
            $stmt->execute();
            $stmt->close();

            // 2D) For each image in this imageIDs list:
            //     If it no longer has a relationship with any icon (no Conn_img_icon),
            //     Delete it from the Images table and unlink it from the server.
            foreach ($imageIDs as $imgID) {
                // Is it still in Conn_img_icon?
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as cnt
                    FROM Conn_img_icon
                    WHERE ID_Image = ?
                ");
                $stmt->bind_param("i", $imgID);
                $stmt->execute();
                $resC = $stmt->get_result();
                $cntRow = $resC->fetch_assoc();
                $countUsage = $cntRow["cnt"];
                $resC->close();
                $stmt->close();

                if (intval($countUsage) === 0) {
                    // Not used by any other icon => delete
                    // 1) Get the file path
                    $stmt = $conn->prepare("
                        SELECT File_Path 
                        FROM Images
                        WHERE ID_Image = ?
                        LIMIT 1
                    ");
                    $stmt->bind_param("i", $imgID);
                    $stmt->execute();
                    $imgRes = $stmt->get_result();
                    if ($imgRes->num_rows > 0) {
                        $imgRow = $imgRes->fetch_assoc();
                        $imgPath = $imgRow["File_Path"];
                    } else {
                        $imgPath = null;
                    }
                    $stmt->close();

                    // 2) Delete from the Images table
                    $stmt = $conn->prepare("
                        DELETE FROM Images
                        WHERE ID_Image = ?
                    ");
                    $stmt->bind_param("i", $imgID);
                    $stmt->execute();
                    $stmt->close();

                    // 3) Delete the file from the server
                    if ($imgPath) {
                        $fullImgPath = $_SERVER['DOCUMENT_ROOT'] . $imgPath;
                        if (file_exists($fullImgPath)) {
                            unlink($fullImgPath);
                        }
                    }
                }
            }
        }

        // 3) Delete this PDF record from the PDFs table
        $stmt = $conn->prepare("
            DELETE FROM PDFs
            WHERE ID_PDF = ?
        ");
        $stmt->bind_param("i", $pdfID);
        $stmt->execute();
        $stmt->close();

        // 4) Delete the PDF file from the server
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $pdfFilePath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $conn->commit();

        $response["status"]  = "success";
        $response["message"] = "PDF and associated icons/res images successfully deleted.";
    } catch (Exception $e) {
        $conn->rollback();
        $response["message"] = "Transaction failed: " . $e->getMessage();
    }
} else {
    $response["message"] = "Invalid request: pdfID missing.";
}

echo json_encode($response);
exit;
?>
