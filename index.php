<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Recupera tutti i progetti
$stmt = $pdo->prepare("
    SELECT p.*, u.username as created_by_name,
           COUNT(pc.id) as completed_checks,
           (SELECT COUNT(*) FROM checklist_items) as total_checks
    FROM projects p 
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN project_checks pc ON p.id = pc.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$projects = $stmt->fetchAll();

$page_title = 'Dashboard - WordPress Checklist';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            Progetto eliminato con successo.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>I tuoi Progetti</h2>
        <a href="new_project.php" class="btn btn-primary">
            <i class="me-1">+</i> Nuovo Progetto
        </a>
    </div>

    <?php if (empty($projects)): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <h4 class="text-muted mb-3">Nessun progetto creato</h4>
                        <p class="text-muted mb-4">Inizia creando il tuo primo progetto WordPress per utilizzare la checklist</p>
                        <a href="new_project.php" class="btn btn-primary btn-lg">
                            Crea Primo Progetto
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($projects as $project): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlEscape($project['name']) ?></h5>
                            
                            <p class="card-text">
                                <a href="<?= htmlEscape($project['link']) ?>" target="_blank" class="text-decoration-none small">
                                    <?= htmlEscape($project['link']) ?>
                                </a>
                            </p>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Progresso</small>
                                    <small class="text-muted">
                                        <?= $project['completed_checks'] ?>/<?= $project['total_checks'] ?>
                                    </small>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <?php 
                                    $percentage = $project['total_checks'] > 0 ? 
                                        ($project['completed_checks'] / $project['total_checks']) * 100 : 0;
                                    ?>
                                    <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                                </div>
                                <small class="text-muted"><?= number_format($percentage, 1) ?>% completato</small>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                                </small>
                                <a href="project.php?id=<?= $project['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    Apri Progetto
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>