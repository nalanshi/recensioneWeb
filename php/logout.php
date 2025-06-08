<?php
/**
 * Script per la gestione del logout degli utenti
 * 
 * Questo script gestisce il processo di logout, distruggendo la sessione corrente
 * e reindirizzando l'utente alla pagina principale.
 * 
 * @author DishDiveReview Team
 * @version 1.0
 */

// Avvio della sessione
session_start();

// Inclusione del file di configurazione del database
require_once 'database.php';

// Verifica che l'utente sia effettivamente loggato
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    // Elimina il cookie "remember_token" se esiste
    if (isset($_COOKIE['remember_token'])) {
        // Connessione al database per eliminare il token
        try {
            // Utilizzo della connessione dal file database.php
            $pdo = DatabaseConfig::getConnection();

            // Elimina il token dal database
            $stmt = $pdo->prepare("UPDATE utenti SET remember_token = NULL WHERE id = :id");
            $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            // Registra l'errore ma continua con il logout
            error_log("Errore durante l'eliminazione del token: " . $e->getMessage());
        }

        // Elimina il cookie lato client
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }

    // Distruggi tutti i dati della sessione
    $_SESSION = array();

    // Se è stato avviato un cookie di sessione, distruggilo
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Distruggi la sessione
    session_destroy();
}

// Imposta un messaggio di successo
session_start();
$_SESSION['logout_success'] = "Hai effettuato il logout con successo.";

// Reindirizza alla pagina principale
header("Location: ../index.php");
exit();
?>
