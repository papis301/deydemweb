<?php
// =====================================================
// 1. DÉMARRAGE DE LA SESSION ET CONTRÔLE D'ACCÈS
// =====================================================

session_start();

$userRole = $_SESSION['admin_role'] ?? '';

if (
    !isset($_SESSION['admin_logged_in']) ||
    $_SESSION['admin_logged_in'] !== true ||
    !in_array($userRole, ['ADMIN', 'MODERATOR'])
) {
    header("Location: login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>DEY DEM - Administration des Trajets</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
    >

    <style>

        /* =====================================================
           GÉNÉRAL
        ===================================================== */

        body {
            background-color: #f4f6f8;
            min-height: 100vh;
        }

        .main-container {
            width: 100%;
        }


        /* =====================================================
           NAVBAR
        ===================================================== */

        .navbar-brand {
            white-space: nowrap;
        }

        .admin-user {
            white-space: nowrap;
        }


        /* =====================================================
           BADGES STATUT
        ===================================================== */

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-approved {
            background-color: #198754;
            color: #fff;
        }

        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
        }

        .badge-expired {
            background-color: #6c757d;
            color: #fff;
        }


        /* =====================================================
           FILTRES
        ===================================================== */

        .filter-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }


        /* =====================================================
           TABLEAU
        ===================================================== */

        .table-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        .table th {
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .route-path {
            min-width: 190px;
        }

        .action-buttons {
            white-space: nowrap;
        }


        /* =====================================================
           CARTES MOBILE
        ===================================================== */

        .mobile-routes {
            display: none;
        }

        .route-mobile-card {
            background: #fff;
            border-radius: 14px;
            margin-bottom: 12px;
            padding: 15px;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.07);

            border: 1px solid #e9ecef;
        }

        .route-mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 10px;

            margin-bottom: 13px;
        }

        .route-mobile-id {
            font-weight: 700;
            color: #212529;
        }

        .route-mobile-path {
            font-size: 16px;
            font-weight: 700;

            padding: 12px;

            background: #f8f9fa;

            border-radius: 10px;

            margin-bottom: 13px;

            line-height: 1.5;
        }

        .route-mobile-path .arrow {
            color: #198754;
            padding: 0 5px;
        }

        .route-mobile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;

            margin-bottom: 13px;
        }

        .route-info-box {
            background: #f8f9fa;
            border-radius: 8px;

            padding: 9px 10px;
        }

        .route-info-label {
            display: block;

            color: #6c757d;

            font-size: 11px;

            margin-bottom: 3px;
        }

        .route-info-value {
            display: block;

            font-size: 13px;

            font-weight: 600;

            word-break: break-word;
        }

        .route-mobile-actions {
            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 7px;

            margin-top: 10px;
        }

        .route-mobile-actions .btn {
            font-size: 13px;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        #alertBox {
            border-radius: 10px;
        }


        /* =====================================================
           MODALE
        ===================================================== */

        .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
        }

        .modal-header {
            background: #212529;
            color: #fff;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767.98px) {

            body {
                background: #f4f6f8;
            }

            /* Navbar */

            .navbar {
                margin-bottom: 15px !important;
            }

            .navbar .container {
                padding-left: 12px;
                padding-right: 12px;
            }

            .navbar-brand {
                font-size: 16px;
            }

            .admin-user {
                font-size: 12px;
                margin-right: 8px !important;
            }

            .admin-user i {
                display: none;
            }

            .navbar .btn {
                font-size: 12px;
                padding: 5px 8px;
            }

            .navbar .btn i {
                margin-right: 0 !important;
            }

            .navbar .btn span {
                display: none;
            }


            /* Conteneur */

            .main-container {
                padding-left: 10px;
                padding-right: 10px;
            }


            /* Titre */

            .page-title {
                font-size: 21px;
                margin-bottom: 13px !important;
            }


            /* Filtres */

            .filter-container {
                display: block;
                margin-bottom: 15px !important;
            }

            .filter-buttons {
                width: 100%;

                display: grid;

                grid-template-columns: 1fr 1fr;

                gap: 6px;
            }

            .filter-buttons .btn {
                width: 100%;

                font-size: 13px;

                padding: 9px 5px;
            }


            /* Cacher tableau */

            .desktop-table {
                display: none !important;
            }


            /* Afficher cartes */

            .mobile-routes {
                display: block;
            }


            /* Carte */

            .route-mobile-card {
                padding: 13px;
            }

            .route-mobile-header {
                margin-bottom: 10px;
            }

            .route-mobile-path {
                font-size: 15px;
                padding: 10px;
            }

            .route-mobile-info {
                grid-template-columns: 1fr 1fr;
            }

            .route-info-value {
                font-size: 12px;
            }


            /* Actions */

            .route-mobile-actions {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .route-mobile-actions .btn {
                padding: 8px 4px;
            }


            /* Modal */

            .modal-dialog {
                margin: 10px;
            }

        }


        /* =====================================================
           TRÈS PETITS ÉCRANS
        ===================================================== */

        @media (max-width: 400px) {

            .filter-buttons {
                grid-template-columns: 1fr 1fr;
            }

            .filter-buttons .btn {
                font-size: 12px;
            }

            .route-mobile-info {
                grid-template-columns: 1fr;
            }

            .route-mobile-actions {
                grid-template-columns: 1fr;
            }

            .route-mobile-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR ADMIN
===================================================== -->

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">

    <div class="container">

        <a
            class="navbar-brand fw-bold"
            href="#"
        >

            <i class="bi bi-shield-lock-fill text-warning"></i>

            DEY DEM Admin

        </a>


        <div class="d-flex align-items-center">

            <span class="text-light me-3 admin-user">

                <i class="bi bi-person-circle"></i>

                <?= htmlspecialchars(
                    $_SESSION['admin_user'] ?? 'Admin'
                ) ?>

            </span>


            <button
                onclick="logout()"
                class="btn btn-outline-danger btn-sm"
            >

                <i class="bi bi-box-arrow-right"></i>

                <span>Déconnexion</span>

            </button>

        </div>

    </div>

</nav>



<!-- =====================================================
     CONTENU PRINCIPAL
===================================================== -->

<div class="container main-container">


    <!-- =================================================
         TITRE + FILTRES
    ================================================== -->

    <div class="filter-container mb-4">

        <h2 class="page-title mb-0">

            Modération des Trajets

        </h2>


        <!-- Filtres -->

        <div
            class="filter-buttons"
            role="group"
        >

            <button
                type="button"
                class="btn btn-outline-secondary active"
                onclick="loadRoutes('ALL', this)"
            >

                <i class="bi bi-list"></i>

                Tous

            </button>


            <button
                type="button"
                class="btn btn-outline-warning"
                onclick="loadRoutes('PENDING', this)"
            >

                <i class="bi bi-hourglass-split"></i>

                En attente

            </button>


            <button
                type="button"
                class="btn btn-outline-success"
                onclick="loadRoutes('APPROVED', this)"
            >

                <i class="bi bi-check-circle"></i>

                Approuvés

            </button>


            <button
                type="button"
                class="btn btn-outline-danger"
                onclick="loadRoutes('REJECTED', this)"
            >

                <i class="bi bi-x-circle"></i>

                Rejetés

            </button>

        </div>

    </div>



    <!-- =================================================
         ZONE NOTIFICATION
    ================================================== -->

    <div
        id="alertBox"
        class="d-none alert alert-dismissible fade show"
        role="alert"
    >

        <span id="alertMessage"></span>

        <button
            type="button"
            class="btn-close"
            onclick="hideAlert()"
        ></button>

    </div>



    <!-- =================================================
         TABLEAU DESKTOP
    ================================================== -->

    <div class="card shadow-sm table-card desktop-table">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>
                                Départ ➔ Arrivée
                            </th>

                            <th>
                                Moyen de Transport
                            </th>

                            <th>
                                Prix Est. (FCFA)
                            </th>

                            <th>
                                Statut
                            </th>

                            <th>
                                Date de création
                            </th>

                            <th class="text-end">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody id="routesTableBody">

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-4"
                            >

                                <div
                                    class="spinner-border text-primary"
                                    role="status"
                                ></div>

                                <p class="mt-2 text-muted">

                                    Chargement des données...

                                </p>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>



    <!-- =================================================
         CARTES MOBILE
    ================================================== -->

    <div
        id="mobileRoutes"
        class="mobile-routes"
    >

        <div class="text-center py-4">

            <div
                class="spinner-border text-primary"
                role="status"
            ></div>

            <p class="mt-2 text-muted">

                Chargement des données...

            </p>

        </div>

    </div>


</div>



<!-- =====================================================
     MODALE D'ÉDITION DU TRAJET
===================================================== -->

<div
    class="modal fade"
    id="editModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    Modifier le Trajet

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>


            <form id="editRouteForm">

                <div class="modal-body">


                    <input
                        type="hidden"
                        id="editRouteId"
                    >


                    <div class="mb-3">

                        <label class="form-label fw-bold">

                            Parcours

                        </label>

                        <input
                            type="text"
                            id="editRouteTitle"
                            class="form-control"
                            disabled
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">

                            Prix Estimé (FCFA)

                        </label>

                        <input
                            type="number"
                            id="editPrice"
                            class="form-control"
                            placeholder="ex: 500"
                            min="0"
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">

                            Statut de validation

                        </label>

                        <select
                            id="editStatus"
                            class="form-select"
                            required
                        >

                            <option value="PENDING">
                                En attente (PENDING)
                            </option>

                            <option value="APPROVED">
                                Approuvé (APPROVED)
                            </option>

                            <option value="REJECTED">
                                Rejeté (REJECTED)
                            </option>

                        </select>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Annuler

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-save"></i>

                        Enregistrer

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>



<script>

let currentFilter = 'ALL';

let editModalObj = null;


// =====================================================
// INITIALISATION
// =====================================================

document.addEventListener(
    'DOMContentLoaded',
    () => {

        editModalObj =
            new bootstrap.Modal(
                document.getElementById('editModal')
            );

        loadRoutes(currentFilter);

    }
);



// =====================================================
// CHARGER LES TRAJETS
// =====================================================

async function loadRoutes(
    status = 'ALL',
    btn = null
) {

    currentFilter = status;


    // -------------------------------------------------
    // Bouton actif
    // -------------------------------------------------

    if (btn) {

        document
            .querySelectorAll('.filter-buttons .btn')
            .forEach(
                b => b.classList.remove('active')
            );

        btn.classList.add('active');

    }


    // -------------------------------------------------
    // Chargement desktop
    // -------------------------------------------------

    const tbody =
        document.getElementById(
            'routesTableBody'
        );


    tbody.innerHTML = `

        <tr>

            <td
                colspan="7"
                class="text-center py-4"
            >

                <div
                    class="spinner-border text-primary"
                    role="status"
                ></div>

                <p class="mt-2 text-muted">

                    Chargement des requêtes...

                </p>

            </td>

        </tr>

    `;


    // -------------------------------------------------
    // Chargement mobile
    // -------------------------------------------------

    const mobileContainer =
        document.getElementById(
            'mobileRoutes'
        );


    mobileContainer.innerHTML = `

        <div class="text-center py-4">

            <div
                class="spinner-border text-primary"
                role="status"
            ></div>

            <p class="mt-2 text-muted">

                Chargement des requêtes...

            </p>

        </div>

    `;


    try {

        const response =
            await fetch(
                `api/admin_routes.php?status=${status}`
            );


        // -------------------------------------------------
        // Session expirée
        // -------------------------------------------------

        if (response.status === 401) {

            window.location.href =
                'login.html';

            return;

        }


        const result =
            await response.json();


        // -------------------------------------------------
        // Résultat
        // -------------------------------------------------

        if (result.success) {

            renderTable(result.data);

            renderMobileCards(result.data);

        } else {

            showAlert(
                'danger',
                result.message
            );

        }


    } catch (error) {

        console.error(error);

        showAlert(
            'danger',
            'Erreur de connexion au serveur.'
        );

    }

}



// =====================================================
// AFFICHAGE TABLEAU DESKTOP
// =====================================================

function renderTable(routes) {

    const tbody =
        document.getElementById(
            'routesTableBody'
        );


    tbody.innerHTML = '';


    if (
        !routes ||
        routes.length === 0
    ) {

        tbody.innerHTML = `

            <tr>

                <td
                    colspan="7"
                    class="text-center py-4 text-muted"
                >

                    <i
                        class="bi bi-inbox display-6 d-block mb-2"
                    ></i>

                    Aucun trajet trouvé pour ce filtre.

                </td>

            </tr>

        `;

        return;

    }


    routes.forEach(
        route => {

            const status =
                getStatusInfo(
                    route.status
                );


            const dateFormatted =
                formatDate(
                    route.created_at
                );


            const tr =
                document.createElement(
                    'tr'
                );


            tr.innerHTML = `

                <td>

                    <strong>
                        #${route.id}
                    </strong>

                </td>


                <td class="route-path">

                    <strong>
                        ${escapeHtml(
                            route.origin_name ||
                            'Inconnu'
                        )}
                    </strong>

                    <span class="text-success">
                        ➔
                    </span>

                    <strong>
                        ${escapeHtml(
                            route.destination_name ||
                            'Inconnu'
                        )}
                    </strong>

                </td>


                <td>

                    ${
                        route.transport_name

                        ? `

                            <span class="badge bg-info text-dark">

                                ${escapeHtml(
                                    route.transport_name
                                )}

                            </span>

                        `

                        :

                        `

                            <em class="text-muted">
                                Non spécifié
                            </em>

                        `
                    }

                </td>


                <td>

                    ${
                        route.estimated_price

                        ?

                        escapeHtml(
                            route.estimated_price
                        ) + ' FCFA'

                        :

                        '<em class="text-muted">-</em>'
                    }

                </td>


                <td>

                    <span
                        class="badge ${status.className}"
                    >

                        ${status.text}

                    </span>

                </td>


                <td>

                    <small class="text-muted">

                        ${dateFormatted}

                    </small>

                </td>


                <td class="text-end action-buttons">


                    <!-- MODIFIER -->

                    <button
                        class="btn btn-sm btn-outline-primary me-1"
                        title="Modifier"
                        onclick="openEditModal(
                            ${route.id},
                            '${escapeQuotes(
                                route.origin_name
                            )} ➔ ${escapeQuotes(
                                route.destination_name
                            )}',
                            '${escapeQuotes(
                                route.estimated_price || ''
                            )}',
                            '${escapeQuotes(
                                route.status
                            )}'
                        )"
                    >

                        <i class="bi bi-pencil"></i>

                    </button>


                    <!-- APPROUVER -->

                    ${
                        route.status !== 'APPROVED'

                        ?

                        `

                        <button
                            class="btn btn-sm btn-success me-1"
                            title="Approuver"
                            onclick="quickUpdateStatus(
                                ${route.id},
                                'APPROVED',
                                '${escapeQuotes(
                                    route.estimated_price || ''
                                )}'
                            )"
                        >

                            <i class="bi bi-check-lg"></i>

                        </button>

                        `

                        :

                        ''
                    }


                    <!-- REJETER -->

                    ${
                        route.status !== 'REJECTED'

                        ?

                        `

                        <button
                            class="btn btn-sm btn-danger"
                            title="Rejeter"
                            onclick="quickUpdateStatus(
                                ${route.id},
                                'REJECTED',
                                '${escapeQuotes(
                                    route.estimated_price || ''
                                )}'
                            )"
                        >

                            <i class="bi bi-x-lg"></i>

                        </button>

                        `

                        :

                        ''
                    }


                </td>

            `;


            tbody.appendChild(tr);

        }
    );

}



