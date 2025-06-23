<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';

$dbName = "360cams";
$conn = db_connect($dbName);

$response = ["status" => "error", "message" => "No data"];

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["pdfID"])) {
    $pdfID = intval($_GET["pdfID"]);

    $stmt = $conn->prepare("
        SELECT 
            ic.ID_Icon, 
            ic.x_ratio, 
            ic.y_ratio,
            co.ID_Image, 
            im.File_Path AS imagePath
        FROM Icons ic
        INNER JOIN Conn_img_icon co ON co.ID_Icon = ic.ID_Icon
        INNER JOIN Images im ON co.ID_Image = im.ID_Image
        WHERE ic.ID_PDF = ?
        ORDER BY ic.ID_Icon
    ");
    $stmt->bind_param("i", $pdfID);
    $stmt->execute();
    $res = $stmt->get_result();

    $iconsMap = [];
    while ($row = $res->fetch_assoc()) {
        $iconID = $row["ID_Icon"];
        
        if (!isset($iconsMap[$iconID])) {
            $iconsMap[$iconID] = [
                "iconID"   => $iconID,
                "x_ratio"  => floatval($row["x_ratio"]),
                "y_ratio"  => floatval($row["y_ratio"]),
                "icon_images" => []
            ];
        }
        
        $iconsMap[$iconID]["icon_images"][] = [
            "imageID"   => $row["ID_Image"],
            "imagePath" => $row["imagePath"]
        ];
    }
    $stmt->close();

    // icons array
    $icons = array_values($iconsMap);

    if (count($icons) > 0) {
        $response["status"]  = "success";
        $response["icons"]   = $icons;
    } else {
        $response["status"]  = "success";
        $response["icons"]   = [];
    }
}

echo json_encode($response);
exit;
?>
