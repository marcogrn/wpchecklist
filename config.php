<?php
session_start();

// Configurazione database
define('DB_HOST', 'localhost');
define('DB_NAME', 'wordpress_checklist_3');
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
    
    // Verifica che l'email esista
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
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
?>