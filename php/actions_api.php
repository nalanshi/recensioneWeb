<?php
/**
 * API per gestire azioni varie come l'upload della foto profilo e il cambio password
 */

require_once 'database.php';

// Avvia la sessione
SessionManager::start();

// Imposta header per risposte JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Gestione delle richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verifica che l'utente sia autenticato
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

$userId = SessionManager::getUserId();
$userManager = new UserManager();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'upload_photo':
            // Upload della foto profilo
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['photo'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nessun file caricato']);
                break;
            }

            if (!Utils::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                break;
            }

            $upload = Utils::handlePhotoUpload($_FILES['photo'], $userId);
            if ($upload['success']) {
                if ($userManager->updateProfilePhoto($userId, $upload['path'])) {
                    echo json_encode(['success' => true, 'photo_url' => $upload['path']]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $upload['message']]);
            }
            break;

        case 'change_password':
            // Cambio password
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
                break;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!Utils::verifyCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                break;
            }

            $current = $input['current_password'] ?? '';
            $new = $input['new_password'] ?? '';
            $confirm = $input['confirm_password'] ?? '';

            if ($new !== $confirm) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Le password non coincidono']);
                break;
            }

            $errors = Utils::validatePassword($new);
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                break;
            }

            $result = $userManager->changePassword($userId, $current, $new);
            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => $result['message']]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Azione non valida']);
    }
} catch (Exception $e) {
    error_log('Errore API azioni: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
}
?>
