<?php
session_start();
if (!($_SESSION['admin_logged_in'] ?? false) || !in_array($_SESSION['admin_role'] ?? '', ['ADMIN', 'MODERATOR'])) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>DEY DEM - Types de transport</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
body{background:#f4f6f8}
.type-card{transition:transform .2s, box-shadow .2s; border:2px solid #e9ecef; border-radius:12px; overflow:hidden; background:#fff;}
.type-card:hover{transform:translateY(-3px); box-shadow:0 8px 16px rgba(0,0,0,.1); border-color:#0d6efd;}
.type-img-wrapper{height:120px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#f8f9fa,#e9ecef);}
.type-img-wrapper img{max-width:80%; max-height:100px; object-fit:contain;}
.type-icon-fallback{width:70px; height:70px; border-radius:50%; background:linear-gradient(135deg,#0d6efd,#0a58ca); color:#fff; display:flex; align-items:center; justify-content:center; font-size:32px;}
.form-label{font-weight:600; font-size:0.9rem;}
.preview-img{max-height:100px; object-fit:contain; border:1px solid #dee2e6; border-radius:8px; padding:4px;}
@media(max-width:768px){ .toolbar{flex-direction:column; align-items:stretch!important;} }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-dark mb-4">
<div class="container">
    <a class="navbar-brand fw-bold" href="admin.php">
        <i class="bi bi-shield-lock-fill text-warning"></i> DEY DEM Admin
    </a>
    <div class="d-flex align-items-center gap-2">
        <span class="text-light d-none d-md-inline">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?>
        </span>
        <a href="admin.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-arrow-left"></i> Trajets
        </a>
        <a href="suggestions.php" class="btn btn-warning btn-sm">
            <i class="bi bi-lightbulb"></i> Suggestions
        </a>
        <button onclick="logout()" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </button>
    </div>
</div>
</nav>

<div class="container">

    <!-- Header -->
    <div class="toolbar d-flex justify-content-between align-items-center mb-4 gap-3 flex-wrap">
        <div>
            <h2 class="mb-0"><i class="bi bi-grid-3x3-gap-fill text-primary"></i> Types de transport</h2>
            <p class="text-muted small mb-0">Gérez les catégories de transport et leurs images</p>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg"></i> Nouveau type
        </button>
    </div>

    <!-- Alert -->
    <div id="alertBox" class="d-none alert alert-dismissible fade show">
        <span id="alertMessage"></span>
        <button class="btn-close" onclick="hideAlert()"></button>
    </div>

    <!-- Grille des types de transport -->
    <div class="row g-4 mb-5" id="typesGrid">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="text-muted mt-2">Chargement...</p>
        </div>
    </div>
</div>

<!-- MODALE CRÉATION / ÉDITION -->
<div class="modal fade" id="typeModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
    <form id="typeForm" enctype="multipart/form-data">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalTitle">Nouveau type de transport</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="typeId" name="id">
            <input type="hidden" id="existingImageUrl" name="existing_image_url">

            <div class="row g-3">
                <!-- Colonne gauche : Infos -->
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" id="typeName" name="name" class="form-control" required maxlength="100" placeholder="Ex: Wargainde">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" id="typeCode" name="code" class="form-control text-uppercase" required maxlength="50" placeholder="Ex: WARGAINDE">
                        <small class="text-muted">Identifiant unique, sans espaces (majuscules et underscores)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea id="typeDescription" name="description" class="form-control" rows="3" placeholder="Brève description du type de transport..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Capacité (nombre)</label>
                            <input type="number" id="typeCapacityEstimate" name="capacity_estimate" class="form-control" min="1" max="1000" placeholder="Ex: 7">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Plage de capacité</label>
                            <input type="text" id="typeCapacityRange" name="capacity_range" class="form-control" maxlength="30" placeholder="Ex: 4 à 7 places">
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="typeIsActive" name="is_active" checked>
                        <label class="form-check-label" for="typeIsActive">Type actif (visible sur le site)</label>
                    </div>
                </div>

                <!-- Colonne droite : Image -->
                <div class="col-md-5">
                    <label class="form-label">Image / Icône</label>
                    <div class="border rounded p-3 text-center bg-light mb-2">
                        <img id="imagePreview" src="" alt="Aperçu" 
                             class="preview-img mb-2" style="display:none;">
                        <div id="imagePlaceholder" class="text-muted py-4">
                            <i class="bi bi-image fs-1"></i>
                            <p class="small mb-0 mt-2">Aucune image</p>
                        </div>
                    </div>
                    <input type="file" id="typeImage" name="image" class="form-control" accept="image/*">
                    <small class="text-muted d-block mt-1">
                        Formats : PNG, JPG, WEBP, SVG (max 2 Mo).<br>
                        Recommandé : 300×200 px, fond transparent.
                    </small>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Enregistrer
            </button>
        </div>
    </form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let modal, currentTypes = [];

// ============================================
// UTILITAIRES
// ============================================
function esc(v){ const d = document.createElement('div'); d.textContent = v ?? ''; return d.innerHTML; }
function showAlert(type, msg){
    const b = document.getElementById('alertBox');
    b.className = `alert alert-${type} alert-dismissible fade show`;
    document.getElementById('alertMessage').textContent = msg;
    setTimeout(hideAlert, 5000);
}
function hideAlert(){ document.getElementById('alertBox').classList.add('d-none'); }
async function logout(){ await fetch('api/admin_check_auth.php?action=logout'); location.href='login.html'; }

// ============================================
// CHARGEMENT DE LA LISTE
// ============================================
async function loadTypes(){
    const grid = document.getElementById('typesGrid');
    grid.innerHTML = '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

    try {
        const res = await fetch('api/admin_transport_types.php');
        if (res.status === 401){ location.href='login.html'; return; }
        const json = await res.json();

        if (!json.success){
            grid.innerHTML = `<div class="col-12 text-center text-danger py-5">${esc(json.message)}</div>`;
            return;
        }

        currentTypes = json.data;
        renderTypes(json.data);
    } catch (e) {
        grid.innerHTML = '<div class="col-12 text-center text-danger py-5">Erreur de connexion.</div>';
    }
}

function renderTypes(types){
    const grid = document.getElementById('typesGrid');
    grid.innerHTML = '';

    if (!types.length){
        grid.innerHTML = `
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                Aucun type de transport. Cliquez sur « Nouveau type » pour commencer.
            </div>`;
        return;
    }

    types.forEach(t => {
        const col = document.createElement('div');
        col.className = 'col-12 col-md-6 col-lg-4 col-xl-3';

        const imageHtml = t.image_url
            ? `<img src="${esc(t.image_url)}" alt="${esc(t.name)}"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
               <div class="type-icon-fallback" style="display:none;"><i class="bi bi-bus-front"></i></div>`
            : `<div class="type-icon-fallback"><i class="bi bi-bus-front"></i></div>`;

        const capacityHtml = t.capacity_range
            ? `<span class="badge bg-light text-dark border"><i class="bi bi-people-fill"></i> ${esc(t.capacity_range)}</span>`
            : (t.capacity_estimate 
                ? `<span class="badge bg-light text-dark border"><i class="bi bi-people-fill"></i> ${t.capacity_estimate} places</span>`
                : '');

        const statusBadge = t.is_active == 1
            ? '<span class="badge bg-success">Actif</span>'
            : '<span class="badge bg-secondary">Inactif</span>';

        col.innerHTML = `
            <div class="type-card h-100">
                <div class="type-img-wrapper">${imageHtml}</div>
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-0">${esc(t.name)}</h6>
                            <small class="text-muted font-monospace">${esc(t.code)}</small>
                        </div>
                        ${statusBadge}
                    </div>
                    <p class="small text-muted mb-2" style="min-height:40px;">
                        ${esc(t.description || 'Aucune description')}
                    </p>
                    <div class="mb-3">${capacityHtml}</div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary flex-fill" onclick="openEditModal(${t.id})">
                            <i class="bi bi-pencil"></i> Modifier
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteType(${t.id}, '${esc(t.name).replace(/'/g, "\\'")}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        grid.appendChild(col);
    });
}

// ============================================
// MODALE CRÉATION
// ============================================
function openCreateModal(){
    document.getElementById('modalTitle').textContent = 'Nouveau type de transport';
    document.getElementById('typeForm').reset();
    document.getElementById('typeId').value = '';
    document.getElementById('existingImageUrl').value = '';
    document.getElementById('typeIsActive').checked = true;
    updateImagePreview('');
    modal.show();
}

// ============================================
// MODALE ÉDITION
// ============================================
function openEditModal(id){
    const t = currentTypes.find(x => x.id == id);
    if (!t) return;

    document.getElementById('modalTitle').textContent = 'Modifier « ' + t.name + ' »';
    document.getElementById('typeId').value = t.id;
    document.getElementById('typeName').value = t.name || '';
    document.getElementById('typeCode').value = t.code || '';
    document.getElementById('typeDescription').value = t.description || '';
    document.getElementById('typeCapacityEstimate').value = t.capacity_estimate || '';
    document.getElementById('typeCapacityRange').value = t.capacity_range || '';
    document.getElementById('typeIsActive').checked = t.is_active == 1;
    document.getElementById('existingImageUrl').value = t.image_url || '';
    updateImagePreview(t.image_url || '');
    modal.show();
}

// ============================================
// APERÇU DE L'IMAGE
// ============================================
function updateImagePreview(url){
    const img = document.getElementById('imagePreview');
    const placeholder = document.getElementById('imagePlaceholder');
    if (url){
        img.src = url;
        img.style.display = 'inline-block';
        placeholder.style.display = 'none';
    } else {
        img.style.display = 'none';
        placeholder.style.display = 'block';
    }
}

document.getElementById('typeImage').addEventListener('change', function(e){
    const file = e.target.files[0];
    if (file){
        const reader = new FileReader();
        reader.onload = ev => updateImagePreview(ev.target.result);
        reader.readAsDataURL(file);
    }
});

// ============================================
// SOUMISSION DU FORMULAIRE
// ============================================
document.getElementById('typeForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);

    try {
        const res = await fetch('api/admin_transport_types.php', {
            method: 'POST',
            body: formData
        });
        if (res.status === 401){ location.href='login.html'; return; }
        const json = await res.json();

        if (json.success){
            showAlert('success', json.message);
            modal.hide();
            loadTypes();
        } else {
            showAlert('danger', json.message);
        }
    } catch (e) {
        showAlert('danger', 'Erreur lors de l\'enregistrement.');
    }
});

// ============================================
// SUPPRESSION
// ============================================
async function deleteType(id, name){
    if (!confirm(`Supprimer définitivement le type « ${name} » ?\n\nCette action est irréversible.`)) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const res = await fetch('api/admin_transport_types.php', {
            method: 'POST',
            body: formData
        });
        if (res.status === 401){ location.href='login.html'; return; }
        const json = await res.json();

        if (json.success){
            showAlert('success', json.message);
            loadTypes();
        } else {
            showAlert('danger', json.message);
        }
    } catch (e) {
        showAlert('danger', 'Erreur lors de la suppression.');
    }
}

// ============================================
// INITIALISATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    modal = new bootstrap.Modal(document.getElementById('typeModal'));
    loadTypes();
});
</script>
</body>
</html>