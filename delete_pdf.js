document.getElementById("deletePdfBtn").addEventListener("click", () => {
    if (!currentPdfId) {
        alert("PDF nicht ausgewählt oder kein PDF im Projekt!");
        return;
    }
    
    if (!confirm("Möchten Sie dieses PDF wirklich löschen?")) {
        return;
    }
    
    let formData = new FormData();
    formData.append("pdfID", currentPdfId);

    fetch("delete_pdf.php", {
        method: "POST",
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        console.log("delete_pdf response =>", data);
        if (data.status === "success") {
            loadProject(currentProjectId);
        } else {
            alert("Error deleting PDF: " + data.message);
        }
    })
    .catch(err => {
        console.error("Delete PDF error:", err);
        alert("Beim Löschen der PDF ist ein Fehler aufgetreten.");
    });
});
