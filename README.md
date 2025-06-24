# PDF-360 Viewer

An all-in-one **PDF annotation & 360-image hotspot** viewer built with PHP 8 + MySQL.  
Upload project PDFs, drop interactive icons, attach 360-degree images, and explore everything in a single responsive UI.

---

## Key features

* **Drag-and-drop PDF uploader** – stores files under `uploads/pdfs`
* **Interactive icon editor** – place hotspots anywhere on a page
* **360-image support** – upload equirectangular JPG/PNG; preview in-app
* **Project dashboard** – create, list, delete projects in one click
* **REST-style JSON endpoints** – vanilla JS front-end (no frameworks)
* **Docker-first workflow** – one command boots Apache + MySQL with schema
* **Fallback LAMP compatibility** – run on any classic PHP/Apache host

<em>(Add a GIF or screenshot section here)</em>

---

## Tech stack

| Layer | Technology | Notes |
|-------|------------|-------|
| **Server** | PHP 8.2 (mod_php) | `mysqli`, `pdo_mysql`, `gd`, `mbstring` enabled |
| **Database** | MySQL 8.2 | schema + seed executed on first start |
| **Front-end** | ES6, Fetch API | `upload.js`, `modal.js`, `icon.js`, … |
| **Styling** | Plain CSS | `style.css` + `responsive.css` |
| **Containers** | Docker Compose v2 | `web` (PHP-Apache) / `db` (MySQL) / `pma` (phpMyAdmin) |

---

## Quick Start (recommended)

> Requires **Docker ≥ 23** & **Docker Compose v2**. Nothing else needs to be installed on the host.

```bash
# 1 – Clone
git clone https://github.com/YOUR-USER/pdf360-viewer.git
cd pdf360-viewer

# 2 – Build & start containers (first run ≈ 1–2 min)
docker compose up --build -d

# 3 – Open in browser
http://localhost:8080      # application
http://localhost:8081      # phpMyAdmin (optional)

# 4 – Stop everything
docker compose down        # containers & network removed
```
## Alternative – Classic LAMP setup

Can’t use Docker? Run it on any server with Apache + PHP 8.1+ and MySQL 8:

```bash
# Clone the repo
git clone https://github.com/YOUR-USER/pdf360-viewer.git
cd pdf360-viewer

# 1 – Copy example environment & edit DB creds
cp .env.example .env

# 2 – Import database schema
mysql -u root -p < database/schema.sql

# 3 – Local dev server
php -S localhost:8000 -t .
```

Required PHP extensions

```bash
mysqli  pdo_mysql  gd  mbstring
```

Add them via apt install php8.2-mysqli php8.2-gd … or your distro’s equivalent.

## Project structure
```bash
pdf360-viewer/
├── database/                # SQL assets
│   ├── schema.sql           #   table definitions + DB/user creation
│
├── uploads/                 # runtime files (NOT committed)
│   ├── pdfs/                #   uploaded PDF documents
│   ├── 360images/           #   equirectangular JPEG/PNG panoramas
│   └── tmp/                 #   temporary chunks / drafts
│       └── .gitkeep         #   keeps the empty dirs under Git
│
├── docker-compose.yml       # services: web · db · phpMyAdmin
├── Dockerfile               # php:8.2-apache + extensions + upload limits
├── php-uploads.ini          # raises upload_max_filesize / post_max_size
├── .env                     # DB secrets
├── .gitignore               # excludes uploads/, .env, vendor/, …
│
├── index.php                # main dashboard entry point
├── hilfsfunktionen.php      # DB connection + utility helpers
├── upload.php               # JSON endpoint for PDF / 360-image uploads
├── upload_icon_image.php    # endpoint for single icon thumbnail uploads
│
├── get_project.php          # lightweight REST-style endpoints
├── get_project_list.php
├── get_icon_images.php
├── get_icons_for_pdf.php
├── del_project.php
├── del_icon.php
├── delete_icon_image.php
├── delete_pdf.php
│
├── icon.js                  # hotspot drawing / dragging / click logic
├── upload.js                # fetch-based upload helper
├── modal.js                 # vanilla JS modal component
├── get_project_list.js      # front-end project list loader
├── del_project.js
├── del_icon.js
│
├── style.css                # base styling
└── responsive.css           # breakpoints for mobile / tablet layouts
```

## Usage guide

1. **Create a project**  
   - Click **New Project** in the left-hand sidebar and enter a name.  
   - The project appears in the list and becomes the active context.

2. **Upload PDFs to the project**  
   - With the project selected, choose **Select PDF** in the right pane and pick a file.  
   - The PDF is stored in `uploads/pdfs/` and listed under the project.  
   - Repeat as needed — a project can hold **multiple PDFs**.

3. **Place icons on a PDF**  
   - Click a PDF to open it in the viewer.  
   - Use **Select 360 Image** to upload an equirectangular JPG/PNG.  
   - Your first left-click on the PDF drops an icon at that position.  
   - Drag the icon to fine-tune its location. A PDF can hold **multiple icons**.

4. **Work with an existing icon**  
   - **Left-click** an icon to open its modal window.  
     - *Left side* Upload another 360 image or see a list of existing ones (each with its own delete button).  
     - *Right side* Interactive viewer for the selected 360 image.  
   - **Right-click** an icon on the PDF to delete it.

5. **Delete items**  
   - **Delete PDF** Button above the viewer removes the current PDF and its icons.  
   - **Delete Project** Button below the project list wipes the entire project, its PDFs, icons, and images.  
   - **Delete 360 Image** Delete button next to each image inside the modal.

6. **Reload projects**  
   - Select any project in the left sidebar at any time; its PDFs and icons reload automatically.

### File locations

| Path                     | Contents                        |
| ------------------------ | ------------------------------ |
| `uploads/pdfs/`          | All uploaded PDF documents      |
| `uploads/360images/`     | All uploaded 360-degree images  |
| `uploads/tmp/`           | Temporary chunks during upload  |
