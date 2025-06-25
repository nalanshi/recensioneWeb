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
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$reviewManager = new ReviewManager();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

try {
    switch ($method) {
        case 'GET':
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            
            $filters = [
                'search' => Utils::sanitizeInput($_GET['search'] ?? ''),
                'rating' => (int)($_GET['rating'] ?? 0),
                'date_filter' => Utils::sanitizeInput($_GET['date_filter'] ?? '')
            ];
            
            // Rimuovi filtri vuoti
            $filters = array_filter($filters);
            
            if ($isAdmin && isset($_GET['all'])) {
                $result = $reviewManager->getAllReviews($page, $limit);
            } else {
                $result = $reviewManager->getUserReviews($userId, $page, $limit, $filters);
            }
            
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

        case 'POST':
            // Crea nuova recensione
            $input = $_POST;

            // Verifica CSRF token
            if (!Utils::verifyCSRFToken($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
                break;
            }

            // Gestione upload immagine prodotto
            $productImagePath = $input['old_image'] ?? '';
            if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload = Utils::handleProductImageUpload($_FILES['product_image']);
                if ($upload['success']) {
                    $productImagePath = $upload['path'];
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $upload['message']]);
                    break;
                }
            }

            // Sanitizza i dati
            $data = [
                'title' => Utils::sanitizeInput($input['title'] ?? ''),
                'content' => Utils::sanitizeInput($input['content'] ?? ''),
                'rating' => (int)($input['rating'] ?? 0),
                'product_name' => Utils::sanitizeInput($input['product_name'] ?? ''),
                'product_image' => $productImagePath
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

            if ($reviewManager->createReview($userId, $data)) {
                echo json_encode(['success' => true, 'message' => 'Recensione creata con successo']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Errore durante la creazione']);
            }
            break;
            
        case 'PUT':
            // Aggiorna recensione
            $input = $_POST;
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
            
            $productImagePath = $input['old_image'] ?? '';
            if (!empty($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $upload = Utils::handleProductImageUpload($_FILES['product_image']);
                if ($upload['success']) {
                    $productImagePath = $upload['path'];
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => $upload['message']]);
                    break;
                }
            }

            // Sanitizza i dati
            $data = [
                'title' => Utils::sanitizeInput($input['title'] ?? ''),
                'content' => Utils::sanitizeInput($input['content'] ?? ''),
                'rating' => (int)($input['rating'] ?? 0),
                'product_name' => Utils::sanitizeInput($input['product_name'] ?? ''),
                'product_image' => $productImagePath
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
            $updated = $reviewManager->updateReview(
                $reviewId,
                $data,
                $isAdmin ? null : $userId
            );
            if ($updated) {
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
            $deleted = $reviewManager->deleteReview(
                $reviewId,
                $isAdmin ? null : $userId
            );
            if ($deleted) {
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

