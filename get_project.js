/********************************************************
 * get_project.js
 * 
 * Purpose:
 *  - Retrieves all PDFs (pdfs[]) and icons (icons[]) belonging to a project
 *    via get_project.php.
 *  - Displays buttons on the PDF bar (pdfBar).
 *  - On click, draws on the canvas using loadThisPdf(pdfID, pdfPath).
 *  - Filters and adds icons based on pdfID.
 ********************************************************/

function loadProject(projectID) {
  fetch("get_project.php?projectID=" + projectID)
    .then(response => response.json())
    .then(jsonData => {
      console.log("loadProject =>", jsonData);

      // There is an error or no project found
      if (jsonData.status !== "success" && jsonData.status !== "no_pdf") {
        alert("Error: " + (jsonData.message || "Unknown error"));
        return;
      }

      // Update the global variables
      currentProjectId = jsonData.projectID;
      
      const projectNameEl = document.getElementById("selectedProject");
      if (projectNameEl) {
        projectNameEl.innerText = "Aktives Projekt: " + (jsonData.projectName || "");
      }

     // "no_pdf" => No PDFs in the project
      if (jsonData.status === "no_pdf") {
        // Clear the canvas
        pdfDoc = null;
        pdfCanvas.width = 600;
        pdfCanvas.height = 300;
        document.getElementById("iconContainer").innerHTML = "";
        
        // Show the PDF bar as empty
        renderPdfBar([]);
        form_div.style.display = "";
        return;
      }

      // success => "pdfs" and "icons" exist
      // pdfs => ALL PDFs in a project
      // icons => ALL icons in a project
      renderPdfBar(jsonData.pdfs, jsonData.icons);

      // Automatically load the first PDF
      if (jsonData.pdfs.length > 0) {
        const firstPdf = jsonData.pdfs[0];
        currentPdfId = firstPdf;
        loadThisPdf(firstPdf.pdfID, firstPdf.pdfPath, jsonData.icons);
      }

    })
    .catch(err => {
      console.error("loadProject error:", err);
      alert("An error occurred while loading the project.");
    });
}

/**
 * renderPdfBar: Adds project PDFs as buttons to the pdfBar div.
 * Calls loadThisPdf(...) when clicked.
 */
function renderPdfBar(pdfs, allIcons) {
  const pdfBar = document.getElementById("pdfBar");
  if (!pdfBar) {
    console.warn("renderPdfBar: #pdfBar element not found.");
    return;
  }

  pdfBar.innerHTML = ""; // Clear previous buttons first

  pdfs.forEach(pdf => {
    const btn = document.createElement("button");
    btn.innerText = "PDF #" + pdf.pdfID;
    btn.addEventListener("click", () => {
      loadThisPdf(pdf.pdfID, pdf.pdfPath, allIcons);
    });
    pdfBar.appendChild(btn);
  });
}

/**
 * loadThisPdf:
 *  - Clears the canvas, 
 *  - Renders the selected PDF using PDF.js,
 *  - Filters and loads the icons belonging to this PDF from allIcons.
 */
function loadThisPdf(pdfID, pdfPath, allIcons) {
  // 1) Clear the canvas
  pdfDoc = null;
  pdfCanvas.width = 600;
  pdfCanvas.height = 300;
  document.getElementById("iconContainer").innerHTML = "";
  form_div.style.display = "";

  // 2) Load the PDF
  loadPdf(pdfPath).then(() => {
    console.log("PDF loaded =>", pdfPath);
    document.getElementById("iconContainer").innerHTML = "";
    currentPdfId = pdfID;
    
    console.log("Current PDF ID:", currentPdfId);
    
    // 3) Add the icons belonging to this PDF to the screen
    const iconsForThisPdf = allIcons.filter(icon => icon.pdfID === pdfID);
    iconsForThisPdf.forEach(icon => {
      // Get the first image from each icon's 'icon_images' array
      let firstImg = undefined;
      if (icon.icon_images && icon.icon_images.length > 0) {
        firstImg = icon.icon_images[0].imagePath; 
      }
      // "loadIcon" => existing function
      loadIcon(icon.iconID, icon.x_ratio, icon.y_ratio, firstImg);
    });

  })
  .catch(err => {
    console.error("loadThisPdf error:", err);
    alert("An error occurred while loading the PDF: " + err);
  });
}

function loadPdf(pdfUrl) {
    return pdfjsLib.getDocument(pdfUrl).promise
    .then(function(pdf) {
        pdfDoc = pdf;
        return renderPage(currentPage).then(() => {
            console.log("Canvas width, heigth:", pdfCanvas.width, pdfCanvas.height);
            form_div.style.display = "";
        });
    })
    .catch(function(error) {
        console.error("Error: Error while loading PDF ", error);
    });
}


// Icon creation function
function loadIcon(iconID, xRatio, yRatio, imagePath) {
    const iconContainer = document.getElementById('iconContainer');

    // Calculate pixel value from the ratio
    let px = xRatio * pdfCanvas.width;
    let py = yRatio * pdfCanvas.height;

    const iconEl = document.createElement('img');
    iconEl.src = "360camImg/webcam.png";
    iconEl.className = "pdf-icon";

    iconEl.style.left = (pdfCanvas.offsetLeft + px - 16) + "px";
    iconEl.style.top  = (pdfCanvas.offsetTop + py - 16) + "px";
    iconEl.style.position = "absolute";
    iconEl.dataset.iconId = iconID;

    icon_events(imagePath, iconEl);
    iconContainer.appendChild(iconEl);
}