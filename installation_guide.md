# WordPress Checklist - Versione Aggiornata

## Modifiche Implementate

### 1. Gestione Utenti Avanzata
- **Registrazione con email** obbligatoria
- **Login con username o email**
- **Recupero password** via email con token sicuri
- **Pagina profilo** per aggiornare dati e cambiare password
- **Statistiche utente** (progetti creati, controlli completati)

### 2. Struttura Modulare
- **Header comune** (`includes/header.php`)
- **Navbar responsive** (`includes/navbar.php`) con menu dropdown utente
- **Footer comune** (`includes/footer.php`)
- **Design pulito** con solo Bootstrap 5.3.0, senza CSS personalizzati

### 3. Miglioramenti UI/UX
- **Design responsive** ottimizzato per mobile
- **Cards moderne** con layout a griglia
- **Progress bar animate** per il completamento progetti
- **Alert dismissibili** per i messaggi
- **Navbar con dropdown** per le opzioni utente

## Nuovi File Creati

### File di Sistema
- `includes/header.php` - Header HTML comune
- `includes/navbar.php` - Navigazione principale
- `includes/footer.php` - Footer con script Bootstrap
- `profile.php` - Gestione profilo utente
- `forgot_password.php` - Richiesta reset password
- `reset_password.php` - Reset password con token

### Database Aggiornato
- **Tabella `users`** con campo `email` obbligatorio e `updated_at`
- **Tabella `password_resets`** per gestire token di reset password
- **Indici ottimizzati** per performance migliori

## Installazione Step-by-Step

### 1. Preparazione Database
```sql
-- Esegui il nuovo schema database
mysql -u root -p < database_schema.sql
```

### 2. Configurazione Email
Modifica `config.php` con i tuoi parametri SMTP:

```php
// Configurazione email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@yoursite.com');
define('FROM_NAME', 'WordPress Checklist');

// URL base dell'applicazione
define('BASE_URL', 'https://yoursite.com/checklist');
```

### 3. Struttura Directory
```
wordpress-checklist/
├── includes/
│   ├── header.php
│   ├── navbar.php
│   └── footer.php
├── config.php
├── index.php
├── login.php
├── register.php
├── profile.php
├── forgot_password.php
├── reset_password.php
├── new_project.php
├── project.php
├── delete_project.php
├── manage_checklist.php
├── import_export.php
├── logout.php
└── default_checklist_template.json
```

### 4. Permessi File
```bash
chmod 755 /path/to/wordpress-checklist
chmod 644 /path/to/wordpress-checklist/*.php
chmod 755 /path/to/wordpress-checklist/includes/
```

## Configurazione Email

### Opzione 1: Gmail SMTP
1. Abilita autenticazione a due fattori
2. Genera password specifica per l'app
3. Usa questi parametri:
   - Host: `smtp.gmail.com`
   - Porta: `587`
   - Username: tua email Gmail
   - Password: password app generata

### Opzione 2: Server SMTP Personalizzato
Modifica in `config.php`:
```php
define('SMTP_HOST', 'mail.tuodominio.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@tuodominio.com');
define('SMTP_PASSWORD', 'tua-password');
```

### Opzione 3: Email Semplificata (Solo Testing)
Per testing locale, la funzione `mail()` PHP funziona senza SMTP.

## Funzionalità Email Implementate

### Reset Password
1. **Richiesta reset** - `forgot_password.php`
   - Verifica email nel database
   - Genera token sicuro (32 byte random)
   - Invia email con link di reset
   
2. **Conferma reset** - `reset_password.php`
   - Valida token e scadenza (1 ora)
   - Permette inserimento nuova password
   - Invalida token dopo uso

3. **Sicurezza token**
   - Token casuali con `random_bytes()`
   - Scadenza automatica dopo 1 ora
   - Invalidazione automatica dopo uso
   - Un solo token attivo per email

## Migrazioni Database

### Da Versione Precedente
Se hai già un database esistente, esegui questa migrazione:

```sql
-- Aggiungi campo email alla tabella users
ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE AFTER username;
ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Crea tabella password_resets
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

-- Aggiorna utente demo con email
UPDATE users SET email = 'demo@example.com' WHERE username = 'demo';
```

## Test dell'Installazione

### 1. Test Registrazione
- Vai su `register.php`
- Crea account con email valida
- Verifica che ricevi email di benvenuto (se configurata)

### 2. Test Reset Password
- Vai su `forgot_password.php`
- Inserisci email registrata
- Controlla email per link di reset
- Testa reset con nuovo password

