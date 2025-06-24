<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('BASE_DIR', __DIR__);
include 'hilfsfunktionen.php';

$dbName = "360cams";
$conn = db_connect($dbName);

$response = ["status" => "error", "message" => "Upload not successful!", "pdfPath" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST["project_name"])){
        create_project($conn, $response);
    }
    if(isset($_FILES["pdfFile"])) {
        upload_pdf($conn, $response);
    }
    if(isset($_FILES["imgFile"])){
        upload_img($conn, $response);
    }
    if(isset($_POST["x_ratio"]) && isset($_POST["y_ratio"]) && isset($_POST["pdfID"]) && isset($_POST["imgID"])){
        upload_icon($conn, $response);
    }
    if(isset($_POST["x_ratio"]) && isset($_POST["y_ratio"]) && isset($_POST["iconID"])){
        update_icon($conn, $response);
    }
}

echo json_encode($response);
exit;

function create_project($conn, &$response){
    $projectName = $_POST['project_name'];

    $stmt = $conn->prepare("INSERT INTO Projects (Name) VALUES (?)");
    $stmt->bind_param("s", $projectName);

    if ($stmt->execute()) {
        $projectID = $conn->insert_id;

        $stmt2 = $conn->prepare("SELECT Name, ID_Project FROM Projects WHERE ID_Project = ?");
        $stmt2->bind_param("i", $projectID);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if($result2->num_rows > 0) {
            $proj = $result2->fetch_assoc();
            $response["projectName"] = $proj["Name"];
        } else {
            $response["message"] = "No project found with this ID.";
        }

        $response["status"] = "success";
        $response["message"] = "Project successfully created";
        $response["projectID"] = $projectID;
    } else {
        $response["message"] = "Error while adding to database: " . $stmt->error;
    }
    $stmt->close();
    $stmt2->close();
}


function upload_pdf($conn, &$response){
    $projectID = intval($_POST['projectID']);
    $fileTmpPath = $_FILES['pdfFile']['tmp_name'];
    $fileName = $_FILES['pdfFile']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== "pdf") {
        $response["message"] = "Error: Only PDF files are accepted";
        return;
    }

    $uploadDir = BASE_DIR . '/uploads/pdfs/';
    $newFileName = time() . "_" . basename($fileName);
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $filePathDb = "/uploads/pdfs/" . $newFileName;
        
        $stmt = $conn->prepare("INSERT INTO PDFs (Title, File_Path, ID_Project) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $fileName, $filePathDb, $projectID);

        if ($stmt->execute()) {
            $pdfID = $conn->insert_id;
            $response["status"] = "success";
            $response["message"] = "PDF successfully uploaded";
            $response["pdfPath"] = $filePathDb;
            $response["pdfID"] = $pdfID;
            $response["projectID"] = $projectID;
        } else {
            $response["message"] = "Error while adding to database: " . $stmt->error;
        }
        $stmt->close();
    }
}

function upload_img($conn, &$response){
    $projectID = intval($_POST['projectID']);
    $fileTmpPath = $_FILES['imgFile']['tmp_name'];
    $fileName = $_FILES['imgFile']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, ["png", "jpg", "jpeg"])) {
        $response["message"] = "Error: Only PNG or JPG files are accepted.";
        return;
    }

    $uploadDir = BASE_DIR . '/uploads/360images/';
    $newFileName = time() . "_" . basename($fileName);
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $filePathDb = "/uploads/360images/" . $newFileName;
        
        $stmt = $conn->prepare("INSERT INTO Images (Title, File_Path, ID_Project) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $fileName, $filePathDb, $projectID);

        if ($stmt->execute()) {
            $imageID = $conn->insert_id;
            $response["status"] = "success";
            $response["message"] = "Image successfully uploaded!";
            $response["imagePath"] = $filePathDb;
            $response["imageID"] = $imageID;
        } else {
            $response["message"] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response["message"] = "move_uploaded_file() failed. Check file permissions!";
    }
}

function upload_icon($conn, &$response){
    $x_ratio = $_POST['x_ratio'];
    $y_ratio = $_POST['y_ratio'];
    $pdfID = $_POST['pdfID'];
    $imgID = $_POST['imgID'];
    
    $stmt = $conn->prepare("INSERT INTO Icons (x_ratio, y_ratio, ID_PDF) VALUES (?, ?, ?)");
    $stmt->bind_param("ddi", $x_ratio, $y_ratio, $pdfID);

    if ($stmt->execute()) {
        $iconID = $conn->insert_id;
        $response["status"] = "success";
        $response["message"] = "Icon successfully saved";
        $response["iconID"] = $iconID;
    } else {
        $response["message"] = "Error while adding to database: " . $stmt->error;
    }

    $stmt->close();

    $stmt2 = $conn->prepare("INSERT INTO Conn_img_icon (ID_Icon, ID_Image) VALUES (?, ?)");
    $stmt2->bind_param("ii", $iconID, $imgID);

    if ($stmt2->execute()) {
        $connIconImgID = $conn->insert_id;
        $response["status"] = "success";
        $response["message"] = "Connection successfully saved";
        $response["connIconImgID"] = $connIconImgID;
    } else {
        $response["message"] = "Error while adding to database: " . $stmt2->error;
    }

    $stmt2->close();
    
}

function update_icon($conn, &$response){
    //error_log("update_icon() function called!");
    $x_ratio = $_POST['x_ratio'];
    $y_ratio = $_POST['y_ratio'];
    $iconID = $_POST['iconID'];
    
    $stmt = $conn->prepare("UPDATE Icons SET x_ratio = ?, y_ratio = ? WHERE ID_Icon = ?");
    $stmt->bind_param("ddi", $x_ratio, $y_ratio, $iconID);

    if ($stmt->execute()) {
        $response["status"] = "success";
        $response["message"] = "Icon successfully updated";
    } else {
        $response["message"] = "Error while updating the database: " . $stmt->error;
    }
    $stmt->close();
}
?>
