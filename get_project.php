<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';

$dbName = "360cams";
$conn = db_connect($dbName);

$response = ["status" => "error", "message" => "Invalid request"];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["projectID"])) {
    $projectID = intval($_GET["projectID"]);

    // 1) Does this ID exist in the Projects table?
    $stmtP = $conn->prepare("
        SELECT Name AS projectName, ID_Project
        FROM Projects
        WHERE ID_Project = ?
        LIMIT 1
    ");
    $stmtP->bind_param("i", $projectID);
    $stmtP->execute();
    $resultP = $stmtP->get_result();
    if ($resultP->num_rows === 0) {
        // Project not found
        $response["message"] = "No project found with this ID.";
        echo json_encode($response);
        exit;
    }
    // Project record exists
    $projRow = $resultP->fetch_assoc();
    $stmtP->close();

    // Initially add the project information to JSON
    $response["projectID"]   = $projRow["ID_Project"];
    $response["projectName"] = $projRow["projectName"];

    // 2) Get ALL PDF records belonging to the project
    $stmtPDF = $conn->prepare("
        SELECT ID_PDF, File_Path, Title 
        FROM PDFs
        WHERE ID_Project = ?
    ");
    $stmtPDF->bind_param("i", $projectID);
    $stmtPDF->execute();
    $resPDF = $stmtPDF->get_result();

    // 'pdfs' array
    $pdfs = [];
    while ($rowPDF = $resPDF->fetch_assoc()) {
        $pdfs[] = [
            "pdfID"   => $rowPDF["ID_PDF"],
            "pdfPath" => $rowPDF["File_Path"],
            "pdfTitle" => $rowPDF["Title"]
        ];
    }
    $stmtPDF->close();

    if (count($pdfs) === 0) {
        // There are NO PDFs in this project => 'no_pdf' situation
        $response["status"]  = "no_pdf";
        $response["message"] = "No PDF found for this project.";
        $response["pdfs"]    = [];
        echo json_encode($response);
        exit;
    }

    // 3) If PDFs are found => return 'success' and include the pdfs array
    $response["status"]  = "success";
    $response["message"] = "Project data loaded";
    $response["pdfs"]    = $pdfs;

    // 4) ICONS + IMAGES:
    // Some users want separate icons for each PDF, others want them project-wide.
    // In your current logic, the 'Icons' table has an ID_PDF field,
    // along with 'Conn_img_icon' + 'Images'.
    // Below, we put the icons INTO A SINGLE array.
    // Each icon has 'iconID', 'x_ratio', 'y_ratio', 'ID_PDF', 'icon_images'...
    // You can filter by PDF on the front-end if you like.

    $stmtIcons = $conn->prepare("
        SELECT 
            ic.ID_Icon, 
            ic.x_ratio, 
            ic.y_ratio,
            ic.ID_PDF,
            co.ID_Image, 
            im.File_Path AS imagePath
        FROM Icons ic
        INNER JOIN Conn_img_icon co ON co.ID_Icon = ic.ID_Icon
        INNER JOIN Images im ON co.ID_Image = im.ID_Image
        WHERE ic.ID_PDF IN (
            SELECT ID_PDF FROM PDFs WHERE ID_Project = ?
        )
        ORDER BY ic.ID_Icon
    ");
    $stmtIcons->bind_param("i", $projectID);
    $stmtIcons->execute();
    $iconRes = $stmtIcons->get_result();

    // As a query result, one icon may have multiple images => group them:
    $iconsMap = [];
    while ($row = $iconRes->fetch_assoc()) {
        $iconID = $row["ID_Icon"];
        if (!isset($iconsMap[$iconID])) {
            $iconsMap[$iconID] = [
                "iconID"      => $iconID,
                "x_ratio"     => $row["x_ratio"],
                "y_ratio"     => $row["y_ratio"],
                "pdfID"       => $row["ID_PDF"],
                "icon_images" => []
            ];
        }
        $iconsMap[$iconID]["icon_images"][] = [
            "imageID"   => $row["ID_Image"],
            "imagePath" => $row["imagePath"]
        ];
    }
    $stmtIcons->close();

    // iconsMap => regular array
    $icons = array_values($iconsMap);

    // 5) Add the icons to the JSON
    $response["icons"] = $icons;

    // Result
    echo json_encode($response);
    exit;

} else {
    // Incorrect request or missing projectID parameter
    echo json_encode($response);
    exit;
}
?>
