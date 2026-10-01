<?php
session_start();

// Vérification de l'authentification admin
if (
    !($_SESSION['admin_logged_in'] ?? false) ||
    !in_array($_SESSION['admin_role'] ?? '', ['ADMIN', 'MODERATOR'])
) {
    header("Location: login.html");
    exit;
}

require_once "config/database.php";

// Traitement du changement de statut si un formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['suggestion_id'], $_POST['new_status'])) {
    $id = intval($_POST['suggestion_id']);
    $new_status = $_POST['new_status'];

    if (in_array($new_status, ['PENDING', 'REVIEWED', 'IMPLEMENTED'])) {
        $updateStmt = $pdo->prepare("UPDATE suggestions SET status = :status WHERE id = :id");
        $updateStmt->execute([':status' => $new_status, ':id' => $id]);
    }
    header("Location: suggestions.php");
    exit;
}

// Récupération de toutes les suggestions (sans jointure pour éviter les erreurs de colonnes)
try {
    $stmt = $pdo->query("
        SELECT * 
        FROM suggestions 
        ORDER BY created_at DESC
    ");
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des suggestions : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DEY DEM - Gestion des Suggestions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

  <!-- Navbar -->
  <nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
    <div class="container">
      <span class="navbar-brand fw-bold">
        <i class="bi bi-speedometer2 me-2"></i>DEY DEM - Gestion des Suggestions
      </span>
      <a href="admin.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Retour au tableau de bord
      </a>
    </div>
  </nav>

  <div class="container py-3">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-dark"><i class="bi bi-lightbulb text-warning me-2"></i>Suggestions des utilisateurs</h2>
        <p class="text-muted small mb-0">Espace strictement réservé à l'administration.</p>
      </div>
      <span class="badge bg-secondary fs-6 px-3 py-2">
        Total : <?= isset($suggestions) ? count($suggestions) : 0 ?>
      </span>
    </div>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger shadow-sm"><?= $error ?></div>
    <?php endif; ?>

    <!-- Tableau des suggestions -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
              <tr>
                <th class="py-3 ps-3">#ID</th>
                <th class="py-3">Auteur / Source</th>
                <th class="py-3" style="width: 40%;">Suggestion</th>
                <th class="py-3">Date</th>
                <th class="py-3">Statut</th>
                <th class="py-3 text-end pe-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($suggestions)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4 d-block mb-2 text-secondary"></i>
                    Aucune suggestion reçue pour le moment.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($suggestions as $s): ?>
                  <tr>
                    <td class="ps-3 fw-bold text-muted">#<?= $s['id'] ?></td>
                    <td>
                      <?php if (!empty($s['user_id'])): ?>
                        <span class="fw-semibold text-primary"><i class="bi bi-person-fill me-1"></i>Utilisateur ID #<?= $s['user_id'] ?></span>
                      <?php else: ?>
                        <span class="text-muted fst-italic">Visiteur Anonyme</span>
                        <div class="small text-muted font-monospace" style="font-size: 0.75rem;">Ses: <?= substr($s['session_token'] ?? '', 0, 8) ?>...</div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($s['content'])) ?></p>
                    </td>
                    <td>
                      <small class="text-muted"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></small>
                    </td>
                    <td>
                      <?php 
                        $statusClass = match($s['status']) {
                            'IMPLEMENTED' => 'bg-success',
                            'REVIEWED' => 'bg-info text-dark',
                            default => 'bg-warning text-dark'
                        };
                        $statusLabel = match($s['status']) {
                            'IMPLEMENTED' => 'Réalisé',
                            'REVIEWED' => 'Étudié',
                            default => 'En attente'
                        };
                      ?>
                      <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                    </td>
                    <td class="text-end pe-3">
                      <form method="POST" class="d-inline-flex gap-1 align-items-center justify-content-end">
                        <input type="hidden" name="suggestion_id" value="<?= $s['id'] ?>">
                        <select name="new_status" class="form-select form-select-sm" style="width: 130px;" onchange="this.form.submit()">
                          <option value="PENDING" <?= $s['status'] === 'PENDING' ? 'selected' : '' ?>>En attente</option>
                          <option value="REVIEWED" <?= $s['status'] === 'REVIEWED' ? 'selected' : '' ?>>Étudié</option>
                          <option value="IMPLEMENTED" <?= $s['status'] === 'IMPLEMENTED' ? 'selected' : '' ?>>Réalisé</option>
                        </select>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>