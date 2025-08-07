<?php
require_once '../config.php';
requireAdmin();

$user = getCurrentUser();
$message = '';
$error = '';

// Gestione azioni
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($user_id && $user_id !== $user['id']) { // Non può modificare se stesso
        switch ($action) {
            case 'approve':
                try {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->execute([$user['id'], $user_id]);
                    
                    // Invia email di notifica
                    $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();
                    
                    if ($user_data) {
                        sendApprovalNotificationEmail($user_data['email'], $user_data['username'], true);
                    }
                    
                    $message = 'Utente approvato con successo';
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'approvazione';
                }
                break;
                
            case 'reject':
                try {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
                    $stmt->execute([$user['id'], $user_id]);
                    
                    // Invia email di notifica
                    $stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();
                    
                    if ($user_data) {
                        sendApprovalNotificationEmail($user_data['email'], $user_data['username'], false);
                    }
                    
                    $message = 'Utente rifiutato';
                } catch (PDOException $e) {
                    $error = 'Errore durante il rifiuto';
                }
                break;
                
            case 'change_role':
                $new_role = $_POST['role'];
                if (in_array($new_role, ['user', 'admin'])) {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $user_id]);
                        $message = 'Ruolo modificato con successo';
                    } catch (PDOException $e) {
                        $error = 'Errore durante la modifica del ruolo';
                    }
                }
                break;
                
            case 'delete':
                try {
                    $pdo->beginTransaction();
                    
                    // Elimina i dati collegati
                    $stmt = $pdo->prepare("DELETE FROM user_dismissed_messages WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM project_checks WHERE checked_by = ?");
                    $stmt->execute([$user_id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM projects WHERE created_by = ?");
                    $stmt->execute([$user_id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    $pdo->commit();
                    $message = 'Utente eliminato con successo';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Errore durante l\'eliminazione';
                }
                break;
        }
    }
}

// Recupera tutti gli utenti con statistiche
$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT username FROM users WHERE id = u.approved_by) as approved_by_name,
           (SELECT COUNT(*) FROM projects WHERE created_by = u.id) as project_count,
           (SELECT COUNT(*) FROM project_checks pc JOIN projects p ON pc.project_id = p.id WHERE p.created_by = u.id) as check_count
    FROM users u
    ORDER BY 
        CASE 
            WHEN u.status = 'pending' THEN 1 
            WHEN u.status = 'approved' THEN 2 
            ELSE 3 
        END,
        u.created_at DESC
");
$users = $stmt->fetchAll();

$page_title = 'Gestione Utenti - WordPress Checklist';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestione Utenti</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Gestione Utenti</li>
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

    <!-- Lista Utenti -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Utente</th>
                            <th>Stato</th>
                            <th>Ruolo</th>
                            <th>Statistiche</th>
                            <th>Registrazione</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $usr): ?>
                            <tr class="<?= $usr['status'] === 'pending' ? 'table-warning' : '' ?>">
                                <td>
                                    <div>
                                        <strong><?= htmlEscape($usr['username']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlEscape($usr['email']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'pending' => 'warning',
                                        'approved' => 'success', 
                                        'rejected' => 'danger'
                                    ];
                                    $status_labels = [
                                        'pending' => 'In Attesa',
                                        'approved' => 'Approvato',
                                        'rejected' => 'Rifiutato'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $status_classes[$usr['status']] ?>">
                                        <?= $status_labels[$usr['status']] ?>
                                    </span>
                                    <?php if ($usr['approved_by_name']): ?>
                                        <br><small class="text-muted">
                                            da <?= htmlEscape($usr['approved_by_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usr['id'] === $user['id']): ?>
                                        <span class="badge bg-<?= $usr['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                            <?= ucfirst($usr['role']) ?>
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                            <select name="role" class="form-select form-select-sm" 
                                                    onchange="this.form.submit()">
                                                <option value="user" <?= $usr['role'] === 'user' ? 'selected' : '' ?>>
                                                    User
                                                </option>
                                                <option value="admin" <?= $usr['role'] === 'admin' ? 'selected' : '' ?>>
                                                    Admin
                                                </option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <strong><?= $usr['project_count'] ?></strong> progetti<br>
                                        <strong><?= $usr['check_count'] ?></strong> controlli
                                    </small>
                                </td>
                                <td>
                                    <small>
                                        <?= date('d/m/Y', strtotime($usr['created_at'])) ?>
                                        <br>
                                        <?= date('H:i', strtotime($usr['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($usr['id'] !== $user['id']): ?>
                                        <div class="btn-group" role="group">
                                            <?php if ($usr['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm" 
                                                            onclick="return confirm('Approvare questo utente?')">
                                                        Approva
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('Rifiutare questo utente?')">
                                                        Rifiuta
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('ATTENZIONE: Eliminare questo utente cancellerà tutti i suoi progetti e dati. Continuare?')">
                                                    Elimina
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>