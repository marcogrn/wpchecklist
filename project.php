<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$project_id) {
    header('Location: index.php');
    exit();
}

// Recupera dettagli progetto
$stmt = $pdo->prepare("SELECT p.*, u.username as created_by_name FROM projects p LEFT JOIN users u ON p.created_by = u.id WHERE p.id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: index.php');
    exit();
}

// Gestione toggle checkbox
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_check'])) {
    $item_id = (int)$_POST['item_id'];
    
    // Verifica se il controllo è già stato fatto
    $stmt = $pdo->prepare("SELECT id FROM project_checks WHERE project_id = ? AND checklist_item_id = ?");
    $stmt->execute([$project_id, $item_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Rimuovi il controllo
        $stmt = $pdo->prepare("DELETE FROM project_checks WHERE project_id = ? AND checklist_item_id = ?");
        $stmt->execute([$project_id, $item_id]);
    } else {
        // Aggiungi il controllo
        $stmt = $pdo->prepare("INSERT INTO project_checks (project_id, checklist_item_id, checked_by) VALUES (?, ?, ?)");
        $stmt->execute([$project_id, $item_id, $user['id']]);
    }
    
    header("Location: project.php?id=$project_id");
    exit();
}

// Recupera checklist con stato di completamento
$stmt = $pdo->prepare("
    SELECT ci.*, 
           pc.checked_at,
           u.username as checked_by_name
    FROM checklist_items ci
    LEFT JOIN project_checks pc ON ci.id = pc.checklist_item_id AND pc.project_id = ?
    LEFT JOIN users u ON pc.checked_by = u.id
    ORDER BY ci.category, ci.sort_order
");
$stmt->execute([$project_id]);
$checklist_items = $stmt->fetchAll();

// Raggruppa per categoria
$categories = [];
foreach ($checklist_items as $item) {
    $categories[$item['category']][] = $item;
}

// Calcola statistiche
$total_items = count($checklist_items);
$completed_items = array_reduce($checklist_items, function($count, $item) {
    return $count + ($item['checked_at'] ? 1 : 0);
}, 0);
$completion_percentage = $total_items > 0 ? ($completed_items / $total_items) * 100 : 0;

$page_title = htmlEscape($project['name']) . ' - WordPress Checklist';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <!-- Header progetto -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2"><?= htmlEscape($project['name']) ?></h2>
                    <p class="text-muted mb-2">
                        <a href="<?= htmlEscape($project['link']) ?>" target="_blank" class="text-decoration-none">
                            <?= htmlEscape($project['link']) ?>
                        </a>
                    </p>
                    <small class="text-muted">
                        Creato da <?= htmlEscape($project['created_by_name']) ?> il 
                        <?= date('d/m/Y H:i', strtotime($project['created_at'])) ?>
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <h4 class="mb-2">
                        <span class="badge bg-primary">
                            <?= $completed_items ?>/<?= $total_items ?> Completati
                        </span>
                    </h4>
                    <div class="progress mb-2" style="height: 12px;">
                        <div class="progress-bar" style="width: <?= $completion_percentage ?>%"></div>
                    </div>
                    <small class="text-muted"><?= number_format($completion_percentage, 1) ?>% completato</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Checklist -->
    <?php foreach ($categories as $category => $items): ?>
        <div class="card mb-3">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlEscape($category) ?></h5>
                    <span class="badge bg-secondary">
                        <?= array_reduce($items, function($count, $item) { return $count + ($item['checked_at'] ? 1 : 0); }, 0) ?>/<?= count($items) ?>
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php foreach ($items as $item): ?>
                    <div class="p-3 border-bottom <?= $item['checked_at'] ? 'bg-light' : '' ?>">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       <?= $item['checked_at'] ? 'checked' : '' ?>
                                       onchange="this.form.submit()">
                                <input type="hidden" name="toggle_check" value="1">
                                <label class="form-check-label w-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlEscape($item['title']) ?></h6>
                                            <?php if ($item['description']): ?>
                                                <p class="text-muted small mb-0">
                                                    <?= htmlEscape($item['description']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($item['checked_at']): ?>
                                            <div class="text-end ms-3">
                                                <small class="text-muted">
                                                    <strong><?= htmlEscape($item['checked_by_name']) ?></strong><br>
                                                    <?= date('d/m/Y H:i', strtotime($item['checked_at'])) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="text-center mt-4 mb-4">
        <a href="index.php" class="btn btn-primary me-2">Torna ai Progetti</a>
        <?php if ($project['created_by'] == $user['id']): ?>
            <a href="delete_project.php?id=<?= $project['id'] ?>" class="btn btn-outline-danger">
                Elimina Progetto
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>