<?php
/**
 * Script di migrazione per aggiornare il database dalla versione precedente
 * alla nuova versione con sistema di approvazione utenti
 */

require_once 'config.php';

echo "=== WordPress Checklist - Script di Migrazione ===\n";
echo "Questo script aggiornerà il database alla nuova versione con sistema di approvazione.\n\n";

// Verifica se esistono già le nuove colonne/tabelle
$existing_columns = [];
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $existing_columns = $columns;
} catch (PDOException $e) {
    echo "Errore nel verificare la struttura della tabella users: " . $e->getMessage() . "\n";
    exit(1);
}

$needs_migration = !in_array('role', $existing_columns) || !in_array('status', $existing_columns);

if (!$needs_migration) {
    echo "Il database sembra già aggiornato. Nessuna migrazione necessaria.\n";
    exit(0);
}

echo "Iniziando migrazione del database...\n\n";

try {
    $pdo->beginTransaction();
    
    // 1. Aggiorna tabella users
    echo "1. Aggiornamento tabella users...\n";
    
    if (!in_array('role', $existing_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER password");
        echo "   - Aggiunta colonna 'role'\n";
    }
    
    if (!in_array('status', $existing_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER role");
        echo "   - Aggiunta colonna 'status'\n";
    }
    
    if (!in_array('approved_by', $existing_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN approved_by INT NULL AFTER status");
        echo "   - Aggiunta colonna 'approved_by'\n";
    }
    
    if (!in_array('approved_at', $existing_columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by");
        echo "   - Aggiunta colonna 'approved_at'\n";
    }
    
    // Aggiungi foreign key per approved_by
    try {
        $pdo->exec("ALTER TABLE users ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "   - Aggiunta foreign key per approved_by\n";
    } catch (PDOException $e) {
        // La foreign key potrebbe già esistere, ignora l'errore
        echo "   - Foreign key approved_by già esistente o non aggiungibile\n";
    }
    
    // 2. Crea tabella admin_messages se non esiste
    echo "2. Creazione tabella admin_messages...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    echo "   - Tabella admin_messages creata\n";
    
    // 3. Crea tabella user_dismissed_messages se non esiste
    echo "3. Creazione tabella user_dismissed_messages...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_dismissed_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message_id INT,
        dismissed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (message_id) REFERENCES admin_messages(id) ON DELETE CASCADE,
        UNIQUE KEY unique_dismissal (user_id, message_id)
    )");
    echo "   - Tabella user_dismissed_messages creata\n";
    
    // 4. Approva automaticamente tutti gli utenti esistenti
    echo "4. Approvazione utenti esistenti...\n";
    $stmt = $pdo->prepare("UPDATE users SET status = 'approved', approved_at = NOW() WHERE status IS NULL OR status = ''");
    $stmt->execute();
    $approved_count = $stmt->rowCount();
    echo "   - Approvati automaticamente $approved_count utenti esistenti\n";
    
    // 5. Verifica se esiste almeno un amministratore
    echo "5. Verifica amministratori...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $admin_count = $stmt->fetchColumn();
    
    if ($admin_count == 0) {
        echo "   - Nessun amministratore trovato.\n";
        echo "   - IMPORTANTE: Sarà necessario eseguire first_setup.php per creare il primo amministratore.\n";
        
        // Imposta tutti gli utenti esistenti come pending se non ci sono admin
        $pdo->exec("UPDATE users SET status = 'pending' WHERE role != 'admin'");
        echo "   - Utenti esistenti impostati come 'pending' in attesa del primo amministratore.\n";
    } else {
        echo "   - Trovati $admin_count amministratori esistenti\n";
    }
    
    // 6. Crea messaggio di benvenuto dell'amministratore
    echo "6. Creazione messaggio di benvenuto...\n";
    $welcome_message = "Benvenuto nel nuovo sistema di gestione WordPress Checklist! Sono stati introdotti i controlli di approvazione utenti e i messaggi globali. Controlla il pannello amministrativo per tutte le nuove funzionalità.";
    
    // Trova il primo admin per assegnare il messaggio
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $first_admin = $stmt->fetchColumn();
    
    if ($first_admin) {
        $stmt = $pdo->prepare("INSERT INTO admin_messages (message, type, created_by, is_active) VALUES (?, 'info', ?, 1)");
        $stmt->execute([$welcome_message, $first_admin]);
        echo "   - Messaggio di benvenuto creato\n";
    }
    
    $pdo->commit();
    
    echo "\n=== MIGRAZIONE COMPLETATA CON SUCCESSO ===\n";
    echo "Riepilogo modifiche:\n";
    echo "- Aggiunta gestione ruoli utenti (user/admin)\n";
    echo "- Aggiunta approvazione utenti (pending/approved/rejected)\n";
    echo "- Creata tabella messaggi amministratore globali\n";
    echo "- Creata tabella dismissal messaggi utenti\n";
    echo "- Utenti esistenti approvati automaticamente\n";
    
    if ($admin_count == 0) {
        echo "\n⚠️  ATTENZIONE:\n";
        echo "Non sono stati trovati amministratori esistenti.\n";
        echo "Esegui first_setup.php per creare il primo amministratore del sistema.\n";
    }
    
    echo "\nNuove funzionalità disponibili:\n";
    echo "- Richieste di registrazione con approvazione\n";
    echo "- Pannello amministrativo per gestione utenti\n";
    echo "- Messaggi globali per tutti gli utenti\n";
    echo "- Statistiche sistema avanzate\n";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "ERRORE durante la migrazione: " . $e->getMessage() . "\n";
    echo "La migrazione è stata annullata. Il database non è stato modificato.\n";
    exit(1);
}

echo "\nMigrazione completata! Puoi ora utilizzare la nuova versione dell'applicazione.\n";
?>