// =====================================================
// AFFICHAGE CARTES MOBILE
// =====================================================

function renderMobileCards(routes) {

    const container =
        document.getElementById(
            'mobileRoutes'
        );


    container.innerHTML = '';


    if (
        !routes ||
        routes.length === 0
    ) {

        container.innerHTML = `

            <div class="text-center py-5 text-muted">

                <i
                    class="bi bi-inbox display-5 d-block mb-2"
                ></i>

                <div>

                    Aucun trajet trouvé pour ce filtre.

                </div>

            </div>

        `;

        return;

    }


    routes.forEach(
        route => {

            const status =
                getStatusInfo(
                    route.status
                );


            const dateFormatted =
                formatDate(
                    route.created_at
                );


            const card =
                document.createElement(
                    'div'
                );


            card.className =
                'route-mobile-card';


            card.innerHTML = `

                <!-- HEADER -->

                <div class="route-mobile-header">

                    <span class="route-mobile-id">

                        <i class="bi bi-signpost-2"></i>

                        Trajet #${route.id}

                    </span>


                    <span
                        class="badge ${status.className}"
                    >

                        ${status.text}

                    </span>

                </div>


                <!-- PARCOURS -->

                <div class="route-mobile-path">

                    <span>

                        ${escapeHtml(
                            route.origin_name ||
                            'Inconnu'
                        )}

                    </span>


                    <span class="arrow">

                        <i class="bi bi-arrow-right"></i>

                    </span>


                    <span>

                        ${escapeHtml(
                            route.destination_name ||
                            'Inconnu'
                        )}

                    </span>

                </div>


                <!-- INFORMATIONS -->

                <div class="route-mobile-info">


                    <!-- Transport -->

                    <div class="route-info-box">

                        <span class="route-info-label">

                            <i class="bi bi-bus-front"></i>

                            Transport

                        </span>


                        <span class="route-info-value">

                            ${
                                route.transport_name

                                ?

                                escapeHtml(
                                    route.transport_name
                                )

                                :

                                'Non spécifié'
                            }

                        </span>

                    </div>


                    <!-- Prix -->

                    <div class="route-info-box">

                        <span class="route-info-label">

                            <i class="bi bi-cash-coin"></i>

                            Prix estimé

                        </span>


                        <span class="route-info-value">

                            ${
                                route.estimated_price

                                ?

                                escapeHtml(
                                    route.estimated_price
                                ) + ' FCFA'

                                :

                                '-'
                            }

                        </span>

                    </div>


                    <!-- Date -->

                    <div class="route-info-box">

                        <span class="route-info-label">

                            <i class="bi bi-calendar3"></i>

                            Créé le

                        </span>


                        <span class="route-info-value">

                            ${dateFormatted}

                        </span>

                    </div>


                    <!-- Statut -->

                    <div class="route-info-box">

                        <span class="route-info-label">

                            <i class="bi bi-info-circle"></i>

                            Statut

                        </span>


                        <span class="route-info-value">

                            ${status.text}

                        </span>

                    </div>

                </div>


                <!-- ACTIONS -->

                <div class="route-mobile-actions">


                    <!-- Modifier -->

                    <button
                        class="btn btn-outline-primary"
                        onclick="openEditModal(
                            ${route.id},
                            '${escapeQuotes(
                                route.origin_name
                            )} ➔ ${escapeQuotes(
                                route.destination_name
                            )}',
                            '${escapeQuotes(
                                route.estimated_price || ''
                            )}',
                            '${escapeQuotes(
                                route.status
                            )}'
                        )"
                    >

                        <i class="bi bi-pencil"></i>

                        Modifier

                    </button>


                    <!-- Approuver -->

                    ${
                        route.status !== 'APPROVED'

                        ?

                        `

                        <button
                            class="btn btn-success"
                            onclick="quickUpdateStatus(
                                ${route.id},
                                'APPROVED',
                                '${escapeQuotes(
                                    route.estimated_price || ''
                                )}'
                            )"
                        >

                            <i class="bi bi-check-lg"></i>

                            Approuver

                        </button>

                        `

                        :

                        `

                        <button
                            class="btn btn-success"
                            disabled
                        >

                            <i class="bi bi-check-circle"></i>

                            Approuvé

                        </button>

                        `
                    }


                    <!-- Rejeter -->

                    ${
                        route.status !== 'REJECTED'

                        ?

                        `

                        <button
                            class="btn btn-danger"
                            onclick="quickUpdateStatus(
                                ${route.id},
                                'REJECTED',
                                '${escapeQuotes(
                                    route.estimated_price || ''
                                )}'
                            )"
                        >

                            <i class="bi bi-x-lg"></i>

                            Rejeter

                        </button>

                        `

                        :

                        `

                        <button
                            class="btn btn-danger"
                            disabled
                        >

                            <i class="bi bi-x-circle"></i>

                            Rejeté

                        </button>

                        `
                    }

                </div>

            `;


            container.appendChild(card);

        }
    );

}



