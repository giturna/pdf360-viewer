/* -- 3) Opening/Closing the 360° Viewer (Three.js) in a Modal -- */

const modalOverlay = document.getElementById('modalOverlay');
const closeBtn = document.getElementById('closeBtn');
closeBtn.addEventListener('click', close360Modal);

let renderer, scene, camera, controls;

/**
 * Open the modal displaying the 360 image.
 * @param {string} imageData - The 360 jpg to display (base64 or URL)
 */
function open360Modal(imageData) {
  modalOverlay.style.display = 'flex';

  // Clear any previous Three.js scene before creating a new one
  const threeContainer = document.getElementById('threeContainer');
  threeContainer.innerHTML = ''; // Reset content
  threeContainer.appendChild(closeBtn); // Re-add the close button

  // Create the renderer
  renderer = new THREE.WebGLRenderer({ antialias: true });
  renderer.setSize(threeContainer.clientWidth, threeContainer.clientHeight);
  threeContainer.appendChild(renderer.domElement);

  // Scene
  scene = new THREE.Scene();

  // Camera
  camera = new THREE.PerspectiveCamera(
    75, 
    threeContainer.clientWidth / threeContainer.clientHeight, 
    0.1, 
    1000
  );
  camera.target = new THREE.Vector3(0, 0, 0);
  
  // Controls (OrbitControls)
  controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableZoom = true;
  controls.minDistance = 0.1;
  controls.maxDistance = 10;

  // Position the camera in the center of the sphere
  camera.position.set(0, 0, 0.1);

  // Load 360 texture
  const textureLoader = new THREE.TextureLoader();
  const texture = textureLoader.load(imageData, () => {
    render360();
  });
  
  // Sphere Geometry (inverted so the inside is visible)
  const geometry = new THREE.SphereGeometry(50, 32, 32);
  // Invert the sphere so that the inner side is visible
  geometry.scale(-1, 1, 1);

  const material = new THREE.MeshBasicMaterial({ map: texture });
  const sphere = new THREE.Mesh(geometry, material);
  scene.add(sphere);

  // Render loop
  function render360() {
    requestAnimationFrame(render360);
    controls.update();
    renderer.render(scene, camera);
  }
}

/** Close the modal */
function close360Modal() {
  modalOverlay.style.display = 'none';
  // Additional cleanup may be required to stop the renderer (e.g., memory cleanup, etc.)
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

let currentIconID = null;

function openIconModal(iconID) {
  currentIconID = iconID;

  // Clear and reload the image list on the left side
  loadIconImages(iconID);

  // Call the uploadIconImage function when the "Upload Image" button is clicked
  const addImageBtn = document.getElementById("addImageBtn");
  addImageBtn.onclick = () => {
    uploadIconImage(iconID);
  };
}

function loadIconImages(iconID) {
  // Fetch images linked to a specific iconID from the server
  fetch("get_icon_images.php?iconID=" + iconID)
    .then(r => r.json())
    .then(data => {
      const imageListDiv = document.getElementById("iconImageList");
      const imageDeleteDiv = document.getElementById("delImageList");
      imageListDiv.innerHTML = "";
      imageDeleteDiv.innerHTML = "";

      if (data.status === "success") {
        data.images.forEach(img => {
          // A button for each image
          const btnLoadImg = document.createElement("button");
          const btnDelImg = document.createElement("button");
          btnLoadImg.innerText = "Bild: " + img.imageTitle;
          btnDelImg.innerText = "Löschen";

          btnLoadImg.onclick = () => {
            convertImageUrlToBase64(img.imagePath)
              .then((base64Data) => {
                open360Modal(base64Data);
              })
              .catch((err) => {
                console.error("Error:", err);
              });
          };

          btnDelImg.onclick = () => {
            deleteIconImage(img.imageID, iconID);
          };

          imageListDiv.appendChild(btnLoadImg);
          imageDeleteDiv.appendChild(btnDelImg);
        });
      } else {
        imageListDiv.innerHTML = "<p>Kein Bild gefunden</p>";
      }
    })
    .catch(err => console.error("Error loading icon images:", err));
}

function uploadIconImage(iconID) {
  const fileInput = document.getElementById("iconImageInput");
  if (!fileInput.files || fileInput.files.length === 0) {
    alert("Bitte wählen Sie ein Bild aus");
    return;
  }

  let formData = new FormData();
  formData.append("iconID", iconID);
  formData.append("imgFile", fileInput.files[0]);
  formData.append("projectID", currentProjectId);

  fetch("upload_icon_image.php", {
    method: "POST",
    body: formData
  })
    .then(r => r.json())
    .then(data => {
      if (data.status === "success") {
        // Refresh the list again with loadIconImages
        loadIconImages(iconID);
        fileInput.value = ""; // Reset the input
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch(err => console.error("Upload icon image error:", err));
}



function deleteIconImage(imageID, iconID) {
  if (!confirm("Möchten Sie dieses Bild wirklich löschen?")) return;

  // Simple endpoint: delete_icon_image.php (deletes the file and database record)
  let formData = new FormData();
  formData.append("imageID", imageID);
  formData.append("iconID", iconID);

  fetch("delete_icon_image.php", {
    method: "POST",
    body: formData
  })
    .then(r => r.json())
    .then(data => {
      if (data.status === "success") {
        alert("Bild erfolgreich gelöscht!");
        // Reload to update the list
        loadIconImages(iconID);
      } else {
        alert("Fehler beim Löschen des Bildes: " + data.message);
      }
    })
    .catch(err => console.error("Delete icon image error:", err));
}

