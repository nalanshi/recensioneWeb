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
]);
session_start();

// Inclusione del file di configurazione del database
require_once 'database.php';

// Funzione per reindirizzare con un messaggio di errore
function redirectWithError($error) {
    $_SESSION['registration_error'] = $error;
    header("Location: registrazione.php");
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
    $header = file_get_contents("../static/header.html");
    $footer = file_get_contents("../static/footer.html");

    if (!SessionManager::isLoggedIn()) {
        $headerLoginHtml = "<a href='login.php' class='login-link' aria-label='Login - Accedi al tuo account'>\n" .
            "  <div class='user-icon-bg'>\n" .
            "    <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>\n" .
            "      <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>\n" .
            "      <circle cx='12' cy='7' r='4'></circle>\n" .
            "    </svg>\n" .
            "  </div>\n" .
            "  <span class='login-text'>Login</span>\n" .
            "</a>";
    } else {
        $username = $_SESSION['username'];
        $profilePhoto = $_SESSION['user_data']['profile_photo'] ?? $_SESSION['profile_photo'] ?? '';
        $icon = $profilePhoto ? "<img src='../{$profilePhoto}' alt='Foto profilo di {$username}' class='user-avatar'>" : "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";
        $headerLoginHtml = "<details class='user-menu'>\n" .
            "  <summary class='login-link' aria-label='Menu utente'>\n" .
            "    <div class='user-icon-bg'>\n" .
            "      {$icon}\n" .
            "    </div>\n" .
            "    <span class='login-text'>{$username}</span>\n" .
            "  </summary>\n" .
            "  <div class='user-menu-panel'>\n" .
            "    <a href='dashboard.php'><span lang='en'>Dashboard</span></a>";
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $headerLoginHtml .= "<a href='gestione_recensioni.php'><span>Gestione recensioni</span></a>";
        }
        $headerLoginHtml .= "<a href='logout.php'><span lang='en'>Logout</span></a>\n" .
            "  </div>\n" .
            "</details>";
    }

    $header = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $header);
    $header = Utils::markCurrentNavLink($header, basename($_SERVER['PHP_SELF']));

    $template = file_get_contents("../static/registrazione.html");
    $template = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $template);
    $template = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $template);

    $errorHtml = '';
    if (isset($_SESSION['registration_error'])) {
        $msg = htmlspecialchars($_SESSION['registration_error']);
        $errorHtml = "<div class='message error'>\n" .
            "  <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>\n" .
            "    <circle cx='12' cy='12' r='10'></circle>\n" .
            "    <line x1='15' y1='9' x2='9' y2='15'></line>\n" .
            "    <line x1='9' y1='9' x2='15' y2='15'></line>\n" .
            "  </svg>\n" .
            "  {$msg}\n" .
            "</div>";
        unset($_SESSION['registration_error']);
    }
    $template = str_replace("<!-- ERROR_MESSAGE_PLACEHOLDER -->", $errorHtml, $template);

    echo $template;
}
?>