// =====================================================
// INFORMATIONS DU STATUT
// =====================================================

function getStatusInfo(status) {

    if (status === 'APPROVED') {

        return {
            className: 'badge-approved',
            text: 'Approuvé'
        };

    }


    if (status === 'REJECTED') {

        return {
            className: 'badge-rejected',
            text: 'Rejeté'
        };

    }


    if (status === 'EXPIRED') {

        return {
            className: 'badge-expired',
            text: 'Expiré'
        };

    }


    return {
        className: 'badge-pending',
        text: 'En attente'
    };

}



// =====================================================
// ÉCHAPPER LES APOSTROPHES
// =====================================================

function escapeQuotes(str) {

    return String(
        str || ''
    )
    .replace(/\\/g, '\\\\')
    .replace(/'/g, "\\'");

}



// =====================================================
// ÉCHAPPER HTML
// =====================================================

function escapeHtml(value) {

    const div =
        document.createElement(
            'div'
        );

    div.textContent =
        value == null
        ? ''
        : String(value);

    return div.innerHTML;

}



// =====================================================
// FORMATAGE DATE
// =====================================================

function formatDate(date) {

    if (!date) {

        return '-';

    }


    const parsedDate =
        new Date(
            String(date).replace(
                ' ',
                'T'
            )
        );


    if (
        isNaN(
            parsedDate.getTime()
        )
    ) {

        return date;

    }


    return parsedDate.toLocaleString(
        'fr-FR',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }
    );

}



