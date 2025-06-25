<?php
/**
 * Class SettingsManager
 * Gestisce le impostazioni dell'utente nel database.
 */
class SettingsManager {
    private $pdo;

    public function __construct() {
        // Connessione al database tramite la configurazione condivisa
        $this->pdo = DatabaseConfig::getConnection();
    }

    /**
     * Ottiene le impostazioni dell'utente
     * @param int $userId ID dell'utente
     * @return array|false Array con le impostazioni o false in caso di errore
     */
    public function getUserSettings(int $userId): array|false {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM user_settings WHERE user_id = ?');
            if (!$stmt->execute([$userId])) {
                error_log('Errore nell\'esecuzione della query per le impostazioni utente');
                return false;
            }

            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            return $settings ? $settings : [];
        } catch (PDOException $e) {
            error_log('PDOException in getUserSettings: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log('Errore generico in getUserSettings: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aggiorna le impostazioni dell'utente
     * @param int $userId ID dell'utente
     * @param array $settings Array con le impostazioni da aggiornare
     * @return bool True se l'aggiornamento è avvenuto con successo, false altrimenti
     */
    public function updateSettings(int $userId, array $settings): bool {
        try {
            foreach ($settings as $key => $value) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO user_settings (user_id, setting_key, setting_value)
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
                );

                if (!$stmt->execute([$userId, $key, $value])) {
                    error_log("Errore nell'aggiornamento della chiave $key");
                    return false;
                }
            }
            return true;
        } catch (PDOException $e) {
            error_log('PDOException in updateSettings: ' . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log('Errore generico in updateSettings: ' . $e->getMessage());
            return false;
        }
    }
}
?>
<?php
/**
 * API per la gestione delle impostazioni
 */

// Impedisci la visualizzazione degli errori PHP direttamente nel browser
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'database.php';

// Avvia la sessione
SessionManager::start();

// Registra un shutdown function per gestire gli errori fatali
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore fatale del server']);
    }
});

// Imposta header per JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Gestisce richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verifica che l'utente sia loggato
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

$userId = SessionManager::getUserId();
$settingsManager = new SettingsManager();


try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Ottieni impostazioni dell'utente
            $settings = $settingsManager->getUserSettings($userId);
            if ($settings !== false) {
                echo json_encode(['success' => true, 'data' => $settings]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle impostazioni']);
            }
            break;
            
        case 'POST':
            // Aggiorna impostazioni
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Verifica CSRF token
            if (!Utils::verifyCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                break;
            }
            
            // Impostazioni valide
            $validSettings = [
                'theme' => ['light', 'dark', 'auto'],
                'language' => ['it', 'en', 'es', 'fr'],
                'email_notifications' => ['0', '1'],
                'review_notifications' => ['0', '1'],
                'profile_visibility' => ['0', '1'],
                'show_email' => ['0', '1']
            ];
            
            $settings = [];
            foreach ($validSettings as $key => $allowedValues) {
                if (isset($input[$key])) {
                    $value = Utils::sanitizeInput($input[$key]);
                    if (in_array($value, $allowedValues)) {
                        $settings[$key] = $value;
                    }
                }
            }
            
            if (empty($settings)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nessuna impostazione valida fornita', 'received' => $input]);
                break;
            }
            
            // Aggiorna le impostazioni
            if ($settingsManager->updateSettings($userId, $settings)) {
                echo json_encode(['success' => true, 'message' => 'Impostazioni aggiornate con successo']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
            break;
    }
} catch (Exception $e) {
    error_log("Errore API impostazioni: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
}
?>