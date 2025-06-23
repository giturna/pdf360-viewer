// When "Add 360° Icon to PDF" button is clicked, 
// the user clicks on the canvas to place the icon
document.getElementById('addIconBtn').addEventListener('click', () => {
    if (!pdfDoc) {
        alert("Laden Sie zuerst das PDF hoch.");
        return;
    }
    if (!current360ImageData) {
        alert("Bitte wählen Sie eine 360-Grad-Bilddatei aus.");
        return;
    }
    alert("Das Symbol wird an der ersten Stelle platziert, auf die Sie im PDF-Canvas klicken.");

    // Add an event listener for a single click to place the icon
    const onCanvasClick = (e) => {
        // Calculate click coordinates within the canvas
        const rect = pdfCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Add the icon
        createIcon(e, x, y, current360ImageData);
        
        // Remove the event listener after a single use
        pdfCanvas.removeEventListener('click', onCanvasClick);
    };

    pdfCanvas.addEventListener('click', onCanvasClick);
});
  
/**
 * Function to add an "icon" over the PDF.
 * @param {event} e
 * @param {number} x - X coordinate on the canvas (pixels)
 * @param {number} y - Y coordinate on the canvas (pixels)
 * @param {string} imageData - Data URL of the 360° image
 */
function createIcon(e, x, y, imageData) {
    const iconContainer = document.getElementById('iconContainer');
    
    // 1) Calculate the ratios
    let xRatio = x / pdfCanvas.width;
    let yRatio = y / pdfCanvas.height;

    // 2) Immediately display the icon on the screen
    const iconEl = document.createElement('img');
    iconEl.src = "360camImg/webcam.png";
    iconEl.className = "pdf-icon";

    // To position the icon in pixels
    iconEl.style.left = (pdfCanvas.offsetLeft + x - 16) + "px";
    iconEl.style.top = (pdfCanvas.offsetTop + y - 16) + "px";
    iconEl.style.position = "absolute";
    iconEl.style.cursor = "grab";

    // 3) Save to the database as (xRatio, yRatio)
    uploadIcon(e, xRatio, yRatio, currentPdfId, selectedImageId)
      .then(iconID => {
        iconEl.dataset.iconId = iconID.toString();
        icon_events(imageData, iconEl);
        iconContainer.appendChild(iconEl);
      })
      .catch(error => {
        console.error("Upload icon error:", error);
      });
}


function icon_events(imageData, iconEl){
    // Drag and drop variables
    let isDragging = false;
    let wasDragged = false;
    let startX, startY, offsetX, offsetY;

    // Right click event for deleting the icon
    iconEl.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        let iconID = parseInt(iconEl.dataset.iconId);
        deleteIcon(iconID);
    });

    // Check if its dragging when click
    iconEl.addEventListener('click', (e) => {
        iconEl.style.cursor = "pointer";
        if (wasDragged) {
            wasDragged = false; // The flag is reset, and subsequent clicks will return to normal behavior.
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        let iconID = parseInt(iconEl.dataset.iconId);
        console.log(iconID);
        open360Modal(imageData);
        openIconModal(iconID);
    });

    // Mousedown Event
    iconEl.addEventListener("mousedown", (e) => {
        e.preventDefault();
        isDragging = true;
        wasDragged = false;
        startX = e.pageX;
        startY = e.pageY;

        const rect = iconEl.getBoundingClientRect();
        offsetX = e.pageX - (rect.left + window.scrollX);
        offsetY = e.pageY - (rect.top + window.scrollY);
        iconEl.style.cursor = "grabbing";

        // Mousemove Event
        const onMouseMove = (e) => {
        if (!isDragging) return;

        let dx = e.pageX - startX;
        let dy = e.pageY - startY;
        if (Math.abs(dx) > 5 || Math.abs(dy) > 5) { // If it has been moved more than 5px then its been dragging
            wasDragged = true;
        }

        let newX = e.pageX - offsetX;
        let newY = e.pageY - offsetY;
        
        const canvasLeft = pdfCanvas.offsetLeft;
        const canvasTop = pdfCanvas.offsetTop;
        
        // icon is inside pdfCanvas
        newX = Math.max(canvasLeft, Math.min(newX, canvasLeft + pdfCanvas.offsetWidth - iconEl.clientWidth));
        newY = Math.max(canvasTop, Math.min(newY, canvasTop + pdfCanvas.offsetHeight - iconEl.clientHeight));

        iconEl.style.left = newX -16 + "px";
        iconEl.style.top = newY -16 + "px";
        };

        // Mouseup Event
        const onMouseUp = (e) => {
        isDragging = false;

        // Update icon table in database
        updateIcon(e, iconEl.style.left, iconEl.style.top, parseInt(iconEl.dataset.iconId));

        iconEl.style.cursor = "grab";
        document.removeEventListener("mousemove", onMouseMove);
        document.removeEventListener("mouseup", onMouseUp);
        };

        document.addEventListener("mousemove", onMouseMove);
        document.addEventListener("mouseup", onMouseUp);
    });
}
  