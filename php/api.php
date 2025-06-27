<?php
/**
 * API unificata per DishDiveReview.
 * Gestisce profilo, recensioni, impostazioni e azioni varie.
 */
require_once 'database.php';

SessionManager::start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$endpoint = $_GET['endpoint'] ?? '';

// Endpoint pubblico per i commenti
if ($endpoint === 'comments') {
    $commentManager = new CommentManager();
    handle_comments($commentManager);
    exit;
}

if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

$userId = SessionManager::getUserId();
$userManager = new UserManager();
$reviewManager = new ReviewManager();
$settingsManager = new SettingsManager();

switch ($endpoint) {
    case 'actions':
        handle_actions($userId, $userManager);
        break;
    case 'profile':
        handle_profile($userId, $userManager);
        break;
    case 'reviews':
        handle_reviews($userId, $reviewManager);
        break;
    case 'delete':
        handle_delete($userId, $reviewManager);
        break;
    case 'settings':
        handle_settings($userId, $settingsManager);
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint non trovato']);
}

function handle_actions($userId, $userManager) {
    $action = $_GET['action'] ?? '';
    try {
        switch ($action) {
            case 'upload_photo':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['photo'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Nessun file caricato']);
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
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    http_response_code(405);
                    echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
                    break;
                }

                $input = json_decode(file_get_contents('php://input'), true);

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
}

function handle_profile($userId, $userManager) {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $userData = $userManager->getUserById($userId);
                if ($userData) {
                    unset($userData['password_hash']);
                    echo json_encode(['success' => true, 'data' => $userData]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
                }
                break;
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
                $input['first_name'] = $input['first_name'] ?? ($input['firstName'] ?? '');
                $input['last_name']  = $input['last_name']  ?? ($input['lastName'] ?? '');
                $data = [
                    'first_name' => Utils::sanitizeInput($input['first_name'] ?? ''),
                    'last_name' => Utils::sanitizeInput($input['last_name'] ?? ''),
                    'email' => Utils::sanitizeInput($input['email'] ?? ''),
                    'birth_day' => (int)($input['birth_day'] ?? 0),
                    'birth_month' => (int)($input['birth_month'] ?? 0),
                    'birth_year' => (int)($input['birth_year'] ?? 0)
                ];
                $errors = Utils::validateProfileData($data);
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                    break;
                }
                if ($userManager->updateProfile($userId, $data)) {
                    echo json_encode(['success' => true, 'message' => 'Profilo aggiornato con successo']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => "Errore durante l'aggiornamento"]);
                }
                break;
            case 'DELETE':
                $input = json_decode(file_get_contents('php://input'), true);
                if (($input['confirmation'] ?? '') !== 'ELIMINA') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Conferma non valida']);
                    break;
                }
                if ($userManager->deleteAccount($userId)) {
                    SessionManager::logout();
                    echo json_encode(['success' => true, 'message' => 'Account eliminato con successo']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => "Errore durante l'eliminazione"]);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
        }
    } catch (Exception $e) {
        error_log('Errore API profilo: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
    }
}

