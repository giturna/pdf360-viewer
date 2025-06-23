let currentProjectId = null;

function createProject(event){
    event.preventDefault();

    let formData = new FormData(document.getElementById("form_create_project"));

    let projectNameInput = document.getElementById("projectNameInput");
    let errorMessage = document.getElementById("error-message");

    // It used to be "projectListContainer", now it's a dropdown
    const projectDropdown = document.getElementById("projectDropdown");
    // Get all <option> tags
    let projectOptions = projectDropdown.querySelectorAll("option");

    let exists = false;

    // Empty project name check
    if (!projectNameInput.value.trim()) {
        projectNameInput.style.border = "2px solid red";
        errorMessage.innerText = "";
        return;
    }

    // Check if the entered project name has already been added
    projectOptions.forEach(opt => {
        if (opt.textContent.toLowerCase() === projectNameInput.value.trim().toLowerCase()) {
            exists = true;
        }
    });
    if (exists) {
        projectNameInput.style.border = "2px solid red";
        errorMessage.innerText = "Ein Projekt mit diesem Namen existiert bereits. Bitte anderen Projektnamen eingeben.";
        return;
    }

    // Reset the boundaries
    projectNameInput.style.border = "1px solid #ccc";

    // Request to create a new project on the server
    fetch("upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log("Response:", data);
        try {
            let jsonData = JSON.parse(data);
            console.log("JSON Object:", jsonData);

            if (jsonData.status === "success") {
                currentProjectId = jsonData.projectID;

                // Update the project list after creating a project
                //loadProjectList();

                let opt = document.createElement("option");
                opt.value = currentProjectId;
                opt.textContent = jsonData.projectName;
                projectDropdown.appendChild(opt);
                projectDropdown.value = currentProjectId;
                loadProject(currentProjectId);

                // Other options (canvas cleaning etc.)
                pdfDoc = null;
                pdfCanvas.width = 600;
                pdfCanvas.height = 300;
                document.getElementById("iconContainer").innerHTML = "";

            } else {
                alert("Error: " + jsonData.message);
            }
        } catch (e) {
            console.error("JSON Parse Error:", e, "Response:", data);
        }
    })
    .catch(error => console.error("Fetch Error:", error));
}



function uploadIcon(event, x_ratio, y_ratio, pdfID, imgID) {
    event.preventDefault();
    console.log("uploadIcon called:", x_ratio, y_ratio, pdfID, imgID);

    let formData = new FormData();
    formData.append("x_ratio", x_ratio);
    formData.append("y_ratio", y_ratio);
    formData.append("pdfID", pdfID);
    formData.append("imgID", imgID);

    return fetch("upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log("Response:", data);
        try {
            let jsonData = JSON.parse(data);
            console.log("JSON Object:", jsonData);

            if (jsonData.status === "success") {
                return jsonData.iconID;
            } else {
                alert("Error: " + jsonData.message);
                throw new Error("Icon upload failed");
            }
        } catch (e) {
            console.error("JSON Parse Error:", e, "Response:", data);
            throw e;
        }
    });
}


function updateIcon(event, x_pixel, y_pixel, iconID) {
    event.preventDefault();
    // left and top are string values like "150px", convert them to numbers first.
    let ratioX = (parseFloat(x_pixel) + 16 - pdfCanvas.offsetLeft) / pdfCanvas.width;
    let ratioY = (parseFloat(y_pixel) + 16 - pdfCanvas.offsetTop) / pdfCanvas.height;
    let canvasWidth = pdfCanvas.offsetWidth;
    let canvasHeight = pdfCanvas.offsetHeight;

    let formData = new FormData();
    formData.append("x_ratio", ratioX);
    formData.append("y_ratio", ratioY);
    formData.append("iconID", iconID);

    fetch("upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log("Update Icon Response:", data);
    })
    .catch(error => console.error("Fetch Error:", error));
}



let selectedImageId = null;
function uploadImg(event) {
    event.preventDefault(); // Prevents page refresh

    let formData = new FormData(document.getElementById("form_img_upload"));
    formData.append("projectID", currentProjectId);

    fetch("upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())  // Takes response as a text
    .then(data => {
        console.log("Response:", data);  // To see the response

        try {
            let jsonData = JSON.parse(data);  // Parses as JSON
            console.log("JSON Object:", jsonData);

            if (jsonData.status === "success") {
                //alert("360° Image uploaded successfully!");
                console.log("Uploaded Image Path:", jsonData.imagePath);
                selectedImageId = jsonData.imageID;
                console.log("Selected Image ID:", selectedImageId);
            } else {
                alert("Error: " + jsonData.message);
            }
        } catch (e) {
            console.error("JSON Parse Error:", e, "Response:", data);
        }
    })
    .catch(error => console.error("Fetch Error:", error));
}
  
  
let currentPdfId = null;
function uploadPdf(event) {
    event.preventDefault(); // Prevents page refresh

    let formData = new FormData(document.getElementById("form_pdf_upload"));
    formData.append("projectID", currentProjectId);

    fetch("upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log("Response:", data);

        try {
            let jsonData = JSON.parse(data);
            console.log("JSON Object: ", jsonData);

            if (jsonData.status === "success") {
                //alert("PDF successfully uploaded!");
                currentPdfId = jsonData.pdfID;
                loadPdftoCanvas(jsonData.pdfID, jsonData.pdfPath);
                console.log("Current PDF ID:", currentPdfId);

                //is_pdf_on_canvas();
            } else {
                alert("Error: " + jsonData.message);
            }
        } catch (e) {
            console.error("JSON Parse Error:", e, "Response:", data);
        }
    })
    .catch(error => console.error("Fetch Error:", error));
}


/**
 * loadIconsForPdf:
 *  - Fetches icons for a specific pdfID from the server (get_icons_for_pdf.php).
 *  - Reads the 'icons' array from the JSON data and adds each icon with a function like loadIcon.
 */
function loadPdftoCanvas(pdfID, pdfPath) {
    fetch("get_icons_for_pdf.php?pdfID=" + pdfID)
    .then(response => response.json())
    .then(data => {
        console.log("loadIconsForPdf =>", data);
        if (data.status === "success") {
            document.getElementById("iconContainer").innerHTML = "";
            // Place each icon on the screen
            // adds button in the pdf bar
            allIcons = data.icons;
            const btn = document.createElement("button");
            btn.innerText = "PDF #" + pdfID;
            btn.addEventListener("click", () => {
                loadThisPdf(pdfID, pdfPath, allIcons);
            });
            pdfBar.appendChild(btn);
        } else {
            console.warn("No icons or error:", data.message);
        }
    })
    .catch(error => {
        console.error("loadIconsForPdf Error:", error);
    });
}
