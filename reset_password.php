<?php
require_once 'config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Verifica token
if (!$token) {
    header('Location: login.php');
    exit();
}

$token_data = validatePasswordResetToken($token);
if (!$token_data) {
    $error = 'Token non valido o scaduto. Richiedi un nuovo reset password.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_data) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = 'Le password non corrispondono';
        } elseif (strlen($password) < 6) {
            $error = 'La password deve essere di almeno 6 caratteri';
        } else {
            try {
                // Aggiorna password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $token_data['email']]);
                
                // Marca token come usato
                usePasswordResetToken($token);
                
                header('Location: login.php?reset=success');
                exit();
            } catch (PDOException $e) {
                $error = 'Errore durante l\'aggiornamento della password';
            }
        }
    } else {
        $error = 'Compilare tutti i campi';
    }
}

$page_title = 'Reset Password - WordPress Checklist';
require_once 'includes/header.php';
?>

<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header text-center bg-dark text-white">
                <h4 class="mb-0">Reset Password</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlEscape($error) ?></div>
                    <?php if (!$token_data): ?>
                        <div class="text-center">
                            <a href="forgot_password.php" class="btn btn-outline-secondary">Richiedi Nuovo Reset</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($token_data && !$error): ?>
                    <div class="alert alert-info">
                        Reset password per: <strong><?= htmlEscape($token_data['username']) ?></strong>
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nuova Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Minimo 6 caratteri</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Conferma Nuova Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-dark w-100">Aggiorna Password</button>
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