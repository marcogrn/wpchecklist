<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header('Location: index.php');
    exit();
}

// Verifica che il progetto esista e appartenga all'utente
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND created_by = ?");
$stmt->execute([$project_id, $user['id']]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: index.php');
    exit();
}

// Gestione eliminazione
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Elimina prima i controlli associati (per referential integrity)
        $stmt = $pdo->prepare("DELETE FROM project_checks WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        // Poi elimina il progetto
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        
        header('Location: index.php?deleted=1');
        exit();
    } catch (PDOException $e) {
        $error = 'Errore durante l\'eliminazione del progetto';
    }
}

$page_title = 'Elimina Progetto - WordPress Checklist';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Elimina Progetto</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlEscape($error) ?></div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <strong>Attenzione!</strong> Stai per eliminare definitivamente il progetto:
                    </div>

                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlEscape($project['name']) ?></h5>
                            <p class="card-text">
                                <strong>Link:</strong> 
                                <a href="<?= htmlEscape($project['link']) ?>" target="_blank">
                                    <?= htmlEscape($project['link']) ?>
                                </a>
                            </p>
                            <small class="text-muted">
                                Creato il <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?>
                            </small>
                        </div>
                    </div>

                    <div class="alert alert-danger">
                        <strong>Questa azione non può essere annullata.</strong><br>
                        Tutti i controlli associati al progetto verranno eliminati.
                    </div>

                    <form method="POST">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="project.php?id=<?= $project['id'] ?>" class="btn btn-secondary me-md-2">
                                Annulla
                            </a>
                            <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">
                                Elimina Definitivamente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>