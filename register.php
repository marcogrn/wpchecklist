<?php
require_once 'config.php';

// Verifica se il sistema ha bisogno di setup iniziale
if (checkFirstTimeSetup()) {
    header('Location: first_setup.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $reason = trim($_POST['reason']);
    
    if ($username && $email && $password && $confirm_password && $reason) {
        // Validazione email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Inserire un indirizzo email valido';
        } elseif ($password !== $confirm_password) {
            $error = 'Le password non corrispondono';
        } elseif (strlen($password) < 6) {
            $error = 'La password deve essere di almeno 6 caratteri';
        } else {
            // Verifica che username ed email non esistano già
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username o email già esistenti';
            } else {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$username, $email, $hashed_password]);
                    
                    // Invia notifica agli amministratori
                    sendRegistrationRequestEmail($username, $email);
                    
                    $success = 'Richiesta di registrazione inviata! Un amministratore esaminerà la tua richiesta e riceverai una notifica via email.';
                    header('refresh:5;url=login.php');
                } catch (PDOException $e) {
                    $error = 'Errore durante l\'invio della richiesta';
                }
            }
        }
    } else {
        $error = 'Compilare tutti i campi';
    }
}

$page_title = 'Richiesta Registrazione - WordPress Checklist';
require_once 'includes/header.php';
?>

<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header text-center bg-dark text-white">
                <h4 class="mb-0">Richiesta di Registrazione</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">Registrazione con Approvazione</h6>
                    <p class="mb-0">Le registrazioni richiedono l'approvazione di un amministratore. Compila il form sottostante e riceverai una notifica via email quando la tua richiesta sarà esaminata.</p>
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
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">Minimo 6 caratteri</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Conferma Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Motivo della Richiesta</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" 
                                      placeholder="Spiega brevemente perché hai bisogno di accedere al sistema..."
                                      required><?= htmlEscape($_POST['reason'] ?? '') ?></textarea>
                            <div class="form-text">Questo aiuta gli amministratori a valutare la tua richiesta</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Invia Richiesta di Registrazione</button>
                    </form>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-outline-secondary btn-sm">Hai già un account? Accedi</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?><label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?= htmlEscape($_POST['username'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlEscape($_POST['email'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    