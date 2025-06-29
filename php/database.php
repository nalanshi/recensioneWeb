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


            return $pdo;
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
     * Crea una nuova recensione
     */
    public function createReview($userId, $data) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO reviews (user_id, title, content, product_name, product_image, created_at) " .
                "VALUES (?, ?, ?, ?, ?, NOW())"
            );
            return $stmt->execute([
                $userId,
                $data['title'],
                $data['content'],
                $data['product_name'],
                $data['product_image']
            ]);
        } catch (PDOException $e) {
            error_log("Errore createReview: " . $e->getMessage());
            return false;
        }
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
            $stmt = $this->db->prepare(
                "SELECT r.id, r.title, r.content, r.product_name,
                        r.product_image, r.created_at, r.updated_at,
                        AVG(c.rating) AS rating
                 FROM reviews r
                 LEFT JOIN comments c ON r.id = c.review_id
                 WHERE {$whereClause}
                 GROUP BY r.id
                 ORDER BY r.created_at DESC
                 LIMIT ? OFFSET ?"
            );

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
    public function deleteReview($reviewId, $userId = null) {
        try {
            if ($userId === null) {
                $stmt = $this->db->prepare("UPDATE reviews SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$reviewId]);
            } else {
                $stmt = $this->db->prepare("UPDATE reviews SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
                $stmt->execute([$reviewId, $userId]);
            }
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Errore deleteReview: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aggiorna una recensione
     */
    public function updateReview($reviewId, $data, $userId = null) {
        try {
            if ($userId === null) {
                $stmt = $this->db->prepare("UPDATE reviews SET title = ?, content = ?, product_name = ?, product_image = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
                return $stmt->execute([
                    $data['title'],
                    $data['content'],
                    $data['product_name'],
                    $data['product_image'],
                    $reviewId
                ]);
            }
            $stmt = $this->db->prepare("UPDATE reviews SET title = ?, content = ?, product_name = ?, product_image = ?, updated_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
            return $stmt->execute([
                $data['title'],
                $data['content'],
                $data['product_name'],
                $data['product_image'],
                $reviewId,
                $userId
            ]);
        } catch (PDOException $e) {
            error_log("Errore updateReview: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene una singola recensione per ID
     */
    public function getReviewById($reviewId) {
        try {
            $stmt = $this->db->prepare(
                "SELECT r.*, u.username, u.email, u.profile_photo, AVG(c.rating) AS rating " .
                "FROM reviews r " .
                "JOIN utenti u ON r.user_id = u.id " .
                "LEFT JOIN comments c ON r.id = c.review_id " .
                "WHERE r.id = ? AND r.deleted_at IS NULL " .
                "GROUP BY r.id"
            );
            $stmt->execute([$reviewId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Errore getReviewById: ' . $e->getMessage());
            return false;
        }
    }
    public function getAllReviews($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $stmt = $this->db->prepare("SELECT r.id, r.title, r.content, r.product_name, r.product_image, r.created_at, r.updated_at, u.username, AVG(c.rating) AS rating FROM reviews r JOIN utenti u ON r.user_id = u.id LEFT JOIN comments c ON r.id = c.review_id WHERE r.deleted_at IS NULL GROUP BY r.id ORDER BY r.created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            $reviews = $stmt->fetchAll();
            $count = $this->db->query("SELECT COUNT(*) as total FROM reviews WHERE deleted_at IS NULL")->fetch()['total'];
            return ['reviews' => $reviews, 'total' => $count, 'page' => $page, 'limit' => $limit, 'total_pages' => ceil($count / $limit)];
        } catch (PDOException $e) {
            error_log('Errore getAllReviews: ' . $e->getMessage());
            return false;
        }
    }

    public function getFilteredReviews($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where = ["r.deleted_at IS NULL"];
            $params = [];
            if (!empty($filters['search'])) {
                $where[] = "(LOWER(r.product_name) LIKE LOWER(?) OR LOWER(r.title) LIKE LOWER(?))";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }
            $whereClause = implode(' AND ', $where);
            $order = "r.created_at DESC";
            if (!empty($filters['sort']) && $filters['sort'] === 'rating') {
                $order = "rating DESC";
            }
            $having = '';
            if (!empty($filters['rating'])) {
                $having = "HAVING rating >= ?";
            }

            $sql = "SELECT r.id, r.title, r.content, r.product_name, r.product_image, r.created_at, r.updated_at, u.username, AVG(c.rating) AS rating
                    FROM reviews r
                    JOIN utenti u ON r.user_id = u.id
                    LEFT JOIN comments c ON r.id = c.review_id
                    WHERE {$whereClause}
                    GROUP BY r.id
                    {$having}
                    ORDER BY {$order}
                    LIMIT ? OFFSET ?";

            $stmt = $this->db->prepare($sql);
            $execParams = $params;
            if (!empty($filters['rating'])) {
                $execParams[] = $filters['rating'];
            }
            $execParams[] = $limit;
            $execParams[] = $offset;
            $stmt->execute($execParams);
            $reviews = $stmt->fetchAll();

            $countSql = "SELECT COUNT(*) as total FROM (
                            SELECT r.id, AVG(c.rating) AS rating
                            FROM reviews r
                            LEFT JOIN comments c ON r.id = c.review_id
                            WHERE {$whereClause}
                            GROUP BY r.id
                            {$having}
                        ) t";
            $countStmt = $this->db->prepare($countSql);
            $countParams = $params;
            if (!empty($filters['rating'])) {
                $countParams[] = $filters['rating'];
            }
            $countStmt->execute($countParams);
            $count = $countStmt->fetch()['total'];

            return ['reviews' => $reviews, 'total' => $count, 'page' => $page, 'limit' => $limit, 'total_pages' => ceil($count / $limit)];
        } catch (PDOException $e) {
            error_log('Errore getFilteredReviews: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restituisce i prodotti ordinati per valutazione media delle recensioni
     */
    public function getTopProducts($limit = 10) {
        try {
            $stmt = $this->db->prepare(
                "SELECT r.product_name, r.product_image, " .
                "COALESCE(AVG(c.rating), 0) AS avg_rating, COUNT(c.id) AS review_count, " .
                "MIN(r.id) AS review_id " .
                "FROM reviews r " .
                "LEFT JOIN comments c ON r.id = c.review_id " .
                "WHERE r.deleted_at IS NULL " .
                "GROUP BY r.product_name, r.product_image " .
                "ORDER BY avg_rating DESC, review_count DESC " .
                "LIMIT ?"
            );
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Errore getTopProducts: ' . $e->getMessage());
            return [];
        }
    }

}

/**
 * Classe per la gestione dei commenti alle recensioni
 */
class CommentManager {
    private $db;

    public function __construct() {
        $this->db = DatabaseConfig::getConnection();
    }

    /**
     * Crea un nuovo commento
     */
    public function createComment($reviewId, $username, $email, $rating, $content) {
        try {
            // Verifica se esiste già un commento dello stesso utente per la stessa recensione
            $check = $this->db->prepare("SELECT id FROM comments WHERE review_id = ? AND username = ?");
            $check->execute([$reviewId, $username]);
            if ($existing = $check->fetch()) {
                $stmt = $this->db->prepare("UPDATE comments SET rating = ?, content = ?, created_at = NOW() WHERE id = ?");
                return $stmt->execute([$rating, $content, $existing['id']]);
            }

            $stmt = $this->db->prepare("INSERT INTO comments (review_id, username, email, rating, content, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([$reviewId, $username, $email, $rating, $content]);
        } catch (PDOException $e) {
            error_log('Errore createComment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene i commenti per una recensione
     */
    public function getComments($reviewId, $limit = null) {
        try {
            $sql = "SELECT username, email, rating, content, created_at FROM comments WHERE review_id = ? ORDER BY created_at DESC";
            if ($limit) {
                $sql .= " LIMIT " . intval($limit);
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$reviewId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Errore getComments: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ottiene il commento di un utente per una specifica recensione
     */
    public function getUserCommentForReview($reviewId, $username) {
        try {
            $stmt = $this->db->prepare("SELECT id, rating, content, created_at FROM comments WHERE review_id = ? AND username = ?");
            $stmt->execute([$reviewId, $username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('Errore getUserCommentForReview: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene i commenti di un utente con paginazione e filtri
     */
    public function getUserComments($username, $page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;

            $where = ["c.username = ?"];
            $params = [$username];

            if (!empty($filters['search'])) {
                $where[] = "(LOWER(c.content) LIKE LOWER(?) OR LOWER(r.title) LIKE LOWER(?))";
                $search = '%' . $filters['search'] . '%';
                $params[] = $search;
                $params[] = $search;
            }

            if (!empty($filters['rating'])) {
                $where[] = "c.rating = ?";
                $params[] = $filters['rating'];
            }

            $whereClause = implode(' AND ', $where);

            $stmt = $this->db->prepare(
                "SELECT c.id, c.review_id, c.rating, c.content, c.created_at, r.title
                 FROM comments c
                 JOIN reviews r ON c.review_id = r.id
                 WHERE {$whereClause}
                 ORDER BY c.created_at DESC
                 LIMIT ? OFFSET ?"
            );

            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $comments = $stmt->fetchAll();

            $countStmt = $this->db->prepare(
                "SELECT COUNT(*) as total
                 FROM comments c JOIN reviews r ON c.review_id = r.id
                 WHERE {$whereClause}"
            );
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetch()['total'];

            return [
                'comments' => $comments,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
        } catch (PDOException $e) {
            error_log('Errore getUserComments: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aggiorna un commento
     */
    public function updateComment($commentId, $data, $username = null) {
        try {
            if ($username === null) {
                $stmt = $this->db->prepare("UPDATE comments SET rating = ?, content = ? WHERE id = ?");
                return $stmt->execute([$data['rating'], $data['content'], $commentId]);
            }
            $stmt = $this->db->prepare("UPDATE comments SET rating = ?, content = ? WHERE id = ? AND username = ?");
            $stmt->execute([$data['rating'], $data['content'], $commentId, $username]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Errore updateComment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un commento
     */
    public function deleteComment($commentId, $username = null) {
        try {
            if ($username === null) {
                $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$commentId]);
            } else {
                $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ? AND username = ?");
                $stmt->execute([$commentId, $username]);
            }
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Errore deleteComment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcola la valutazione media dei commenti per un determinato prodotto
     */
    public function getAverageRatingForProduct($productName) {
        try {
            $stmt = $this->db->prepare(
                "SELECT AVG(c.rating) AS avg_rating FROM comments c " .
                "JOIN reviews r ON c.review_id = r.id " .
                "WHERE r.product_name = ? AND r.deleted_at IS NULL"
            );
            $stmt->execute([$productName]);
            $row = $stmt->fetch();
            return $row && $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 2) : 0;
        } catch (PDOException $e) {
            error_log('Errore getAverageRatingForProduct: ' . $e->getMessage());
            return 0;
        }
    }

    public function getAverageRatingForReview($reviewId) {
        try {
            $stmt = $this->db->prepare("SELECT AVG(rating) AS avg_rating FROM comments WHERE review_id = ?");
            $stmt->execute([$reviewId]);
            $row = $stmt->fetch();
            return $row && $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 2) : 0;
        } catch (PDOException $e) {
            error_log('Errore getAverageRatingForReview: ' . $e->getMessage());
            return 0;
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

        // Ridimensiona e comprime la foto prima di salvarla
        $imageResource = null;
        switch ($file['type']) {
            case 'image/jpeg':
                $imageResource = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $imageResource = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $imageResource = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $imageResource = imagecreatefromwebp($file['tmp_name']);
                }
                break;
        }

        if (!$imageResource) {
            return ['success' => false, 'message' => 'Errore elaborazione immagine'];
        }

        $maxDimension = 256;
        $width = imagesx($imageResource);
        $height = imagesy($imageResource);
        $scale = min(1, $maxDimension / max($width, $height));
        $newWidth = (int)($width * $scale);
        $newHeight = (int)($height * $scale);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if (in_array($file['type'], ['image/png', 'image/gif', 'image/webp'])) {
            imagecolortransparent($resized, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $saved = false;
        switch ($file['type']) {
            case 'image/jpeg':
                $saved = imagejpeg($resized, $filepath, 85);
                break;
            case 'image/png':
                $saved = imagepng($resized, $filepath, 6);
                break;
            case 'image/gif':
                $saved = imagegif($resized, $filepath);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $saved = imagewebp($resized, $filepath, 85);
                }
                break;
        }

        imagedestroy($imageResource);
        imagedestroy($resized);

        if ($saved) {
            return ['success' => true, 'path' => 'images/profiles/' . $filename];
        }

        return ['success' => false, 'message' => 'Errore durante l\'upload'];
    }

    /**
     * Gestisce l'upload dell'immagine prodotto
     */
    public static function handleProductImageUpload($file) {
        $uploadDir = '../images/products/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo di file non supportato'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File troppo grande (max 5MB)'];
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'path' => 'images/products/' . $filename];
        }

        return ['success' => false, 'message' => 'Errore durante l\'upload'];
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
        $ratings = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $ratings .= '<i class="fas fa-rating rating"></i>';
            } else {
                $ratings .= '<i class="fas fa-rating rating empty"></i>';
            }
        }
        return $ratings;
    }

    /**
     * Sanitizza l'input dell'utente
     */
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
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
            header('Location: login.php');
            exit;
        }
    }
}
?>