// =====================================================
// OUVRIR LA MODALE D'ÉDITION
// =====================================================

function openEditModal(
    id,
    title,
    price,
    status
) {

    document.getElementById(
        'editRouteId'
    ).value = id;


    document.getElementById(
        'editRouteTitle'
    ).value = title;


    document.getElementById(
        'editPrice'
    ).value = price;


    document.getElementById(
        'editStatus'
    ).value = status;


    editModalObj.show();

}



// =====================================================
// FORMULAIRE D'ÉDITION
// =====================================================

document
    .getElementById(
        'editRouteForm'
    )
    .addEventListener(
        'submit',
        async function(e) {

            e.preventDefault();


            const route_id =
                document.getElementById(
                    'editRouteId'
                ).value;


            const estimated_price =
                document.getElementById(
                    'editPrice'
                ).value;


            const status =
                document.getElementById(
                    'editStatus'
                ).value;


            await updateRoute(
                route_id,
                status,
                estimated_price
            );


            editModalObj.hide();

        }
    );



// =====================================================
// MISE À JOUR RAPIDE
// =====================================================

async function quickUpdateStatus(
    route_id,
    status,
    currentPrice
) {

    await updateRoute(
        route_id,
        status,
        currentPrice
    );

}



// =====================================================
// ENVOI À L'API PHP
// =====================================================

