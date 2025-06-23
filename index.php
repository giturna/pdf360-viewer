<?php
//phpinfo();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'hilfsfunktionen.php';

$dbName = "360cams";
// Create connection
$conn = db_connect($dbName);

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>360cams</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <header>
        
    </header>
    <main>
        <div class="main_container">
            <div>
                <div id="createProject">
                    <h4>Projekt Erstellen</h4>
                    <form id="form_create_project">
                        <label>Projekt name: </label>
                        <input type="text" id="projectNameInput" name="project_name">
                        <p id="error-message" style="color: red;"></p>
                        <button id="createProjectBtn" onclick="createProject(event)">Erstellen</button>
                    </form>
                </div>
                <div id="loadProject">
                    <h4>Projekt laden</h4>
                    <select id="projectDropdown">
                        <option value="">(Projekt auswählen)</option>
                        <!-- Projects will be added here as <option> elements -->
                    </select>
                    <button id="deleteSelectedProjectBtn" onclick="onDeleteProject()">Ausgewähltes Projekt löschen</button>
                </div>

            </div>
            <div id="formular">
                <div>
                    <p id="selectedProject"></p>
                </div>
                <!-- PDF file selection -->
                <form id="form_pdf_upload">
                    <label>PDF auswählen:</label>
                    <input type="file" id="pdfInput" name="pdfFile" accept="application/pdf"/>
                    <button id="loadPdfBtn" onclick="uploadPdf(event)">PDF hochladen</button>
                </form>
                
                <br/><br/>

                <!-- 360° image selection (user can upload from here) -->
                <form id="form_img_upload">
                    <label>360-Grad-Bild auswählen:</label>
                    <input type="file" id="imgInput" name="imgFile" accept="image/jpeg,image/png"/>
                    <button id="addIconBtn" onclick="uploadImg(event)">360-Symbol zu PDF hinzufügen</button>
                </form>

                <br/><br/>

                <div id="pdfBar" style="margin-bottom: 10px;"></div>
                <div id="deletePdf" style="margin-bottom: 10px;">
                    <button id="deletePdfBtn">PDF Löschen</button>
                </div>

                <!-- Canvas to render the PDF -->
                <p id="uploadResult"></p>
                <canvas id="pdfCanvas"></canvas>

                <!-- Layer to place icons over the PDF -->
                <div id="iconContainer"></div>

                <!-- 360° Modal (Overlay) -->
                <div id="modalOverlay">
                    <div id="modalContent">
                        <!-- Left Sidebar: Image list + add new image -->
                        <div id="modalSidebar">
                            <h4>Neues Bild auswählen</h4>
                            <input type="file" id="iconImageInput" accept="image/jpeg,image/png" />
                            <button id="addImageBtn">Bild hinzufügen</button>
                    
                            <hr>
                            <div id="imageButtons">
                                <div id="iconImageList"></div> <!-- List of other images linked to the icon (buttons) -->
                                <div id="delImageList"></div>
                            </div>
                            
                        </div>

                        <!-- Right side: Three.js Viewer Area -->
                        <div id="threeContainer">
                            <button id="closeBtn">X</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </main>

    <!-- PDF.js (CDN) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.worker.min.js"></script>

    <!-- Third-party library: Three.js (v0.136.0) -->
    <script src="https://cdn.jsdelivr.net/npm/three@0.136.0/build/three.min.js"></script>

    <!-- OrbitControls (same Three.js version) -->
    <script src="https://cdn.jsdelivr.net/npm/three@0.136.0/examples/js/controls/OrbitControls.js"></script>

    <!-- Custom Scripts -->
    <script src="script.js"></script>
    <script src="upload.js"></script>
    <script src="icon.js"></script>
    <script src="modal.js"></script>
    <script src="get_project.js"></script>
    <script src="get_project_list.js"></script>
    <script src="del_project.js"></script>
    <script src="delete_pdf.js"></script>
    <script src="del_icon.js"></script>

</body>
<?php
$conn->close();
?>
</html>
