<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $link = trim($_POST['link']);
    
    if ($name && $link) {
        // Verifica che il link sia valido
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $error = 'Inserire un link valido (deve iniziare con http:// o https://)';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO projects (name, link, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$name, $link, $user['id']]);
                
                $project_id = $pdo->lastInsertId();
                header("Location: project.php?id=$project_id");
                exit();
            } catch (PDOException $e) {
                $error = 'Errore durante la creazione del progetto';
            }
        }
    } else {
        $error = 'Inserire nome e link del progetto';
    }
}

$page_title = 'Nuovo Progetto - WordPress Checklist';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Crea Nuovo Progetto</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlEscape($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?= htmlEscape($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome Progetto</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlEscape($_POST['name'] ?? '') ?>" 
                                   placeholder="Es: Sito Web Azienda XYZ" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="link" class="form-label">Link del Sito</label>
                            <input type="url" class="form-control" id="link" name="link" 
                                   value="<?= htmlEscape($_POST['link'] ?? '') ?>" 
                                   placeholder="https://www.esempio.com" required>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Annulla</a>
                            <button type="submit" class="btn btn-primary">Crea Progetto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
                