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
    $email = trim($_POST['email']);
    
    if ($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Inserire un indirizzo email valido';
        } else {
            // Verifica che l'email esista nel database e sia approvata
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'approved'");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                // Invia email di reset
                if (sendPasswordResetEmail($email)) {
                    $success = 'Email di reset inviata! Controlla la tua casella di posta.';
                } else {
                    $error = 'Errore durante l\'invio dell\'email. Riprova più tardi.';
                }
            } else {
                // Per sicurezza, non rivelare se l'email esiste o meno
                // Ma diamo un messaggio diverso se l'account non è approvato
                $stmt = $pdo->prepare("SELECT status FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user_status = $stmt->fetch();
                
                if ($user_status && $user_status['status'] !== 'approved') {
                    $error = 'Account non approvato. Contatta l\'amministratore per il reset della password.';
                } else {
                    $success = 'Se l\'email è registrata nel sistema e l\'account è approvato, riceverai le istruzioni per il reset.';
                }
            }
        }
    } else {
        $error = 'Inserire l\'indirizzo email';
    }
}

$page_title = 'Recupero Password - WordPress Checklist';
require_once 'includes/header.php';
?>

<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header text-center bg-dark text-white">
                <h4 class="mb-0">Recupero Password</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlEscape($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlEscape($success) ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <small>
                            <strong>Nota:</strong> Il reset password è disponibile solo per account approvati. 
                            Se il tuo account è in attesa di approvazione, contatta un amministratore.
                        </small>
                    </div>
                    
                    <p class="text-muted">Inserisci il tuo indirizzo email per ricevere le istruzioni per reimpostare la password.</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Indirizzo Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlEscape($_POST['email'] ?? '') ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-dark w-100">Invia Email di Reset</button>
                    </form>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-outline-secondary btn-sm">Torna al Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>