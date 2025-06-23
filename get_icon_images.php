<?php
header('Content-Type: application/json; charset=utf-8');
include 'hilfsfunktionen.php';
$conn = db_connect("360cams");

$response = ["status"=>"error","images"=>[]];

if($_SERVER["REQUEST_METHOD"]=="GET" && isset($_GET["iconID"])) {
    $iconID = intval($_GET["iconID"]);
    
    $stmt = $conn->prepare("SELECT im.ID_Image, im.File_Path, im.Title
                            FROM Conn_img_icon c
                            INNER JOIN Images im ON c.ID_Image = im.ID_Image
                            WHERE c.ID_Icon = ?
                        ");
    $stmt->bind_param("i", $iconID);
    $stmt->execute();
    $res = $stmt->get_result();
    $imgs = [];
    while($row = $res->fetch_assoc()){
        $imgs[] = [
            "imageID"=>$row["ID_Image"],
            "imagePath"=>$row["File_Path"],
            "imageTitle"=>$row["Title"]
        ];
    }
    $stmt->close();
    
    if(count($imgs)>0){
        $response["status"] = "success";
        $response["images"] = $imgs;
    } else {
        $response["message"] = "No images found for this icon.";
        $response["status"] = "error";
    }
}
echo json_encode($response);
exit;
?>
