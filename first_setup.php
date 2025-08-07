<?php
require_once 'config.php';

// Verifica se è necessario il setup iniziale
if (!checkFirstTimeSetup()) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($username && $email && $password && $confirm_password) {
        // Validazione
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Inserire un indirizzo email valido';
        } elseif ($password !== $confirm_password) {
            $error = 'Le password non corrispondono';
        } elseif (strlen($password) < 8) {
            $error = 'La password deve essere di almeno 8 caratteri';
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, approved_at) VALUES (?, ?, ?, 'admin', 'approved', NOW())");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $success = 'Amministratore creato con successo! Ora puoi effettuare il login.';
                header('refresh:3;url=login.php');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Username o email già esistenti';
                } else {
                    $error = 'Errore durante la creazione dell\'amministratore';
                }
            }
        }
    } else {
        $error = 'Compilare tutti i campi';
    }
}

$page_title = 'Setup Iniziale - WordPress Checklist';
require_once 'includes/header.php';
?>

<div class="container-fluid d-flex align-items-center justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-lg">
            <div class="card-header text-center bg-primary text-white">
                <img src="/img/logo.svg" alt="logo" class="img-fluid mb-2 mt-2" style="max-height:40px;">
                <h4 class="mb-0 mt-2">Setup Iniziale</h4>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info">
                    <h5 class="alert-heading">Benvenuto!</h5>
                    <p class="mb-0">Questa è la prima volta che accedi al sistema. Crea l'account amministratore principale per iniziare.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlEscape($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlEscape($success) ?>
                        <br><small>Verrai reindirizzato al login...</small>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username Amministratore</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlEscape($_POST['username'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Amministratore</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlEscape($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Minimo 8 caratteri</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Conferma Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <small>
                                <strong>Importante:</strong> Ricorda le credenziali inserite. Sarai l'unico amministratore 
                                iniziale del sistema e potrai gestire tutte le richieste di registrazione degli altri utenti.
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 btn-lg">
                            Crea Amministratore e Avvia Sistema
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-white">
                WordPress Checklist - Sistema di Gestione Progetti
            </small>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>