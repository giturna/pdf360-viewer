function deleteProject(projectID) {
    let fd = new FormData();
    fd.append("projectID", projectID);

    fetch("del_project.php", {
        method: "POST",
        body: fd
    })
    .then(response => response.json())
    .then(data => {
        console.log("deleteProject =>", data);
        if (data.status === "success") {
            alert("Projekt erfolgreich gelöscht!");
            window.location.reload();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(err => {
        console.error("deleteProject error:", err);
        alert("Fehler beim Löschen");
    });
}

function onDeleteProject() {
    const projectDropdown = document.getElementById("projectDropdown");
    if (!projectDropdown) {
        alert("Dropdown not found!");
        return;
    }
    const selectedProjectID = projectDropdown.value;
    if (!selectedProjectID) {
        alert("No project selected!");
        return;
    }
    if (!confirm("Wollen Sie dieses Projekt wirklich löschen?")) return;

    // Delete the selected ID
    deleteProject(parseInt(selectedProjectID));
}