async function updateRoute(
    route_id,
    status,
    estimated_price
) {

    try {

        const response =
            await fetch(
                'api/admin_routes.php',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json'
                    },

                    body: JSON.stringify({
                        route_id,
                        status,
                        estimated_price
                    })
                }
            );


        // -------------------------------------------------
        // SESSION EXPIRÉE
        // -------------------------------------------------

        if (response.status === 401) {

            window.location.href =
                'login.html';

            return;

        }


        const result =
            await response.json();


        if (result.success) {

            showAlert(
                'success',
                result.message
            );


            loadRoutes(
                currentFilter
            );

        } else {

            showAlert(
                'danger',
                result.message
            );

        }


    } catch (error) {

        console.error(error);

        showAlert(
            'danger',
            'Erreur lors de la mise à jour.'
        );

    }

}



// =====================================================
// DÉCONNEXION
// =====================================================

async function logout() {

    try {

        await fetch(
            'api/admin_check_auth.php?action=logout'
        );

    } catch (error) {

        console.error(error);

    }


    window.location.href =
        'login.html';

}



// =====================================================
// AFFICHER UNE ALERTE
// =====================================================

function showAlert(
    type,
    message
) {

    const alertBox =
        document.getElementById(
            'alertBox'
        );


    const alertMessage =
        document.getElementById(
            'alertMessage'
        );


    alertBox.className =
        `alert alert-${type} alert-dismissible fade show`;


    alertMessage.textContent =
        message;


    alertBox.classList.remove(
        'd-none'
    );


    // Masquer automatiquement
    setTimeout(
        () => {

            hideAlert();

        },
        5000
    );

}



// =====================================================
// MASQUER ALERTE
// =====================================================

function hideAlert() {

    document
        .getElementById(
            'alertBox'
        )
        .classList.add(
            'd-none'
        );

}

</script>


</body>

</html>