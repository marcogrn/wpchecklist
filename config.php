<?php
session_start();

// Configurazione database
define('DB_HOST', 'localhost');
define('DB_NAME', 'wordpress_checklist_4');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Configurazione email
define('SMTP_HOST', 'smtp.ionos.it');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'info@marcoguerini.com');
define('SMTP_PASSWORD', 'rcM6@3HMg');
define('FROM_EMAIL', 'info@marcoguerini.com');
define('FROM_NAME', 'WordPress Checklist');

// URL base dell'applicazione
define('BASE_URL', 'https://checklist.host:8890');

// Connessione database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Errore connessione database: " . $e->getMessage());
}

// Funzioni utili
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: index.php?error=access_denied');
        exit();
    }
}

function requireApprovedUser() {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['status'] !== 'approved') {
        header('Location: pending_approval.php');
        exit();
    }
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function htmlEscape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function sendEmail($to, $subject, $message, $isHTML = true) {
    // Headers per email HTML
    $headers = "MIME-Version: 1.0\r\n";
    if ($isHTML) {
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    }
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

function createPasswordResetToken($email) {
    global $pdo;
    
    // Verifica che l'email esista e sia approvata
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'approved'");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        return false;
    }
    
    // Invalida token esistenti per questa email
    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE email = ? AND used_at IS NULL")
        ->execute([$email]);
    
    // Crea nuovo token
    $token = generateToken();
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expires_at]);
    
    return $token;
}

function validatePasswordResetToken($token) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT pr.*, u.username 
        FROM password_resets pr 
        JOIN users u ON pr.email = u.email 
        WHERE pr.token = ? 
        AND pr.expires_at > NOW() 
        AND pr.used_at IS NULL
        AND u.status = 'approved'
    ");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function usePasswordResetToken($token) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
    return $stmt->execute([$token]);
}

function sendPasswordResetEmail($email) {
    $token = createPasswordResetToken($email);
    if (!$token) {
        return false;
    }
    
    $reset_link = BASE_URL . "/reset_password.php?token=" . $token;
    
    $subject = "Reset Password - WordPress Checklist";
    $message = "
    <html>
    <body>
        <h2>Reset Password</h2>
        <p>Hai richiesto il reset della password per il tuo account WordPress Checklist.</p>
        <p><a href='$reset_link' style='background-color: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>Oppure copia e incolla questo link nel tuo browser:</p>
        <p>$reset_link</p>
        <p>Questo link scadrà tra 1 ora.</p>
        <p>Se non hai richiesto questo reset, ignora questa email.</p>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message, true);
}

function sendRegistrationRequestEmail($username, $email) {
    // Ottieni tutti gli admin per notificarli
    global $pdo;
    $stmt = $pdo->prepare("SELECT email FROM users WHERE role = 'admin' AND status = 'approved'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($admins)) return true; // Se non ci sono admin, non inviare email
    
    $subject = "Nuova Richiesta di Registrazione - WordPress Checklist";
    $admin_link = BASE_URL . "/admin/users.php";
    
    $message = "
    <html>
    <body>
        <h2>Nuova Richiesta di Registrazione</h2>
        <p>Un nuovo utente ha richiesto l'accesso al sistema WordPress Checklist:</p>
        <ul>
            <li><strong>Username:</strong> $username</li>
            <li><strong>Email:</strong> $email</li>
        </ul>
        <p><a href='$admin_link' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Gestisci Richieste</a></p>
        <p>Accedi al pannello amministrativo per approvare o rifiutare questa richiesta.</p>
    </body>
    </html>
    ";
    
    $success = true;
    foreach ($admins as $admin_email) {
        if (!sendEmail($admin_email, $subject, $message, true)) {
            $success = false;
        }
    }
    
    return $success;
}

function sendApprovalNotificationEmail($email, $username, $approved) {
    $subject = $approved ? "Registrazione Approvata - WordPress Checklist" : "Registrazione Rifiutata - WordPress Checklist";
    
    if ($approved) {
        $login_link = BASE_URL . "/login.php";
        $message = "
        <html>
        <body>
            <h2>Registrazione Approvata</h2>
            <p>Ciao $username,</p>
            <p>La tua registrazione per WordPress Checklist è stata approvata!</p>
            <p>Ora puoi accedere al sistema utilizzando le tue credenziali:</p>
            <p><a href='$login_link' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Accedi Ora</a></p>
            <p>Benvenuto nel team!</p>
        </body>
        </html>
        ";
    } else {
        $message = "
        <html>
        <body>
            <h2>Registrazione Non Approvata</h2>
            <p>Ciao $username,</p>
            <p>Ci dispiace informarti che la tua richiesta di registrazione per WordPress Checklist non è stata approvata.</p>
            <p>Per maggiori informazioni, contatta l'amministratore del sistema.</p>
        </body>
        </html>
        ";
    }
    
    return sendEmail($email, $subject, $message, true);
}

function checkFirstTimeSetup() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $admin_count = $stmt->fetchColumn();
    return $admin_count == 0;
}

function getActiveAdminMessages($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT am.* 
        FROM admin_messages am
        WHERE am.is_active = 1
        AND am.id NOT IN (
            SELECT udm.message_id 
            FROM user_dismissed_messages udm 
            WHERE udm.user_id = ?
        )
        ORDER BY am.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function dismissMessage($user_id, $message_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_dismissed_messages (user_id, message_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $message_id]);
    } catch (PDOException $e) {
        return false;
    }
}
?>