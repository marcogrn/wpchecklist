# WordPress Checklist - Sistema con Approvazione Utenti

## Nuove Funzionalità Implementate

### 1. Sistema di Approvazione Utenti
- **Registrazione con richiesta**: Gli utenti non possono più registrarsi direttamente ma devono richiedere l'accesso
- **Approvazione amministratore**: Solo gli amministratori possono approvare/rifiutare le richieste
- **Notifiche email**: Invio automatico di email per richieste e approvazioni
- **Stati utente**: pending, approved, rejected

### 2. Setup Amministratore Iniziale
- **First Setup**: Al primo accesso viene richiesta la creazione dell'amministratore principale
- **Configurazione sicura**: Password minimo 8 caratteri per admin
- **Accesso prioritario**: Gli admin possono sempre accedere anche durante il setup

### 3. Pannello Amministrativo Completo
- **Gestione utenti**: Visualizzazione, approvazione, modifica ruoli, eliminazione
- **Assegnazione ruoli**: Solo due ruoli disponibili (user/admin)
- **Statistiche avanzate**: Dashboard con metriche del sistema
- **Messaggi globali**: Sistema di comunicazione con tutti gli utenti

### 4. Sistema Messaggi Globali
- **Messaggi admin**: Gli amministratori possono inviare messaggi a tutti gli utenti
- **Tipi di alert**: Info, Successo, Avviso, Pericolo
- **Dismissal utenti**: Gli utenti possono chiudere i messaggi
- **Reset visibilità**: Gli admin possono rendere nuovamente visibili i messaggi

## Installazione Step-by-Step

### 1. Nuova Installazione

```bash
# Clone del progetto
git clone <repository-url> wordpress-checklist
cd wordpress-checklist

# Creazione database
mysql -u root -p -e "CREATE DATABASE wordpress_checklist_3;"
mysql -u root -p wordpress_checklist_3 < database_schema.sql
```

### 2. Migrazione da Versione Precedente

```bash
# Backup database esistente
mysqldump -u root -p wordpress_checklist_2 > backup_$(date +%Y%m%d).sql

# Aggiorna configurazione database in config.php
# Cambia DB_NAME da 'wordpress_checklist_2' a 'wordpress_checklist_3'

# Esegui script di migrazione
php migrate.php
```

### 3. Configurazione File

#### config.php
```php
// Aggiorna nome database
define('DB_NAME', 'wordpress_checklist_3');

// Configurazione email (obbligatoria per notifiche)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@yoursite.com');
define('FROM_NAME', 'WordPress Checklist');

// URL base per link email
define('BASE_URL', 'https://yoursite.com/checklist');
```

### 4. Struttura Directory Aggiornata

```
wordpress-checklist/
├── admin/                  # Nuova directory pannello admin
│   ├── users.php          # Gestione utenti
│   ├── messages.php       # Messaggi globali
│   └── stats.php          # Statistiche sistema
├── includes/
│   ├── header.php
│   ├── navbar.php         # Aggiornato con menu admin
│   └── footer.php
├── config.php             # Aggiornato con nuove funzioni
├── first_setup.php        # Nuovo: setup amministratore iniziale
├── pending_approval.php   # Nuovo: pagina attesa approvazione
├── migrate.php            # Nuovo: script migrazione database
├── login.php              # Aggiornato con controlli approvazione
├── register.php           # Aggiornato: richiesta registrazione
├── forgot_password.php    # Aggiornato con controlli approvazione
├── index.php              # Aggiornato con messaggi admin
└── ... (altri file esistenti)
```

### 5. Primo Accesso al Sistema

#### Nuova Installazione
1. Accedi a `yoursite.com/checklist/`
2. Verrai reindirizzato a `first_setup.php`
3. Crea l'account amministratore principale
4. Effettua login con le credenziali create

#### Dopo Migrazione
1. Se non esistevano admin: esegui `first_setup.php`
2. Se esistevano admin: accedi normalmente
3. Gli utenti esistenti sono automaticamente approvati

## Workflow Approvazione Utenti

### 1. Richiesta Registrazione (Utente)
1. L'utente va su `register.php`
2. Compila il form con motivazione della richiesta
3. Il sistema invia email di notifica agli amministratori
4. L'utente riceve conferma di richiesta inviata

### 2. Gestione Richieste (Amministratore)
1. L'admin riceve email di notifica
2. Accede a `admin/users.php`
3. Visualizza richieste pending
4. Approva o rifiuta con un click
5. Il sistema invia email automatica all'utente

### 3. Accesso Sistema (Utente Approvato)
1. L'utente riceve email di approvazione
2. Può effettuare login normalmente
3. Ha accesso a tutte le funzionalità standard

## Funzionalità Messaggi Globali

### Creazione Messaggi (Admin)
1. Accedi a `admin/messages.php`
2. Scrivi messaggio e scegli tipo alert
3. Il messaggio appare immediatamente a tutti gli utenti
4. Monitoraggio statistiche visualizzazione

### Gestione Messaggi (Admin)
- **Attivazione/Disattivazione**: Toggle visibilità messaggi
- **Reset Dismissal**: Rendi nuovamente visibile a tutti
- **Statistiche**: Visualizza quanti utenti hanno visto/chiuso
- **Eliminazione**: Rimozione permanente messaggio

