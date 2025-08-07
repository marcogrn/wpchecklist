<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        if ($username && $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Inserire un indirizzo email valido';
            } else {
                // Verifica che username ed email non siano già usati da altri
                $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $user['id']]);
                
                if ($stmt->fetch()) {
                    $error = 'Username o email già utilizzati da un altro utente';
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $user['id']]);
                        
                        $_SESSION['username'] = $username;
                        $user['username'] = $username;
                        $user['email'] = $email;
                        
                        $success = 'Profilo aggiornato con successo';
                    } catch (PDOException $e) {
                        $error = 'Errore durante l\'aggiornamento del profilo';
                    }
                }
            }
        } else {
            $error = 'Username e email sono obbligatori';
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($current_password && $new_password && $confirm_password) {
            if (!password_verify($current_password, $user['password'])) {
                $error = 'Password attuale non corretta';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Le nuove password non corrispondono';
            } elseif (strlen($new_password) < 6) {
                $error = 'La nuova password deve essere di almeno 6 caratteri';
            } else {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user['id']]);
                    
                    $success = 'Password cambiata con successo';
                } catch (PDOException $e) {
                    $error = 'Errore durante il cambio password';
                }
            }
        } else {
            $error = 'Compilare tutti i campi password';
        }
    }
}

// Statistiche utente
$stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE created_by = ?");
$stmt->execute([$user['id']]);
$total_projects = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM project_checks pc 
    JOIN projects p ON pc.project_id = p.id 
    WHERE p.created_by = ?
");
$stmt->execute([$user['id']]);
$total_checks = $stmt->fetchColumn();

$page_title = 'Il mio Profilo - WordPress Checklist';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Il mio Profilo</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= htmlEscape($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= htmlEscape($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Aggiorna Profilo -->
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlEscape($user['username']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlEscape($user['email']) ?>" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Aggiorna Profilo</button>
                    </form>

                    <hr>

                    <!-- Cambia Password -->
                    <h5>Cambia Password</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Password Attuale</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nuova Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">Minimo 6 caratteri</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Conferma Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning">Cambia Password</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Statistiche Account</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="text-primary"><?= $total_projects ?></h3>
                            <small class="text-muted">Progetti Creati</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success"><?= $total_checks ?></h3>
                            <small class="text-muted">Controlli Completati</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="text-muted small">
                        <strong>Account creato:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                    </div>
                    
                    <?php if ($user['updated_at'] && $user['updated_at'] !== $user['created_at']): ?>
                        <div class="text-muted small mt-2">
                            <strong>Ultimo aggiornamento:</strong><br>
                            <?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>