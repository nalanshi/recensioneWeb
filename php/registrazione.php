<?php
/**
 * Script per la gestione della registrazione degli utenti
 * 
 * Questo script gestisce il processo di registrazione di nuovi utenti,
 * validando i dati inseriti e creando un nuovo record nel database.
 * 
 * @author DishDiveReview Team
 * @version 1.0
 */

// Avvio della sessione con impostazioni di sicurezza
session_set_cookie_params([
    'lifetime' => 3600,           // Durata del cookie di sessione (1 ora)
    'path' => '/',                // Percorso del cookie
    'secure' => true,             // Cookie inviato solo su HTTPS
    'httponly' => true,           // Cookie non accessibile via JavaScript
    'samesite' => 'Strict'        // Protezione contro attacchi CSRF
]);
session_start();

// Inclusione del file di configurazione del database
require_once 'database.php';

// Funzione per reindirizzare con un messaggio di errore
function redirectWithError($error) {
    $_SESSION['registration_error'] = $error;
    header("Location: ../static/registrazione.html");
    exit();
}

// Funzione per sanificare gli input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Verifica se la richiesta è di tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupero e sanificazione dei dati dal form
    $nome = isset($_POST['nome']) ? sanitizeInput($_POST['nome']) : '';
    $cognome = isset($_POST['cognome']) ? sanitizeInput($_POST['cognome']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : ''; // Non sanifichiamo la password
    $confirmPassword = isset($_POST['confirm-password']) ? $_POST['confirm-password'] : '';
    $terms = isset($_POST['terms']) ? true : false;

    // Validazione dei dati
    if (empty($nome)) {
        redirectWithError("Il campo nome è obbligatorio.");
    }

    if (empty($cognome)) {
        redirectWithError("Il campo cognome è obbligatorio.");
    }

    if (empty($email)) {
        redirectWithError("Il campo email è obbligatorio.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithError("L'indirizzo email non è valido.");
    }

    if (empty($username)) {
        redirectWithError("Il campo username è obbligatorio.");
    } elseif (strlen($username) < 3) {
        redirectWithError("L'username deve contenere almeno 3 caratteri.");
    }

    if (empty($password)) {
        redirectWithError("Il campo password è obbligatorio.");
    } elseif (strlen($password) < 8) {
        redirectWithError("La password deve contenere almeno 8 caratteri.");
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        redirectWithError("La password deve contenere almeno una lettera maiuscola, una lettera minuscola e un numero.");
    }

    if ($password !== $confirmPassword) {
        redirectWithError("Le password non coincidono.");
    }

    if (!$terms) {
        redirectWithError("Devi accettare i termini e le condizioni.");
    }

    // Connessione al database
    try {
        // Utilizzo della connessione dal file database.php
        $pdo = DatabaseConfig::getConnection();

        // Verifica se l'username o l'email sono già in uso
        $stmt = $pdo->prepare("SELECT * FROM utenti WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user['username'] === $username) {
                redirectWithError("L'username è già in uso.");
            } else {
                redirectWithError("L'indirizzo email è già in uso.");
            }
        }

        // Hash della password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Inserimento del nuovo utente nel database
        $stmt = $pdo->prepare("INSERT INTO utenti (nome, cognome, email, username, password, role, created_at) VALUES (:nome, :cognome, :email, :username, :password, 'user', NOW())");
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindParam(':cognome', $cognome, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->execute();

        // Recupera l'ID dell'utente appena creato
        $userId = $pdo->lastInsertId();

        // Creazione della sessione
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['is_logged_in'] = true;
        $_SESSION['role'] = 'user';

        // Reindirizzamento alla pagina principale con messaggio di successo
        $_SESSION['registration_success'] = "Registrazione completata con successo! Benvenuto su DishDiveReview.";
        header("Location: ../index.php");
        exit();
    } catch (PDOException $e) {
        // Errore di connessione al database
        error_log("Errore di connessione al database: " . $e->getMessage());
        redirectWithError("Si è verificato un errore durante la registrazione. Riprova più tardi.");
    }
} else {
    // Se la richiesta non è di tipo POST, reindirizza alla pagina di registrazione
    header("Location: ../static/registrazione.html");
    exit();
}
?>
