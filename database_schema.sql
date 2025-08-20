-- Database: variare dbname con il nome del vostro db

CREATE DATABASE IF NOT EXISTS dbname;
USE dbname;

-- Tabella utenti (aggiornata con ruoli e approvazione)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabella per token reset password
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
);

-- Tabella progetti
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    link VARCHAR(255) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Tabella checklist items
CREATE TABLE checklist_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    sort_order INT DEFAULT 0
);

-- Tabella per tracciare i controlli completati
CREATE TABLE project_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    checklist_item_id INT,
    checked_by INT,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (checklist_item_id) REFERENCES checklist_items(id),
    FOREIGN KEY (checked_by) REFERENCES users(id),
    UNIQUE KEY unique_check (project_id, checklist_item_id)
);

-- Tabella per messaggi globali dell'amministratore
CREATE TABLE admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Tabella per tracciare quali utenti hanno chiuso quali messaggi
CREATE TABLE user_dismissed_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message_id INT,
    dismissed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (message_id) REFERENCES admin_messages(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dismissal (user_id, message_id)
);

-- Inserimento checklist predefinita per WordPress (30 controlli completi)
INSERT INTO checklist_items (title, description, category, sort_order) VALUES
('Verifica velocità di caricamento', 'Testare con PageSpeed Insights, GTmetrix o altri strumenti. Obiettivo: < 3 secondi', 'Performance', 1),
('Test prestazioni database', 'Ottimizzare query lente, pulire database da revisioni e spam', 'Performance', 2),
('Ottimizzazione immagini', 'Verificare compressione immagini, lazy loading e formati WebP', 'Performance', 3),
('Test caching', 'Verificare funzionamento cache del browser e server', 'Performance', 4),
('Controllo responsive design', 'Testare su dispositivi mobili, tablet e desktop (320px, 768px, 1024px, 1200px)', 'Design & UX', 5),
('Test stampa pagine', 'Verificare layout di stampa delle pagine principali', 'Design & UX', 6),
('Verifica accessibilità', 'Controllare contrasti colori, navigazione tastiera, alt text, ARIA labels', 'Design & UX', 7),
('Test cross-browser', 'Testare su Chrome, Firefox, Safari, Edge nelle versioni più recenti', 'Compatibilità', 8),
('Test su dispositivi reali', 'Verificare funzionamento su iPhone, Android, iPad', 'Compatibilità', 9),
('Controllo form di contatto', 'Testare invio, ricezione email, validazione campi, messaggi di conferma', 'Funzionalità', 10),
('Test 404 e redirect', 'Verificare pagine 404 personalizzate, redirect da HTTP a HTTPS, redirect www', 'Funzionalità', 11),
('Test ricerca interna', 'Verificare funzionamento ricerca del sito se presente', 'Funzionalità', 12),
('Controllo menu e navigazione', 'Testare tutti i link di navigazione, menu mobile, breadcrumb', 'Funzionalità', 13),
('Verifica SEO base', 'Controllare meta title, description, H1-H6, alt text immagini, sitemap.xml', 'SEO', 14),
('Controllo contenuti duplicati', 'Verificare assenza di contenuti duplicati e canonical URL', 'SEO', 15),
('Verifica robots.txt', 'Controllare configurazione robots.txt e indicizzazione', 'SEO', 16),
('Test structured data', 'Verificare markup strutturato (Schema.org) con Google Rich Results Test', 'SEO', 17),
('Verifica SSL/HTTPS', 'Controllare certificato SSL valido, redirect HTTP->HTTPS, mixed content', 'Sicurezza', 18),
('Backup database e file', 'Creare backup completo prima del go-live e configurare backup automatici', 'Sicurezza', 19),
('Controllo credenziali', 'Cambiare password admin predefinite, rimuovere utenti demo', 'Sicurezza', 20),
('Controllo plugin e temi', 'Aggiornare tutti i plugin e temi, rimuovere quelli non utilizzati', 'Manutenzione', 21),
('Pulizia database', 'Rimuovere revisioni, spam, transient scaduti', 'Manutenzione', 22),
('Controllo log errori', 'Verificare log PHP e server per eventuali errori', 'Manutenzione', 23),
('Verifica Google Analytics', 'Controllare tracking code, obiettivi, e-commerce tracking se presente', 'Analytics', 24),
('Configurazione Google Search Console', 'Aggiungere proprietà, verificare sitemap, controllare copertura indicizzazione', 'Analytics', 25),
('Test email transazionali', 'Verificare invio email da WordPress (registrazioni, reset password, notifiche)', 'Email', 26),
('Configurazione SMTP', 'Configurare SMTP per invio email affidabile', 'Email', 27),
('Test privacy e GDPR', 'Verificare privacy policy, cookie banner, consenso trattamento dati', 'Compliance', 28),
('Controllo informazioni legali', 'Verificare presenza e correttezza di termini di servizio, informative', 'Compliance', 29),
('Test ambiente di produzione', 'Verificare configurazione server di produzione, PHP version, limiti', 'Deploy', 30);