### Visualizzazione Messaggi (Utenti)
- Appaiono come alert colorati nella dashboard
- Possono essere chiusi con la X
- Non riappaiono una volta chiusi (salvo reset admin)
- Diversi tipi: Info (blu), Successo (verde), Avviso (giallo), Pericolo (rosso)

## Controlli di Sicurezza

### Autenticazione e Autorizzazione
- **requireLogin()**: Verifica login attivo
- **requireApprovedUser()**: Solo utenti approvati
- **requireAdmin()**: Solo amministratori
- **Protezione CSRF**: Token per azioni sensibili

### Validazione Input
- Escape HTML su tutti gli output
- Prepared statements per database
- Validazione email server-side
- Controllo lunghezza password (min 6 per user, 8 per admin)

### Gestione Errori
- Log errori database
- Messaggi utente generici per sicurezza
- Rollback transazioni in caso di errore
- Backup automatico consigliato

## Database Schema Aggiornato

### Nuove Colonne Tabella `users`
```sql
role ENUM('user', 'admin') DEFAULT 'user'
status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'  
approved_by INT NULL
approved_at TIMESTAMP NULL
```

### Nuove Tabelle
```sql
-- Messaggi amministratore
admin_messages (id, message, type, is_active, created_by, created_at, updated_at)

-- Tracking dismissal messaggi
user_dismissed_messages (id, user_id, message_id, dismissed_at)
```

## Configurazione Email

### Gmail SMTP (Consigliato)
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-gmail@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Non la password normale!
```

#### Setup Gmail App Password
1. Attiva autenticazione a 2 fattori
2. Vai in "App Password" nelle impostazioni Google
3. Genera password specifica per "WordPress Checklist"
4. Usa quella password in SMTP_PASSWORD

### Server Email Personalizzato
```php
define('SMTP_HOST', 'mail.tuodominio.com');
define('SMTP_PORT', 587); // o 465 per SSL
define('SMTP_USERNAME', 'noreply@tuodominio.com');
define('SMTP_PASSWORD', 'tua-password-email');
```

## Tipi di Email Inviate

### 1. Notifica Richiesta Registrazione (agli Admin)
- **Trigger**: Nuovo utente richiede registrazione
- **Destinatari**: Tutti gli amministratori
- **Contenuto**: Dettagli utente e link pannello admin

### 2. Approvazione Registrazione (all'Utente)
- **Trigger**: Admin approva richiesta
- **Destinatario**: Utente richiedente
- **Contenuto**: Conferma approvazione e link login

### 3. Rifiuto Registrazione (all'Utente)
- **Trigger**: Admin rifiuta richiesta
- **Destinatario**: Utente richiedente  
- **Contenuto**: Notifica rifiuto

### 4. Reset Password (all'Utente)
- **Trigger**: Richiesta reset password
- **Destinatario**: Utente (solo se approvato)
- **Contenuto**: Link sicuro per reset

## Risoluzione Problemi

### Errore: "Nessun amministratore trovato"
```bash
# Esegui setup amministratore
php first_setup.php
# O accedi direttamente a yoursite.com/first_setup.php
```

### Email non vengono inviate
```php
// Test configurazione in config.php
$test = sendEmail('test@example.com', 'Test', 'Messaggio di test');
var_dump($test); // true = successo, false = errore
```

### Errori di Migrazione
```bash
# Ripristina backup
mysql -u root -p wordpress_checklist_3 < backup_YYYYMMDD.sql

# Verifica permessi utente database
GRANT ALL PRIVILEGES ON wordpress_checklist_3.* TO 'user'@'localhost';
```

### Problemi Sessioni/Login
```bash
# Verifica permessi directory sessioni
sudo chown -R www-data:www-data /var/lib/php/sessions
sudo chmod 775 /var/lib/php/sessions
```

## Aggiornamenti Futuri

### Backup Raccomandati Prima di Aggiornamenti
```bash
# Database
mysqldump -u root -p wordpress_checklist_3 > backup_$(date +%Y%m%d_%H%M).sql

# File applicazione  
tar -czf backup_files_$(date +%Y%m%d_%H%M).tar.gz /path/to/wordpress-checklist/
```

### Log Controlli
```bash
# Attiva log PHP per debug
echo "log_errors = On" >> /etc/php/8.1/apache2/php.ini
echo "error_log = /var/log/php_errors.log" >> /etc/php/8.1/apache2/php.ini
```

## Caratteristiche Avanzate

### Multi-Tenancy
Il sistema è predisposto per gestione multi-tenant:
- Utenti isolati per progetto
- Admin possono vedere tutto
- Statistiche aggregate disponibili

### API Future
Struttura predisposta per future API:
- Autenticazione token-based
- Endpoints REST per progetti/checklist
- Webhook per integrazioni esterne

### Personalizzazioni
- Temi colore in config.php
- Logo personalizzabile in /img/
- Template email personalizzabili
- Checklist import/export JSON

## Supporto e Contributi

Per problemi, bug report o richieste di funzionalità:
1. Verifica la documentazione
2. Controlla i log di errore
3. Crea issue dettagliato con:
   - Versione PHP/MySQL
   - Passi per riprodurre il problema
   - Log errori rilevanti
   - Screenshot se pertinenti

Questo sistema fornisce ora una base solida e sicura per la gestione delle checklist WordPress con controllo completo degli accessi e comunicazione efficace con gli utenti.