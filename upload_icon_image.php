<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php'; // If the database connection function exists

$dbName = "360cams";
$conn = db_connect($dbName); // Get the connection using the db_connect function


$response = ["status" => "error", "message" => "File upload failed."];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["iconID"]) && isset($_FILES["imgFile"]) && isset($_POST["projectID"])) {
    $iconID = intval($_POST["iconID"]);
    $projectID = $_POST["projectID"];

    // Get the file
    $fileTmpPath = $_FILES['imgFile']['tmp_name'];
    $fileName = $_FILES['imgFile']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Simple format check
    $validExtensions = ["jpg", "jpeg", "png"];
    if (!in_array($fileExtension, $validExtensions)) {
        $response["message"] = "Error: Only JPG/PNG files are accepted.";
        echo json_encode($response);
        exit;
    }

    // Directory where we will save on the server
    $uploadDir = "/var/www/html3/uploads/360images/";

    // Unique file name
    $newFileName = time() . "_" . basename($fileName);
    $destPath = $uploadDir . $newFileName;

    // Move the file to a permanent location on the server
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        // When saving to the database, store the file path as /uploads/360images/...
        $filePathDb = "/uploads/360images/" . $newFileName;
        
        // 1) Add to the Images table
        $stmt = $conn->prepare("INSERT INTO Images (File_Path, Title, ID_Project) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $filePathDb, $newFileName, $projectID);
        if ($stmt->execute()) {
            $imageID = $conn->insert_id;
            $stmt->close();

            // 2) Add to the Conn_img_icon table (icon–image relationship)
            $stmt2 = $conn->prepare("INSERT INTO Conn_img_icon (ID_Icon, ID_Image) VALUES (?, ?)");
            $stmt2->bind_param("ii", $iconID, $imageID);
            if ($stmt2->execute()) {
                $stmt2->close();
                
                // Everything completed successfully
                $response["status"]  = "success";
                $response["message"] = "Image uploaded and linked to icon.";
                $response["imageID"] = $imageID;
                $response["imagePath"] = $filePathDb;
            } else {
                $response["message"] = "Could not link icon & image: " . $stmt2->error;
            }
        } else {
            $response["message"] = "Insert into Images failed: " . $stmt->error;
        }
    } else {
        $response["message"] = "move_uploaded_file() failed. Check file permissions!";
    }
} else {
    $response["message"] = "Invalid request: iconID or imgFile missing.";
}

echo json_encode($response);
exit;
?>
