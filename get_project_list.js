const projectDropdown = document.getElementById("projectDropdown");

function loadProjectList() {
    fetch("get_project_list.php", {
        method: "GET"
    })
    .then(response => response.text())
    .then(data => {
        console.log("Response:", data);

        try {
            let jsonData = JSON.parse(data);
            console.log("JSON Object: ", jsonData);

            if (jsonData.status === "success") {
                // Clear the dropdown first
                cleanProjectDropdown();

                // Then add the projects
                jsonData.projects.forEach(project => {
                    showProjectOption(project.projectID, project.projectName);
                });
            } else {
                console.warn("Error or no projects:", jsonData.message);
            }
        } catch(e) {
            console.error("JSON Parse Error:", e, "Response:", data);
        }
    })
    .catch(error => console.error("Fetch Error:", error));
}

function cleanProjectDropdown() {
    if (projectDropdown) {
        projectDropdown.innerHTML = '<option value="">(Projekt auswählen)</option>';  // Delete all <option> elements
    } else {
        console.error("projectDropdown not found.");
        return;
    }
}

function showProjectOption(projectID, projectName) {
    // Create an <option>
    const opt = document.createElement("option");
    opt.value = projectID;
    opt.textContent = projectName;

    projectDropdown.appendChild(opt);
}


projectDropdown.addEventListener("change", (e) => {
    const selectedID = e.target.value;  // The value selected from the dropdown = projectID
    if (!selectedID) return;           // If empty is selected, do nothing

    loadProject(selectedID);
});

// Finally, when the page loads, fetch the project list
loadProjectList();
