<?php
/**
 * API per la gestione delle recensioni
 */

require_once 'database.php';

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
$reviewManager = new ReviewManager();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Ottieni recensioni dell'utente
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            
            $filters = [
                'search' => Utils::sanitizeInput($_GET['search'] ?? ''),
                'rating' => (int)($_GET['rating'] ?? 0),
                'date_filter' => Utils::sanitizeInput($_GET['date_filter'] ?? '')
            ];
            
            // Rimuovi filtri vuoti
            $filters = array_filter($filters);
            
            $result = $reviewManager->getUserReviews($userId, $page, $limit, $filters);
            
            if ($result !== false) {
                // Formatta le date e aggiungi stelle
                foreach ($result['reviews'] as &$review) {
                    $review['formatted_date'] = Utils::formatDate($review['created_at']);
                    $review['stars_html'] = Utils::generateStars($review['rating']);
                    $review['content_preview'] = strlen($review['content']) > 150 
                        ? substr($review['content'], 0, 150) . '...' 
                        : $review['content'];
                }
                
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle recensioni']);
            }
            break;
            
        case 'PUT':
            // Aggiorna recensione
            $input = json_decode(file_get_contents('php://input'), true);
            $reviewId = (int)($_GET['id'] ?? 0);
            
            if (!$reviewId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID recensione mancante']);
                break;
            }
            
            // Verifica CSRF token
            if (!Utils::verifyCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                break;
            }
            
            // Sanitizza i dati
            $data = [
                'title' => Utils::sanitizeInput($input['title'] ?? ''),
                'content' => Utils::sanitizeInput($input['content'] ?? ''),
                'rating' => (int)($input['rating'] ?? 0)
            ];
            
            // Valida i dati
            $errors = [];
            if (empty($data['title']) || strlen($data['title']) < 3) {
                $errors[] = "Il titolo deve contenere almeno 3 caratteri";
            }
            if (empty($data['content']) || strlen($data['content']) < 10) {
                $errors[] = "Il contenuto deve contenere almeno 10 caratteri";
            }
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                $errors[] = "La valutazione deve essere tra 1 e 5 stelle";
            }
            
            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                break;
            }
            
            // Aggiorna la recensione
            if ($reviewManager->updateReview($reviewId, $userId, $data)) {
                echo json_encode(['success' => true, 'message' => 'Recensione aggiornata con successo']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
            }
            break;
            
        case 'DELETE':
            // Elimina recensione
            $reviewId = (int)($_GET['id'] ?? 0);
            
            if (!$reviewId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID recensione mancante']);
                break;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Verifica CSRF token
            if (!Utils::verifyCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                break;
            }
            
            // Elimina la recensione
            if ($reviewManager->deleteReview($reviewId, $userId)) {
                echo json_encode(['success' => true, 'message' => 'Recensione eliminata con successo']);
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
    error_log("Errore API recensioni: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
}
?>

