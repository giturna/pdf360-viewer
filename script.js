/* -- 1) Loading PDF with PDF.js -- */

// Global variables
let pdfDoc = null;         // Reference to the loaded PDF
let currentPage = 1;       // Currently displaying only one page
let pdfScale = 1.0;        // Scale (zoom) factor
const pdfCanvas = document.getElementById('pdfCanvas');
const pdfCtx = pdfCanvas.getContext('2d');

let form_div = document.getElementById("formular");
let projectName = document.getElementById("selectedProject");
form_div.style.display = "none";

// Some basic settings for PDF.js
pdfjsLib.GlobalWorkerOptions.workerSrc = 
  'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.worker.min.js';

// PDF upload button
document.getElementById('loadPdfBtn').addEventListener('click', async () => {
  const fileInput = document.getElementById('pdfInput');
  if (!fileInput.files || fileInput.files.length === 0) {
    alert("Bitte wählen Sie eine PDF-Datei aus.");
    return;
  }

  const file = fileInput.files[0];
  const fileReader = new FileReader();
  
  fileReader.onload = async function() {
    const typedarray = new Uint8Array(this.result);
    // Load the PDF into memory with PDF.js
    pdfDoc = await pdfjsLib.getDocument(typedarray).promise;
    // Render the first page for now
    renderPage(currentPage);
  };
  fileReader.readAsArrayBuffer(file);
});

async function renderPage(pageNum) {
  if (!pdfDoc) return;
  const page = await pdfDoc.getPage(pageNum);

  // Get original dimensions (with scale 1)
  let originalViewport = page.getViewport({ scale: pdfScale });
  const fixedWidth = 1400; // Fixed width
  let scaleFactor = fixedWidth / originalViewport.width; // Scale factor
  let viewport = page.getViewport({ scale: scaleFactor });

  // Adjust the canvas according to the new dimensions
  pdfCanvas.width = viewport.width;
  pdfCanvas.height = viewport.height;

  const renderContext = {
    canvasContext: pdfCtx,
    viewport: viewport
  };
  await page.render(renderContext).promise;
}

/* -- 2) Adding a 360° Icon to the PDF (Click to Add instead of Drag & Drop) -- */

let current360ImageData = null; // Stores the data URL of the selected 360° image

// Convert the selected 360° image file to base64 (or blob URL)
document.getElementById('imgInput').addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(evt) {
    current360ImageData = evt.target.result;  // base64 data URL
  };
  reader.readAsDataURL(file);
});

function convertImageUrlToBase64(imageUrl) {
  // 1) fetch: Retrieves the file from the server
  return fetch(imageUrl)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Resmi çekerken hata oluştu: " + response.statusText);
      }
      // 2) Convert to Blob object
      return response.blob();
    })
    .then((blob) => {
      // 3) Read the Blob as base64 using FileReader
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onloadend = () => {
          // When the FileReader operation finishes, return the base64 data with resolve
          resolve(reader.result);
        };
        
        reader.onerror = () => {
          reject("Resim okunurken hata oluştu.");
        };
        
        reader.readAsDataURL(blob);
      });
    });
}
