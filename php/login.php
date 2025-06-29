<?php
/**
 * Script per la gestione del login degli utenti
 * 
 * Questo script gestisce il processo di autenticazione, verificando le credenziali
 * dell'utente e creando una sessione in caso di successo.
 * 
 * @author DishDiveReview Team
 * @version 1.1
 */

// Inclusione del file di configurazione del database
require_once 'database.php';

// Avvio della sessione con impostazioni di sicurezza
session_set_cookie_params([
    'lifetime' => 3600,           // Durata del cookie di sessione (1 ora)
    'path' => '/',                // Percorso del cookie
]);
SessionManager::start();

// Funzione per reindirizzare con un messaggio di errore
function redirectWithError($error) {
    $_SESSION['login_error'] = $error;
    header("Location: login.php");
    exit();
}

// Verifica se la richiesta è di tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupero e sanificazione dei dati dal form
    $username = isset($_POST['username']) ? Utils::sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Non sanifichiamo la password
    $remember = isset($_POST['remember']) ? true : false;

    // Validazione dei dati
    if (empty($username)) {
        redirectWithError("Il campo username è obbligatorio.");
    }

    if (empty($password)) {
        redirectWithError("Il campo password è obbligatorio.");
    }

    // Connessione al database
    try {
        // Utilizzo della connessione dal file database.php
        $pdo = DatabaseConfig::getConnection();

        // Preparazione della query per verificare le credenziali tramite username
        $stmt = $pdo->prepare("SELECT * FROM utenti WHERE username = :username AND deleted_at IS NULL");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        // Verifica se l'utente esiste
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica della password
            if (password_verify($password, $user['password'])) {
                // Utilizzo della classe SessionManager per il login
                $userData = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'nome' => $user['nome'],
                    'cognome' => $user['cognome'],
                    'profile_photo' => $user['profile_photo']
                ];

                SessionManager::login($user['id'], $userData);

                // Imposta anche le variabili di sessione tradizionali per compatibilità
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_logged_in'] = true;
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_photo'] = $user['profile_photo'];

                // Se l'utente ha selezionato "Ricordami", impostiamo un cookie
                if ($remember) {
                    $token = bin2hex(random_bytes(32)); // Genera un token sicuro

                    // Salva il token nel database
                    $stmt = $pdo->prepare("UPDATE utenti SET remember_token = :token WHERE id = :id");
                    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
                    $stmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                    $stmt->execute();

                    // Imposta il cookie (valido per 30 giorni)
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                }

                // Reindirizzamento alla pagina principale
                header("Location: ../index.php");
                exit();
            } else {
                // Password errata
                redirectWithError("Username o password non validi.");
            }
        } else {
            // Utente non trovato
            redirectWithError("Username o password non validi.");
        }
    } catch (PDOException $e) {
        // Errore di connessione al database
        error_log("Errore di connessione al database: " . $e->getMessage());
        redirectWithError("Si è verificato un errore durante l'accesso. Riprova più tardi.");
    }
} else {
    $header = file_get_contents("../static/header.html");
    $footer = file_get_contents("../static/footer.html");

    $homeLink = "<a href='../index.php' class='login-link' aria-label='Torna alla homepage' aria-current='page'>\n" .
        "  <div class='user-icon-bg'>\n" .
        "    <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>\n" .
        "      <path d='M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'></path>\n" .
        "      <polyline points='9,22 9,12 15,12 15,22'></polyline>\n" .
        "    </svg>\n" .
        "  </div>\n" .
        "  <span class='login-text'>Home</span>\n" .
        "</a>";
    $header = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $homeLink, $header);

    $template = file_get_contents("../static/login.html");
    $template = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $template);
    $template = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $template);

    $errorHtml = '';
    if (isset($_SESSION['login_error'])) {
        $msg = htmlspecialchars($_SESSION['login_error']);
        $errorHtml = "<div class='message error'>\n" .
            "  <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>\n" .
            "    <circle cx='12' cy='12' r='10'></circle>\n" .
            "    <line x1='15' y1='9' x2='9' y2='15'></line>\n" .
            "    <line x1='9' y1='9' x2='15' y2='15'></line>\n" .
            "  </svg>\n" .
            "  {$msg}\n" .
            "</div>";
        unset($_SESSION['login_error']);
    }
    $template = str_replace("<!-- ERROR_MESSAGE_PLACEHOLDER -->", $errorHtml, $template);

    echo $template;
}
?>
