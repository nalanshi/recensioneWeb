<?php
/**
 * Class SessionManager
 * Gestisce le operazioni relative alla sessione utente.
 */
class SessionManager {
    /**
     * Avvia la sessione PHP con impostazioni di sicurezza.
     */
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 3600,           // Durata del cookie di sessione (1 ora)
                'path' => '/',                // Percorso del cookie
                'secure' => true,             // Cookie inviato solo su HTTPS
                'httponly' => true,           // Cookie non accessibile via JavaScript
                'samesite' => 'Lax'           // Protezione contro attacchi CSRF
            ]);
            session_start();
        }
    }

    /**
     * Effettua il login dell'utente.
     * @param int $userId ID dell'utente.
     * @param array $userData Dati dell'utente da salvare in sessione.
     */
    public static function login(int $userId, array $userData) {
        self::start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_data'] = $userData;
        $_SESSION['is_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        self::regenerateId();
    }

    public static function requireLogin() {
        header('Location: ../login.php');
        exit;
    }
    /**
     * Effettua il logout dell'utente.
     */
    public static function logout() {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Verifica se l'utente è loggato.
     * @return bool True se l'utente è loggato, false altrimenti.
     */
    public static function isLoggedIn(): bool {
        self::start();
        return isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
    }

    /**
     * Restituisce l'ID dell'utente loggato.
     * @return int|null ID dell'utente o null se non loggato.
     */
    public static function getUserId(): ?int {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Restituisce i dati dell'utente loggato.
     * @return array|null Dati dell'utente o null se non loggato.
     */
    public static function getUserData(): ?array {
        self::start();
        return $_SESSION['user_data'] ?? null;
    }

    /**
     * Rigenera l'ID della sessione per prevenire attacchi di fissazione della sessione.
     */
    public static function regenerateId() {
        session_regenerate_id(true);
    }

    /**
     * Aggiorna l'attività della sessione.
     */
    public static function updateLastActivity() {
        self::start();
        $_SESSION['last_activity'] = time();
    }

    /**
     * Verifica l'inattività della sessione e la distrugge se necessario.
     * @param int $timeout Tempo massimo di inattività in secondi.
     */
    public static function checkInactivity(int $timeout = 1800) { // 30 minuti
        self::start();
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            self::logout();
        }
        self::updateLastActivity();
    }
}
?>

