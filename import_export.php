<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

// Gestione export
if (isset($_GET['export'])) {
    $stmt = $pdo->query("SELECT title, description, category, sort_order FROM checklist_items ORDER BY category, sort_order");
    $items = $stmt->fetchAll();
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="wordpress_checklist_' . date('Y-m-d') . '.json"');
    echo json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Gestione reset checklist predefinita
if (isset($_POST['reset_default'])) {
    try {
        $pdo->beginTransaction();
        
        // Elimina tutto
        $pdo->exec("DELETE FROM project_checks");
        $pdo->exec("DELETE FROM checklist_items");
        
        // Reinserisce i valori predefiniti dal file JSON
        $default_json = file_get_contents('default_checklist_template.json');
        $default_items = json_decode($default_json, true);
        
        $stmt = $pdo->prepare("INSERT INTO checklist_items (title, description, category, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($default_items as $item) {
            $stmt->execute([
                $item['title'],
                $item['description'],
                $item['category'],
                $item['sort_order']
            ]);
        }
        
        $pdo->commit();
        $message = "Checklist ripristinata ai valori predefiniti (" . count($default_items) . " controlli)";
    } catch (PDOException $e) {
        $pdo->rollback();
        $error = 'Errore durante il ripristino: ' . $e->getMessage();
    }
}

// Gestione import
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);
        
        if ($data && is_array($data)) {
            try {
                $pdo->beginTransaction();
                
                // Se richiesto, elimina i controlli esistenti
                if (isset($_POST['replace_existing'])) {
                    $pdo->exec("DELETE FROM project_checks");
                    $pdo->exec("DELETE FROM checklist_items");
                }
                
                $imported = 0;
                foreach ($data as $item) {
                    if (isset($item['title']) && isset($item['category'])) {
                        $stmt = $pdo->prepare("INSERT INTO checklist_items (title, description, category, sort_order) VALUES (?, ?, ?, ?)");
                        $stmt->execute([
                            $item['title'],
                            $item['description'] ?? '',
                            $item['category'],
                            $item['sort_order'] ?? 0
                        ]);
                        $imported++;
                    }
                }
                
                $pdo->commit();
                $message = "Importati con successo $imported controlli";
            } catch (PDOException $e) {
                $pdo->rollback();
                $error = 'Errore durante l\'importazione: ' . $e->getMessage();
            }
        } else {
            $error = 'File JSON non valido';
        }
    } else {
        $error = 'Errore durante l\'upload del file';
    }
}

// Conta controlli esistenti
$stmt = $pdo->query("SELECT COUNT(*) FROM checklist_items");
$total_items = $stmt->fetchColumn();

$page_title = 'Import/Export Checklist - WordPress Checklist';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Import/Export Checklist</h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlEscape($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlEscape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Export -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="mb-0">Esporta Checklist</h4>
                </div>
                <div class="card-body">
                    <p>Esporta la checklist attuale in formato JSON per condividerla o fare backup.</p>
                    <div class="alert alert-info">
                        <strong>Controlli attuali:</strong> <?= $total_items ?>
                    </div>
                    
                    <?php if ($total_items > 0): ?>
                        <a href="?export=1" class="btn btn-primary">
                            Scarica Checklist (JSON)
                        </a>
                    <?php else: ?>
                        <p class="text-muted">Nessun controllo da esportare</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Import -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4 class="mb-0">Importa Checklist</h4>
                </div>
                <div class="card-body">
                    <p>Importa una checklist da file JSON.</p>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">File JSON</label>
                            <input type="file" class="form-control" id="import_file" name="import_file" 
                                   accept=".json" required>
                        </div>
                        
                        <?php if ($total_items > 0): ?>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="replace_existing" 
                                           name="replace_existing" value="1">
                                    <label class="form-check-label" for="replace_existing">
                                        <strong>Sostituisci checklist esistente</strong>
                                    </label>
                                </div>
                                <div class="alert alert-warning mt-2">
                                    <small>
                                        <strong>ATTENZIONE:</strong> Questa opzione eliminerà tutti i controlli esistenti 
                                        e tutti i completamenti nei progetti!
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-primary">Importa Checklist</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Istruzioni -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Formato File JSON</h5>
                </div>
                <div class="card-body">
                    <p>Il file JSON deve contenere un array di oggetti con questa struttura:</p>
                    <pre class="bg-light p-3 rounded"><code>[
  {
    "title": "Nome del controllo",
    "description": "Descrizione opzionale",
    "category": "Categoria",
    "sort_order": 1
  }
]</code></pre>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Campi obbligatori:</strong></p>
                            <ul class="list-unstyled">
                                <li><code>title</code> - Nome del controllo</li>
                                <li><code>category</code> - Categoria di appartenenza</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Campi opzionali:</strong></p>
                            <ul class="list-unstyled">
                                <li><code>description</code> - Descrizione dettagliata</li>
                                <li><code>sort_order</code> - Ordine di visualizzazione</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reset -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Reset Checklist</h5>
                </div>
                <div class="card-body">
                    <p>Ripristina la checklist WordPress predefinita (30 controlli).</p>
                    <div class="alert alert-warning">
                        <small><strong>ATTENZIONE:</strong> Eliminerà tutti i controlli attuali e i completamenti nei progetti!</small>
                    </div>
                    <form method="POST" onsubmit="return confirm('Sei sicuro di voler ripristinare la checklist predefinita? Questa azione eliminerà tutti i controlli personalizzati e i completamenti!')">
                        <button type="submit" name="reset_default" value="1" class="btn btn-warning">
                            Ripristina Predefinita
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>