function handle_reviews($userId, $reviewManager) {
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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
                $filters = array_filter($filters);
                if ($isAdmin && isset($_GET['all'])) {
                    $result = $reviewManager->getAllReviews($page, $limit);
                } else {
                    $result = $reviewManager->getUserReviews($userId, $page, $limit, $filters);
                }
                if ($result !== false) {
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
                $input = $_POST;
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
                $data = [
                    'title' => Utils::sanitizeInput($input['title'] ?? ''),
                    'content' => Utils::sanitizeInput($input['content'] ?? ''),
                    'rating' => (int)($input['rating'] ?? 0),
                    'product_name' => Utils::sanitizeInput($input['product_name'] ?? ''),
                    'product_image' => $productImagePath
                ];
                $errors = [];
                if (empty($data['title']) || strlen($data['title']) < 3) {
                    $errors[] = 'Il titolo deve contenere almeno 3 caratteri';
                }
                if (empty($data['content']) || strlen($data['content']) < 10) {
                    $errors[] = 'Il contenuto deve contenere almeno 10 caratteri';
                }
                if ($data['rating'] < 1 || $data['rating'] > 5) {
                    $errors[] = 'La valutazione deve essere tra 1 e 5 stelle';
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
                $input = $_POST;
                $reviewId = (int)($_GET['id'] ?? 0);
                if (!$reviewId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID recensione mancante']);
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
                $data = [
                    'title' => Utils::sanitizeInput($input['title'] ?? ''),
                    'content' => Utils::sanitizeInput($input['content'] ?? ''),
                    'rating' => (int)($input['rating'] ?? 0),
                    'product_name' => Utils::sanitizeInput($input['product_name'] ?? ''),
                    'product_image' => $productImagePath
                ];
                $errors = [];
                if (empty($data['title']) || strlen($data['title']) < 3) {
                    $errors[] = 'Il titolo deve contenere almeno 3 caratteri';
                }
                if (empty($data['content']) || strlen($data['content']) < 10) {
                    $errors[] = 'Il contenuto deve contenere almeno 10 caratteri';
                }
                if ($data['rating'] < 1 || $data['rating'] > 5) {
                    $errors[] = 'La valutazione deve essere tra 1 e 5 stelle';
                }
                if (!empty($errors)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
                    break;
                }
                $updated = $reviewManager->updateReview($reviewId, $data, $isAdmin ? null : $userId);
                if ($updated) {
                    echo json_encode(['success' => true, 'message' => 'Recensione aggiornata con successo']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => "Errore durante l'aggiornamento"]);
                }
                break;
            case 'DELETE':
                $reviewId = (int)($_GET['id'] ?? 0);
                if (!$reviewId) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID recensione mancante']);
                    break;
                }
                $input = json_decode(file_get_contents('php://input'), true);
                $deleted = $reviewManager->deleteReview($reviewId, $isAdmin ? null : $userId);
                if ($deleted) {
                    echo json_encode(['success' => true, 'message' => 'Recensione eliminata con successo']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Recensione non trovata']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
        }
    } catch (Exception $e) {
        error_log('Errore API recensioni: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
    }
}

function handle_delete($userId, $reviewManager) {
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
        return;
    }
    $reviewId = (int)($_GET['id'] ?? 0);
    if (!$reviewId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID recensione mancante']);
        return;
    }
    $deleted = $reviewManager->deleteReview($reviewId, $isAdmin ? null : $userId);
    if ($deleted) {
        echo json_encode(['success' => true, 'message' => 'Recensione eliminata con successo']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Recensione non trovata']);
    }
}

function handle_comments($commentManager) {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                $reviewId = (int)($_POST['review_id'] ?? 0);
                $name = Utils::sanitizeInput($_POST['name'] ?? '');
                $email = Utils::sanitizeInput($_POST['email'] ?? '');
                $content = Utils::sanitizeInput($_POST['content'] ?? '');
                if ($reviewId && $name && $email && $content) {
                    if ($commentManager->createComment($reviewId, $name, $email, $content)) {
                        echo json_encode(['success' => true]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
                }
                break;
            case 'GET':
                $reviewId = (int)($_GET['review_id'] ?? 0);
                if ($reviewId) {
                    $comments = $commentManager->getComments($reviewId);
                    echo json_encode(['success' => true, 'data' => $comments]);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'ID recensione mancante']);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
        }
    } catch (Exception $e) {
        error_log('Errore API commenti: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
    }
}

function handle_settings($userId, $settingsManager) {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $settings = $settingsManager->getUserSettings($userId);
                if ($settings !== false) {
                    echo json_encode(['success' => true, 'data' => $settings]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Errore nel recupero delle impostazioni']);
                }
                break;
            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true);
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
                    echo json_encode([
                        'success' => false,
                        'message' => 'Nessuna impostazione valida fornita',
                        'received' => $input
                    ]);
                    break;
                }
                if ($settingsManager->updateSettings($userId, $settings)) {
                    echo json_encode(['success' => true, 'message' => 'Impostazioni aggiornate con successo']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => "Errore durante l'aggiornamento"]);
                }
                break;
            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Metodo non supportato']);
        }
    } catch (Exception $e) {
        error_log('Errore API impostazioni: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore interno del server']);
    }
}
