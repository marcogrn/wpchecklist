<?php
require_once 'config.php';

// Verifica se il sistema ha bisogno di setup iniziale
if (checkFirstTimeSetup()) {
    header('Location: first_setup.php');
    exit();
}

$error = '';
$success = '';

// Gestione messaggio di successo reset password
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Password aggiornata con successo! Puoi ora effettuare il login.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];
    
    if ($username_or_email && $password) {
        // Permetti login solo per utenti approvati
        $stmt = $pdo->prepare("SELECT id, username, email, password, role, status FROM users WHERE (username = ? OR email = ?) AND status = 'approved'");
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit();
        } else {
            // Verifica se l'utente esiste ma non è approvato
            $stmt = $pdo->prepare("SELECT status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_or_email, $username_or_email]);
            $user_status = $stmt->fetch();
            
            if ($user_status) {
                switch ($user_status['status']) {
                    case 'pending':
                        $error = 'Account in attesa di approvazione. Riceverai una email quando l\'amministratore approverà il tuo account.';
                        break;
                    case 'rejected':
                        $error = 'Account non approvato. Contatta l\'amministratore per maggiori informazioni.';
                        break;
                    default:
                        $error = 'Credenziali non valide';
                }
            } else {
                $error = 'Credenziali non valide';
            }
        }
    } else {
        $error = 'Inserire username/email e password';
    }
}

$page_title = 'Login - WordPress Checklist';
require_once 'includes/header.php';
?>

<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header text-center bg-dark text-white">
                <img src="/img/logo.svg" alt="logo" class="img-fluid mb-2 mt-2" style="max-height:40px;">
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlEscape($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlEscape($success) ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username_or_email" class="form-label">Username o Email</label>
                        <input type="text" class="form-control" id="username_or_email" name="username_or_email" 
                               value="<?= htmlEscape($_POST['username_or_email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Accedi</button>
                    
                    <div class="text-center mt-3">
                        <a href="register.php" class="btn btn-outline-secondary btn-sm me-2">Richiedi Registrazione</a>
                        <a href="forgot_password.php" class="btn btn-outline-secondary btn-sm">Password Dimenticata?</a>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <div class="border-top pt-3">
                        <small class="text-muted">
                            By Toicom
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>