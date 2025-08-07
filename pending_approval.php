<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();

// Se l'utente è già approvato, reindirizza alla dashboard
if ($user['status'] === 'approved') {
    header('Location: index.php');
    exit();
}

$page_title = 'In Attesa di Approvazione - WordPress Checklist';
require_once 'includes/header.php';
?>

<div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="col-md-6">
        <div class="card shadow-lg text-center">
            <div class="card-body py-5">
                <?php if ($user['status'] === 'pending'): ?>
                    <div class="mb-4">
                        <div class="spinner-border text-primary mb-3" style="width: 4rem; height: 4rem;"></div>
                        <h3 class="text-primary">In Attesa di Approvazione</h3>
                    </div>
                    
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Ciao <?= htmlEscape($user['username']) ?>!</h5>
                        <p class="mb-0">
                            La tua richiesta di registrazione è stata ricevuta ed è in attesa di approvazione 
                            da parte di un amministratore.
                        </p>
                    </div>
                    
                    <div class="row text-center mt-4">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <i class="bi bi-person-check" style="font-size: 2rem; color: #28a745;"></i>
                                <h6 class="mt-2">Richiesta Inviata</h6>
                                <small class="text-muted">La tua richiesta è stata registrata</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <i class="bi bi-clock" style="font-size: 2rem; color: #ffc107;"></i>
                                <h6 class="mt-2">In Revisione</h6>
                                <small class="text-muted">Un amministratore esaminerà la tua richiesta</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <i class="bi bi-envelope" style="font-size: 2rem; color: #17a2b8;"></i>
                                <h6 class="mt-2">Notifica Email</h6>
                                <small class="text-muted">Riceverai una email con l'esito</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <p class="text-muted">
                            <strong>Account creato il:</strong> 
                            <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                        </p>
                        <p class="text-muted">
                            <strong>Email di contatto:</strong> 
                            <?= htmlEscape($user['email']) ?>
                        </p>
                    </div>
                    
                <?php elseif ($user['status'] === 'rejected'): ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Registrazione Non Approvata</h5>
                        <p class="mb-0">
                            Ci dispiace, ma la tua richiesta di registrazione non è stata approvata.
                            <br>Per maggiori informazioni, contatta l'amministratore del sistema.
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="logout.php" class="btn btn-outline-secondary">Logout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Icons CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css">

<?php require_once 'includes/footer.php'; ?>