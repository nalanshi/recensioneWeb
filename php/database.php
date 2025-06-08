<?php
/**
 * Configurazione del database
 * Modifica questi parametri secondo la tua configurazione
 */

class DatabaseConfig {
    private static $host = 'localhost';
    private static $dbname = 'recensioni_db';
    private static $username = 'root';
    private static $password = '';
    private static $charset = 'utf8mb4';
    
    public static function getConnection() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            return new PDO($dsn, self::$username, self::$password, $options);
        } catch (PDOException $e) {
            error_log("Errore connessione database: " . $e->getMessage());
            throw new Exception("Errore di connessione al database");
        }
    }
}

/**
 * Classe per la gestione degli utenti
 */
class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConfig::getConnection();
    }
    
    /**
     * Ottiene i dati dell'utente per ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, first_name, last_name, 
                       profile_photo, birth_day, birth_month, birth_year,
                       created_at, updated_at
                FROM users 
                WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Errore getUserById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiorna i dati del profilo utente
     */
    public function updateProfile($userId, $data) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, 
                    birth_day = ?, birth_month = ?, birth_year = ?,
                    updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ");
            
            $result = $stmt->execute([
                $data['first_name'],
                $data['last_name'], 
                $data['email'],
                $data['birth_day'],
                $data['birth_month'],
                $data['birth_year'],
                $userId
            ]);
            
            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Errore updateProfile: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiorna la foto del profilo
     */
    public function updateProfilePhoto($userId, $photoPath) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET profile_photo = ?, updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ");
            return $stmt->execute([$photoPath, $userId]);
        } catch (PDOException $e) {
            error_log("Errore updateProfilePhoto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cambia la password dell'utente
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verifica password attuale
            $stmt = $this->db->prepare("
                SELECT password_hash 
                FROM users 
                WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Password attuale non corretta'];
            }
            
            // Aggiorna password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = ?, updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ");
            
            $result = $stmt->execute([$newPasswordHash, $userId]);
            
            return [
                'success' => $result,
                'message' => $result ? 'Password aggiornata con successo' : 'Errore nell\'aggiornamento'
            ];
        } catch (PDOException $e) {
            error_log("Errore changePassword: " . $e->getMessage());
            return ['success' => false, 'message' => 'Errore interno del server'];
        }
    }
    
    /**
     * Elimina l'account utente (soft delete)
     */
    public function deleteAccount($userId) {
        try {
            $this->db->beginTransaction();
            
            // Soft delete dell'utente
            $stmt = $this->db->prepare("
                UPDATE users 
                SET deleted_at = NOW(), email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP())
                WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$userId]);
            
            // Soft delete delle recensioni
            $stmt = $this->db->prepare("
                UPDATE reviews 
                SET deleted_at = NOW()
                WHERE user_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$userId]);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Errore deleteAccount: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Classe per la gestione delle recensioni
 */
class ReviewManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConfig::getConnection();
    }
    
    /**
     * Ottiene le recensioni dell'utente con paginazione e filtri
     */
    public function getUserReviews($userId, $page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Costruzione query con filtri
            $whereConditions = ["r.user_id = ?", "r.deleted_at IS NULL"];
            $params = [$userId];
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(r.title LIKE ? OR r.content LIKE ? OR r.product_name LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['rating'])) {
                $whereConditions[] = "r.rating = ?";
                $params[] = $filters['rating'];
            }
            
            if (!empty($filters['date_filter'])) {
                switch ($filters['date_filter']) {
                    case 'week':
                        $whereConditions[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                        break;
                    case 'month':
                        $whereConditions[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                    case 'year':
                        $whereConditions[] = "r.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                        break;
                }
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Query per le recensioni
            $stmt = $this->db->prepare("
                SELECT r.id, r.title, r.content, r.rating, r.product_name, 
                       r.product_image, r.created_at, r.updated_at,
                       COUNT(rl.id) as likes_count
                FROM reviews r
                LEFT JOIN review_likes rl ON r.id = rl.review_id AND rl.deleted_at IS NULL
                WHERE {$whereClause}
                GROUP BY r.id
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $reviews = $stmt->fetchAll();
            
            // Query per il conteggio totale
            $countParams = array_slice($params, 0, -2); // Rimuovi limit e offset
            $countStmt = $this->db->prepare("
                SELECT COUNT(DISTINCT r.id) as total
                FROM reviews r
                WHERE {$whereClause}
            ");
            $countStmt->execute($countParams);
            $total = $countStmt->fetch()['total'];
            
            return [
                'reviews' => $reviews,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log("Errore getUserReviews: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina una recensione (soft delete)
     */
    public function deleteReview($reviewId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE reviews 
                SET deleted_at = NOW()
                WHERE id = ? AND user_id = ? AND deleted_at IS NULL
            ");
            return $stmt->execute([$reviewId, $userId]);
        } catch (PDOException $e) {
            error_log("Errore deleteReview: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiorna una recensione
     */
    public function updateReview($reviewId, $userId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE reviews 
                SET title = ?, content = ?, rating = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ? AND deleted_at IS NULL
            ");
            return $stmt->execute([
                $data['title'],
                $data['content'],
                $data['rating'],
                $reviewId,
                $userId
            ]);
        } catch (PDOException $e) {
            error_log("Errore updateReview: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Classe per la gestione delle impostazioni utente
 */
class SettingsManager {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConfig::getConnection();
    }
    
    /**
     * Ottiene le impostazioni dell'utente
     */
    public function getUserSettings($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT setting_key, setting_value
                FROM user_settings 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $settings = $stmt->fetchAll();
            
            // Converte in array associativo
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting['setting_key']] = $setting['setting_value'];
            }
            
            // Impostazioni predefinite se non esistono
            $defaults = [
                'theme' => 'light',
                'language' => 'it',
                'email_notifications' => '1',
                'review_notifications' => '1',
                'profile_visibility' => '1',
                'show_email' => '0'
            ];
            
            return array_merge($defaults, $result);
        } catch (PDOException $e) {
            error_log("Errore getUserSettings: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiorna le impostazioni dell'utente
     */
    public function updateSettings($userId, $settings) {
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare("
                    INSERT INTO user_settings (user_id, setting_key, setting_value, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at = VALUES(updated_at)
                ");
                $stmt->execute([$userId, $key, $value]);
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Errore updateSettings: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Funzioni di utilità
 */
class Utils {
    /**
     * Valida i dati del profilo
     */
    public static function validateProfileData($data) {
        $errors = [];
        
        if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
            $errors[] = "Il nome deve contenere almeno 2 caratteri";
        }
        
        if (empty($data['last_name']) || strlen($data['last_name']) < 2) {
            $errors[] = "Il cognome deve contenere almeno 2 caratteri";
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email non valida";
        }
        
        if (!empty($data['birth_day']) && ($data['birth_day'] < 1 || $data['birth_day'] > 31)) {
            $errors[] = "Giorno di nascita non valido";
        }
        
        if (!empty($data['birth_month']) && ($data['birth_month'] < 1 || $data['birth_month'] > 12)) {
            $errors[] = "Mese di nascita non valido";
        }
        
        if (!empty($data['birth_year']) && ($data['birth_year'] < 1900 || $data['birth_year'] > date('Y'))) {
            $errors[] = "Anno di nascita non valido";
        }
        
        return $errors;
    }
    
    /**
     * Valida la password
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "La password deve contenere almeno 8 caratteri";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "La password deve contenere almeno una lettera maiuscola";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "La password deve contenere almeno un numero";
        }
        
        return $errors;
    }
    
    /**
     * Gestisce l'upload della foto profilo
     */
    public static function handlePhotoUpload($file, $userId) {
        $uploadDir = '../images/profiles/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        // Verifica tipo file
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo di file non supportato'];
        }
        
        // Verifica dimensione
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File troppo grande (max 5MB)'];
        }
        
        // Crea directory se non esiste
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Genera nome file unico
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Sposta file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'path' => 'images/profiles/' . $filename];
        } else {
            return ['success' => false, 'message' => 'Errore durante l\'upload'];
        }
    }
    
    /**
     * Formatta la data per la visualizzazione
     */
    public static function formatDate($date, $format = 'd/m/Y H:i') {
        return date($format, strtotime($date));
    }
    
    /**
     * Genera stelle per il rating
     */
    public static function generateStars($rating) {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $stars .= '<i class="fas fa-star star"></i>';
            } else {
                $stars .= '<i class="fas fa-star star empty"></i>';
            }
        }
        return $stars;
    }
    
    /**
     * Sanitizza l'input dell'utente
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Genera token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verifica token CSRF
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Gestione delle sessioni
 */
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function login($userId, $userData) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_data'] = $userData;
        $_SESSION['login_time'] = time();
    }
    
    public static function logout() {
        session_destroy();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.html');
            exit;
        }
    }
}
?>

