<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

// Gestione azioni
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = trim($_POST['category']);
            
            if ($title && $category) {
                try {
                    // Trova il prossimo sort_order per la categoria
                    $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM checklist_items WHERE category = ?");
                    $stmt->execute([$category]);
                    $max_order = $stmt->fetchColumn() ?: 0;
                    
                    $stmt = $pdo->prepare("INSERT INTO checklist_items (title, description, category, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $category, $max_order + 1]);
                    $message = 'Controllo aggiunto con successo';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'aggiunta del controllo';
                }
            } else {
                $error = 'Titolo e categoria sono obbligatori';
            }
            break;
            
        case 'edit':
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $category = trim($_POST['category']);
            
            if ($id && $title && $category) {
                try {
                    $stmt = $pdo->prepare("UPDATE checklist_items SET title = ?, description = ?, category = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $category, $id]);
                    $message = 'Controllo modificato con successo';
                } catch (PDOException $e) {
                    $error = 'Errore durante la modifica del controllo';
                }
            } else {
                $error = 'Dati non validi per la modifica';
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            if ($id) {
                try {
                    // Elimina prima i riferimenti nei project_checks
                    $stmt = $pdo->prepare("DELETE FROM project_checks WHERE checklist_item_id = ?");
                    $stmt->execute([$id]);
                    
                    // Poi elimina il controllo
                    $stmt = $pdo->prepare("DELETE FROM checklist_items WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Controllo eliminato con successo';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'eliminazione del controllo';
                }
            }
            break;
    }
}

// Recupera tutti i controlli raggruppati per categoria
$stmt = $pdo->query("
    SELECT * FROM checklist_items 
    ORDER BY category, sort_order
");
$all_items = $stmt->fetchAll();

$categories = [];
foreach ($all_items as $item) {
    $categories[$item['category']][] = $item;
}

// Recupera tutte le categorie esistenti per il dropdown
$stmt = $pdo->query("SELECT DISTINCT category FROM checklist_items ORDER BY category");
$existing_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Gestione Checklist - WordPress Checklist';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestione Checklist</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="me-1">+</i> Aggiungi Controllo
        </button>
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

    <?php if (empty($categories)): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <h4 class="text-muted mb-3">Nessun controllo presente</h4>
                        <p class="text-muted mb-4">Inizia aggiungendo il primo controllo alla checklist</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
                            Aggiungi Primo Controllo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($categories as $category => $items): ?>
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= htmlEscape($category) ?></h5>
                        <span class="badge bg-secondary"><?= count($items) ?> controlli</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($items as $item): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlEscape($item['title']) ?></h6>
                                    <?php if ($item['description']): ?>
                                        <p class="text-muted small mb-0">
                                            <?= htmlEscape($item['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-3">
                                    <button class="btn btn-outline-secondary btn-sm me-1" 
                                            onclick="editItem(<?= htmlEscape(json_encode($item)) ?>)">
                                        Modifica
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteItem(<?= $item['id'] ?>, '<?= htmlEscape($item['title']) ?>')">
                                        Elimina
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Aggiungi Controllo -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aggiungi Nuovo Controllo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="add_title" class="form-label">Titolo *</label>
                        <input type="text" class="form-control" id="add_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="add_category" class="form-label">Categoria *</label>
                        <input type="text" class="form-control" id="add_category" name="category" 
                               list="categories" required>
                        <datalist id="categories">
                            <?php foreach ($existing_categories as $cat): ?>
                                <option value="<?= htmlEscape($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-text">Inserisci una categoria esistente o creane una nuova</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Aggiungi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Controllo -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Controllo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Titolo *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Descrizione</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Categoria *</label>
                        <input type="text" class="form-control" id="edit_category" name="category" 
                               list="categories" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editItem(item) {
        document.getElementById('edit_id').value = item.id;
        document.getElementById('edit_title').value = item.title;
        document.getElementById('edit_description').value = item.description || '';
        document.getElementById('edit_category').value = item.category;
        
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    }

    function deleteItem(id, title) {
        if (confirm('Sei sicuro di voler eliminare il controllo "' + title + '"?\n\nQuesto rimuoverà anche tutti i completamenti associati nei progetti.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>