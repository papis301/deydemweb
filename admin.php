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
<title>DEY DEM - Administration</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>
body{background:#f4f6f8}
.badge-pending{background:#ffc107;color:#000}
.badge-approved{background:#198754}
.badge-rejected{background:#dc3545}
.table td,.table th{vertical-align:middle;white-space:nowrap}
@media(max-width:768px){
    h2{font-size:20px}
    .toolbar{flex-direction:column;align-items:stretch!important}
    .filters{display:grid;grid-template-columns:1fr 1fr}
    .filters .btn{font-size:12px}
    .table{font-size:12px}
    .admin-name{display:none}
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark bg-dark mb-4">
<div class="container">
    <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-lock-fill text-warning"></i> DEY DEM Admin</a>
    <div class="d-flex align-items-center gap-2">
        <span class="text-light admin-name"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></span>
        <a href="suggestions.php" class="btn btn-warning btn-sm"><i class="bi bi-lightbulb"></i> Suggestions</a>
        <button onclick="logout()" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Déconnexion</button>
    </div>
</div>
</nav>

<div class="container">
    <div class="toolbar d-flex justify-content-between align-items-center mb-4 gap-3">
        <h2 class="mb-0">Modération des Trajets</h2>
        <div class="filters btn-group">
            <button class="btn btn-outline-secondary active" onclick="loadRoutes('ALL',this)">Tous</button>
            <button class="btn btn-outline-warning" onclick="loadRoutes('PENDING',this)">En attente</button>
            <button class="btn btn-outline-success" onclick="loadRoutes('APPROVED',this)">Approuvés</button>
            <button class="btn btn-outline-danger" onclick="loadRoutes('REJECTED',this)">Rejetés</button>
        </div>
    </div>

    <div id="alertBox" class="d-none alert alert-dismissible fade show">
        <span id="alertMessage"></span><button class="btn-close" onclick="hideAlert()"></button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Départ ➔ Arrivée</th><th>Transport</th><th>Prix (FCFA)</th><th>Statut</th><th>Date</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody id="routesTableBody">
                    <tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div><div class="text-muted mt-2">Chargement...</div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODALE -->
<div class="modal fade" id="editModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Modifier le trajet</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <form id="editRouteForm">
        <div class="modal-body">
            <input type="hidden" id="editRouteId">
            <div class="mb-3"><label class="form-label fw-bold">Parcours</label><input id="editRouteTitle" class="form-control" disabled></div>
            <div class="mb-3"><label class="form-label">Prix estimé (FCFA)</label><input type="number" id="editPrice" class="form-control" min="0"></div>
            <div class="mb-3"><label class="form-label">Statut</label>
                <select id="editStatus" class="form-select">
                    <option value="PENDING">En attente</option>
                    <option value="APPROVED">Approuvé</option>
                    <option value="REJECTED">Rejeté</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
        </div>
    </form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentFilter='ALL', modal;
document.addEventListener('DOMContentLoaded', ()=>{ modal=new bootstrap.Modal(document.getElementById('editModal')); loadRoutes(); });

async function loadRoutes(status='ALL', btn=null){
    currentFilter=status;
    if(btn){
        document.querySelectorAll('.filters .btn').forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
    }
    const tbody=document.getElementById('routesTableBody');
    tbody.innerHTML=`<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary"></div><div class="text-muted mt-2">Chargement...</div></td></tr>`;
    try{
        const res=await fetch('api/admin_routes.php?status='+status);
        if(res.status===401){location.href='login.html';return;}
        const json=await res.json();
        if(json.success) renderTable(json.data); else showAlert('danger', json.message);
    }catch(e){ showAlert('danger', 'Erreur de connexion au serveur.'); }
}

function renderTable(routes){
    const tbody=document.getElementById('routesTableBody');
    tbody.innerHTML='';
    if(!routes.length){
        tbody.innerHTML=`<tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-inbox fs-2"></i><br>Aucun trajet trouvé.</td></tr>`;
        return;
    }
    routes.forEach(r=>{
        const st=getStatus(r.status);
        const tr=document.createElement('tr');
        tr.innerHTML=`
            <td><strong>#${r.id}</strong></td>
            <td><strong>${esc(r.origin_name||'Inconnu')}</strong> <span class="text-success">➔</span> <strong>${esc(r.destination_name||'Inconnu')}</strong></td>
            <td>${r.transport_name ? `<span class="badge bg-info text-dark">${esc(r.transport_name)}</span>` : '<em class="text-muted">Non spécifié</em>'}</td>
            <td>${r.estimated_price ? esc(r.estimated_price)+' FCFA' : '-'}</td>
            <td><span class="badge ${st.class}">${st.text}</span></td>
            <td><small>${formatDate(r.created_at)}</small></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" onclick="edit(${r.id},'${quote(r.origin_name)} ➔ ${quote(r.destination_name)}','${quote(r.estimated_price||'')}','${quote(r.status)}')"><i class="bi bi-pencil"></i></button>
                ${r.status!=='APPROVED'?`<button class="btn btn-sm btn-success" onclick="update(${r.id},'APPROVED','${quote(r.estimated_price||'')}')"><i class="bi bi-check-lg"></i></button>`:''}
                ${r.status!=='REJECTED'?`<button class="btn btn-sm btn-danger" onclick="update(${r.id},'REJECTED','${quote(r.estimated_price||'')}')"><i class="bi bi-x-lg"></i></button>`:''}
            `;
        tbody.appendChild(tr);
    });
}

function getStatus(s){
    if(s==='APPROVED') return {class:'badge-approved', text:'Approuvé'};
    if(s==='REJECTED') return {class:'badge-rejected', text:'Rejeté'};
    return {class:'badge-pending', text:'En attente'};
}

function edit(id,title,price,status){
    document.getElementById('editRouteId').value=id;
    document.getElementById('editRouteTitle').value=title;
    document.getElementById('editPrice').value=price;
    document.getElementById('editStatus').value=status;
    modal.show();
}

document.getElementById('editRouteForm').addEventListener('submit', async e=>{
    e.preventDefault();
    await update(document.getElementById('editRouteId').value, document.getElementById('editStatus').value, document.getElementById('editPrice').value);
    modal.hide();
});

async function update(id,status,price){
    try{
        const res=await fetch('api/admin_routes.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({route_id:id,status:status,estimated_price:price})});
        if(res.status===401){location.href='login.html';return;}
        const json=await res.json();
        if(json.success){ showAlert('success', json.message); loadRoutes(currentFilter); }
        else showAlert('danger', json.message);
    }catch(e){ showAlert('danger', 'Erreur lors de la mise à jour.'); }
}

async function logout(){ await fetch('api/admin_check_auth.php?action=logout'); location.href='login.html'; }
function showAlert(type,msg){ const b=document.getElementById('alertBox'); b.className=`alert alert-${type} alert-dismissible fade show`; document.getElementById('alertMessage').textContent=msg; }
function hideAlert(){ document.getElementById('alertBox').classList.add('d-none'); }
function formatDate(d){ if(!d)return '-'; const date=new Date(String(d).replace(' ','T')); return isNaN(date)?d:date.toLocaleString('fr-FR'); }
function esc(v){ const d=document.createElement('div'); d.textContent=v??''; return d.innerHTML; }
function quote(v){ return String(v??'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
</script>
</body>
</html>