### 3. Test Funzionalità
- Login con username o email
- Crea nuovo progetto
- Completa alcuni controlli checklist
- Testa import/export
- Verifica profilo utente

## Caratteristiche Bootstrap Utilizzate

### Componenti Principali
- **Grid System** - Layout responsive
- **Cards** - Contenitori moderni per contenuto
- **Navbar** - Navigazione con dropdown
- **Modals** - Dialog per add/edit controlli
- **Progress Bars** - Visualizzazione avanzamento
- **Alerts** - Messaggi di successo/errore
- **Forms** - Input validation e styling

### Classi Bootstrap Principali
- `container`, `row`, `col-*` - Layout
- `card`, `card-header`, `card-body` - Contenitori
- `btn`, `btn-primary`, `btn-outline-*` - Pulsanti
- `alert`, `alert-success`, `alert-danger` - Messaggi
- `form-control`, `form-label`, `form-text` - Form
- `navbar`, `nav-link`, `dropdown` - Navigazione
- `badge`, `progress`, `progress-bar` - Indicatori

## Sicurezza Implementata

### Autenticazione
- Password hash con `password_hash()` (bcrypt)
- Verifica con `password_verify()`
- Sessioni PHP per mantenere login
- Token CSRF per form critici

### Database
- **Prepared statements** per tutte le query
- **Escape HTML** su tutti gli output
- **Validazione input** server-side
- **Foreign key constraints** per integrità

### Email Security
- **Token casuali** per reset password
- **Scadenza automatica** token (1 ora)
- **Invalidazione post-uso** dei token
- **Rate limiting** implicito (un token per email)

## Personalizzazioni Future

### Aggiungere Ruoli Utente
```sql
-- Aggiungi campo ruolo
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';

-- Crea admin
UPDATE users SET role = 'admin' WHERE username = 'admin';
```

### Aggiungere Notifiche Email
```php
// In config.php - funzione per notifiche
function sendProjectCompletionEmail($project_id) {
    // Logica per notificare completamento progetto
}
```

### Temi Personalizzati
```php
// Aggiungi in config.php
define('THEME_COLOR', '#6c757d'); // Grigio default
// Oppure: '#007bff' (blu), '#28a745' (verde), etc.
```

## Risoluzione Problemi

### Email Non Funzionano
1. **Verifica configurazione SMTP** in `config.php`
2. **Controlla log errori** del server web
3. **Testa con Gmail SMTP** per debug
4. **Verifica firewall** porta 587

### Errori Database
1. **Controlla credenziali** in `config.php`
2. **Verifica esistenza database** `wordpress_checklist_2`
3. **Esegui migration** se upgrade da versione precedente
4. **Controlla permessi utente** MySQL

### Problemi Sessions
1. **Verifica permessi** directory `/tmp` o sessioni PHP
2. **Controlla configurazione** `session.save_path`
3. **Verifica headers** non inviati prima di `session_start()`

### Layout Rotto
1. **Verifica CDN Bootstrap** attivo
2. **Controlla connessione internet**
3. **Testa browser diversi**
4. **Verifica include** file header/footer

## Backup e Manutenzione

### Backup Automatico
```bash
# Script cron per backup giornaliero
#!/bin/bash
mysqldump -u user -p wordpress_checklist_2 > backup_$(date +%Y%m%d).sql
```

### Pulizia Token Scaduti
```sql
-- Esegui settimanalmente
DELETE FROM password_resets WHERE expires_at < NOW() - INTERVAL 7 DAY;
```

### Monitoraggio Uso
```sql
-- Query utili per statistiche
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_projects FROM projects;
SELECT COUNT(*) as total_checks FROM project_checks;
```

## Prossimi Sviluppi Consigliati

### Funzionalità Avanzate
1. **Team collaboration** - Condivisione progetti tra utenti
2. **Notifiche email** - Alert per scadenze e completamenti
3. **API REST** - Integrazione con tools esterni
4. **Dashboard analytics** - Grafici e statistiche avanzate
5. **Mobile app** - Versione nativa per iOS/Android

### Miglioramenti Tecnici
1. **Caching** - Redis/Memcached per performance
2. **Rate limiting** - Protezione da abuse
3. **Logging** - Tracciamento azioni utente
4. **Tests** - Unit testing con PHPUnit
5. **Docker** - Containerizzazione per deploy

Questa versione aggiornata offre una base solida e professionale per la gestione delle checklist WordPress, con un'architettura modulare e sicura pronta per ulteriori sviluppi.