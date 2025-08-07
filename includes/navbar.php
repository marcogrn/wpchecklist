<?php
// File: includes/navbar.php
// Navbar comune per tutte le pagine dell'applicazione

$user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a href="index.php" class="navbar-brand">
            <img src="/img/logo.svg" alt="logo" class="img-fluid" style="max-height:40px;">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">           
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>" href="/index.php">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'manage_checklist' ? 'active' : '' ?>" href="/manage_checklist.php">
                        Gestione Checklist
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'import_export' ? 'active' : '' ?>" href="/import_export.php">
                        Import/Export
                    </a>
                </li>
                
                <!-- Menu Amministratore -->
                <?php if ($user['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($current_page, 'admin') !== false ? 'active' : '' ?>" 
                           href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            Amministrazione
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/users.php">Gestione Utenti</a></li>
                            <li><a class="dropdown-item" href="/admin/messages.php">Messaggi Globali</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <?= htmlEscape($user['username']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header"><?= htmlEscape($user['email']) ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/profile.php">Il mio Profilo</a></li>
                        <li><a class="dropdown-item" href="/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>