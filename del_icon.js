function deleteIcon(iconID) {
    if (confirm("Möchten Sie dieses Symbol und das darin enthaltene 360-Grad-Bild wirklich löschen?")) {
        fetch("del_icon.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: "iconID=" + encodeURIComponent(iconID)
        })
        .then(response => response.json())
        .then(data => {
            console.log("Delete Icon Response:", data);
            if (data.status === "success") {
                // Removes deleted icon from page
                let iconEl = document.querySelector('[data-icon-id="' + iconID + '"]');
                if (iconEl) {
                    iconEl.remove();
                }
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => console.error("Fetch Error:", error));
    }
}
