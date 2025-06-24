<?php
/**
 * API per la gestione del profilo utente
 */

require_once 'database.php';
require_once __DIR__ . '/classes/SessionManager.php';
require_once __DIR__ . '/classes/Utils.php';
// Avvia la sessione
SessionManager::start();

// Imposta header per JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
$userManager = new UserManager();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Ottieni dati del profilo
            $userData = $userManager->getUserById($userId);
            if ($userData) {
                // Rimuovi dati sensibili
                unset($userData['password_hash']);
                echo json_encode(['success' => true, 'data' => $userData]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
            }
            break;
            
        case 'POST':
            // Distingui tra aggiornamento profilo e upload immagine
            if (isset($_FILES['profile_image'])) {
                // Gestione upload immagine profilo
                $uploadResult = Utils::handlePhotoUpload($_FILES['profile_image'], $userId);
                
                if ($uploadResult['success']) {
                    if ($userManager->updateProfilePhoto($userId, $uploadResult['path'])) {
                        echo json_encode(['success' => true, 'message' => 'Immagine profilo aggiornata con successo', 'image_path' => '../' . $uploadResult['path']]);
                    } else {
                        // Se l'aggiornamento del database fallisce, rimuovi il file caricato
                        unlink('../' . $uploadResult['path']);
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento del percorso immagine nel database']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
                }
            } else {
                // Aggiorna profilo (dati testuali)
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Verifica CSRF token
                if (!Utils::verifyCSRFToken($input['csrf_token'] ?? '')) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                    break;
                }
                
                // Sanitizza i dati
                $data = [
                    'first_name' => Utils::sanitizeInput($input['first_name'] ?? ''),
                    'last_name' => Utils::sanitizeInput($input['last_name'] ?? ''),
                    'email' => Utils::sanitizeInput($input['email'] ?? ''),
                    'birth_day' => (int)($input['birth_day'] ?? 0),
                    'birth_month' => (int)($input['birth_month'] ?? 0),
                    'birth_year' => (int)($input['birth_year'] ?? 0)
                ];
                
                // Valida i dati
                $errors = Utils::validateProfileData($data);
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                    break;
                }
                
                // Aggiorna il profilo
                if ($userManager->updateProfile($userId, $data)) {
                    echo json_encode(['success' => true, 'message' => 'Profilo aggiornato con successo']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
                }
            }
            break;
            
        case 'DELETE':
            // Elimina account
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Verifica CSRF token
            if (!Utils::verifyCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                break;
            }
            
            // Verifica conferma
            if (($input['confirmation'] ?? '') !== 'ELIMINA') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Conferma non valida']);
                break;
            }
            
            // Elimina account
            if ($userManager->deleteAccount($userId)) {
                SessionManager::logout();
                echo json_encode(['success' => true, 'message' => 'Account eliminato con successo']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
            break;
    }
} catch (Exception $e) {
    error_log("Errore API profilo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
}
?>

