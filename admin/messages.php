<?php
require_once '../config.php';
requireAdmin();

$user = getCurrentUser();
$message = '';
$error = '';

// Gestione azioni
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $msg_text = trim($_POST['message']);
            $msg_type = $_POST['type'];
            
            if ($msg_text && in_array($msg_type, ['info', 'warning', 'success', 'danger'])) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO admin_messages (message, type, created_by) VALUES (?, ?, ?)");
                    $stmt->execute([$msg_text, $msg_type, $user['id']]);
                    $message = 'Messaggio aggiunto con successo';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'aggiunta del messaggio';
                }
            } else {
                $error = 'Dati non validi';
            }
            break;
            
        case 'toggle':
            $msg_id = (int)$_POST['message_id'];
            if ($msg_id) {
                try {
                    $stmt = $pdo->prepare("UPDATE admin_messages SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$msg_id]);
                    $message = 'Stato messaggio modificato';
                } catch (PDOException $e) {
                    $error = 'Errore durante la modifica';
                }
            }
            break;
            
        case 'delete':
            $msg_id = (int)$_POST['message_id'];
            if ($msg_id) {
                try {
                    $pdo->beginTransaction();
                    
                    // Elimina prima i dismissal
                    $stmt = $pdo->prepare("DELETE FROM user_dismissed_messages WHERE message_id = ?");
                    $stmt->execute([$msg_id]);
                    
                    // Poi il messaggio
                    $stmt = $pdo->prepare("DELETE FROM admin_messages WHERE id = ?");
                    $stmt->execute([$msg_id]);
                    
                    $pdo->commit();
                    $message = 'Messaggio eliminato';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Errore durante l\'eliminazione';
                }
            }
            break;
            
        case 'clear_dismissals':
            $msg_id = (int)$_POST['message_id'];
            if ($msg_id) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM user_dismissed_messages WHERE message_id = ?");
                    $stmt->execute([$msg_id]);
                    $message = 'Dismissal cancellati - il messaggio apparirà di nuovo a tutti gli utenti';
                } catch (PDOException $e) {
                    $error = 'Errore durante la cancellazione';
                }
            }
            break;
    }
}

// Recupera tutti i messaggi con statistiche
$stmt = $pdo->query("
    SELECT am.*, 
           u.username as created_by_name,
           (SELECT COUNT(*) FROM user_dismissed_messages WHERE message_id = am.id) as dismissed_count,
           (SELECT COUNT(*) FROM users WHERE status = 'approved') as total_users
    FROM admin_messages am
    LEFT JOIN users u ON am.created_by = u.id
    ORDER BY am.created_at DESC
");
$messages = $stmt->fetchAll();

$page_title = 'Messaggi Globali - WordPress Checklist';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Messaggi Globali</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Messaggi Globali</li>
            </ol>
        </nav>
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

    <!-- Form Nuovo Messaggio -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Nuovo Messaggio Globale</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Info:</strong> I messaggi globali appaiono come alert nella dashboard di tutti gli utenti. 
                Gli utenti possono chiudere i messaggi, ma tu puoi renderli nuovamente visibili.
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="message" class="form-label">Messaggio</label>
                            <textarea class="form-control" id="message" name="message" rows="3" 
                                      placeholder="Scrivi il messaggio che apparirà a tutti gli utenti..." required></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="type" class="form-label">Tipo di Alert</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="info">Info (Blu)</option>
                                <option value="success">Successo (Verde)</option>
                                <option value="warning">Avviso (Giallo)</option>
                                <option value="danger">Pericolo (Rosso)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Aggiungi Messaggio</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista Messaggi -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Messaggi Esistenti</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($messages)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">Nessun messaggio globale creato</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="border-bottom p-3">
                        <div class="row align-items-start">
                            <div class="col-md-8">
                                <!-- Preview del messaggio -->
                                <div class="alert alert-<?= $msg['type'] ?> mb-2">
                                    <strong>Messaggio dall'Amministratore:</strong>
                                    <?= nl2br(htmlEscape($msg['message'])) ?>
                                </div>
                                
                                <small class="text-muted">
                                    Creato da <strong><?= htmlEscape($msg['created_by_name']) ?></strong> 
                                    il <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <!-- Statistiche -->
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <strong>Stato:</strong> 
                                        <span class="badge bg-<?= $msg['is_active'] ? 'success' : 'secondary' ?>">
                                            <?= $msg['is_active'] ? 'Attivo' : 'Disattivo' ?>
                                        </span>
                                    </small>
                                </div>
                                
                                <?php if ($msg['is_active']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <strong>Visualizzazioni:</strong> 
                                            <?= $msg['total_users'] - $msg['dismissed_count'] ?>/<?= $msg['total_users'] ?> utenti
                                        </small>
                                        <div class="progress" style="height: 4px;">
                                            <?php 
                                            $view_percentage = $msg['total_users'] > 0 ? 
                                                (($msg['total_users'] - $msg['dismissed_count']) / $msg['total_users']) * 100 : 0;
                                            ?>
                                            <div class="progress-bar bg-info" style="width: <?= $view_percentage ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Azioni -->
                                <div class="btn-group-vertical w-100">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" class="btn btn-outline-<?= $msg['is_active'] ? 'warning' : 'success' ?> btn-sm">
                                            <?= $msg['is_active'] ? 'Disattiva' : 'Attiva' ?>
                                        </button>
                                    </form>
                                    
                                    <?php if ($msg['dismissed_count'] > 0): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="clear_dismissals">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" class="btn btn-outline-info btn-sm mt-1" 
                                                    onclick="return confirm('Rendere nuovamente visibile questo messaggio a tutti gli utenti?')">
                                                Rendi Visibile
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm mt-1" 
                                                onclick="return confirm('Eliminare definitivamente questo messaggio?')">
                                            Elimina
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>