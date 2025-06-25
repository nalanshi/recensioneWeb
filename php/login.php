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
    'secure' => true,             // Cookie inviato solo su HTTPS
    'httponly' => true,           // Cookie non accessibile via JavaScript
    'samesite' => 'Strict'        // Protezione contro attacchi CSRF
]);
SessionManager::start();

// Funzione per reindirizzare con un messaggio di errore
function redirectWithError($error) {
    $_SESSION['login_error'] = $error;
    header("Location: login_form.php");
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

        // Preparazione della query per verificare le credenziali
        // Verifica sia per username che per email
        $stmt = $pdo->prepare("SELECT * FROM utenti WHERE (username = :username OR email = :email) AND deleted_at IS NULL");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $username, PDO::PARAM_STR); // Utilizziamo lo stesso valore per email
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
    // Se la richiesta non è di tipo POST, reindirizza alla pagina di login
    header("Location: login_form.php");
    exit();
}
?>
