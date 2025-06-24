<?php
/**
 * Configurazione del database
 * Modifica questi parametri secondo la tua configurazione
 */

class DatabaseConfig {
    private static $host = 'mysql2.sqlpub.com';
    private static $port = '3307';
    private static $dbname = 'databaserecensione';
    private static $username = 'testnalan';
    private static $password = 'l5jKAzMduE6bgAWH';

    public static function getConnection() {
        try {
            $dsn = "mysql:host=" . self::$host;
            if (!empty(self::$port)) {
                $dsn .= ";port=" . self::$port;
            }
            $dsn .= ";dbname=" . self::$dbname . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $pdo = new PDO($dsn, self::$username, self::$password, $options);

            // Stampa le tabelle presenti nel database
            // self::printDatabaseTables($pdo); // Commentato per evitare output indesiderato

            return $pdo;
        } catch (PDOException $e) {
            error_log("Errore connessione database: " . $e->getMessage());
            throw new Exception("Errore di connessione al database");
        }
    }

    /**
     * Stampa tutte le tabelle presenti nel database
     */
    private static function printDatabaseTables($pdo) {
        try {
            // Query per ottenere tutte le tabelle nel database MySQL
            $stmt = $pdo->query("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = '" . self::$dbname . "' 
                ORDER BY table_name
            ");

            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo "<h2>Tabelle presenti nel database:</h2>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";
        } catch (PDOException $e) {
            error_log("Errore durante l'elenco delle tabelle: " . $e->getMessage());
            echo "<p>Impossibile elencare le tabelle del database.</p>";
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
                SELECT id, username, email, nome as first_name, cognome as last_name, 
                       profile_photo, birth_day, birth_month, birth_year,
                       created_at, updated_at
                FROM utenti 
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
                UPDATE utenti 
                SET nome = ?, cognome = ?, email = ?, 
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
                UPDATE utenti 
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
                SELECT password as password_hash 
                FROM utenti 
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
                UPDATE utenti 
                SET password = ?, updated_at = NOW()
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
                UPDATE utenti 
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
                $whereConditions[] = "(LOWER(r.title) LIKE LOWER(?) OR LOWER(r.content) LIKE LOWER(?) OR LOWER(r.product_name) LIKE LOWER(?))";
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
}