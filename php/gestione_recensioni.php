<?php
/**
 * Pagina di gestione delle recensioni (solo admin)
 *
 * Consente di pubblicare ed eliminare recensioni
 */

require_once 'database.php';

SessionManager::start();
SessionManager::requireLogin();

// Sincronizza i dati dell'utente con il database per ottenere l'ultima foto profilo
if (SessionManager::isLoggedIn()) {
    $userManager = new UserManager();
    $user = $userManager->getUserById(SessionManager::getUserId());
    if ($user) {
        $_SESSION['user_data']['profile_photo'] = $user['profile_photo'];
        $_SESSION['profile_photo'] = $user['profile_photo'];
    }
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Accesso negato';
    exit();
}

$reviewManager = new ReviewManager();
$commentManager = new CommentManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $reviewManager->deleteReview((int)$_POST['delete_id']);
        header('Location: gestione_recensioni.php');
        exit();
    } else {
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'product_name' => trim($_POST['product'] ?? ''),
            'content' => trim($_POST['content'] ?? ''),
            'product_image' => ''
        ];
        if (!empty($_FILES['image']['name'])) {
            $upload = Utils::handlePhotoUpload($_FILES['image'], SessionManager::getUserId());
            if ($upload['success']) {
                $data['product_image'] = $upload['path'];
            }
        }
        $reviewManager->createReview(SessionManager::getUserId(), $data);
        header('Location: gestione_recensioni.php');
        exit();
    }
}

$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");
$DOM = file_get_contents("../static/gestione_recensioni.html");

$DOM = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $DOM);
$DOM = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $DOM);

$username = $_SESSION['username'];

$profilePhoto = $_SESSION['user_data']['profile_photo'] ?? $_SESSION['profile_photo'] ?? '';
$icon = $profilePhoto ? "<img src='../{$profilePhoto}' alt='Foto profilo di {$username}' class='user-avatar'>" : "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";
$headerLoginHtml = " <div class='login-link user-menu' role='button' tabindex='0' aria-haspopup='true' aria-expanded='false' aria-label='Menu utente'>
                        <div class='user-icon-bg'>
                          {$icon}
                        </div>
                        <span class='login-text'>{$username}</span>
                        <div class='user-menu-panel' aria-hidden='true'>
                          <a href='dashboard.php'><span lang='en'>Dashboard</span></a>
                          <a href='gestione_recensioni.php'><span>Gestione recensioni</span></a>
                          <a href='logout.php'><span lang='en'>Logout</span></a>
                        </div>
                      </div>";
$DOM = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $DOM);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = trim($_GET['search'] ?? '');
$result = $reviewManager->getFilteredReviews($page, 10, ['search' => $search]);

$reviewsHtml = '';
if ($result !== false && !empty($result['reviews'])) {
    foreach ($result['reviews'] as $r) {
        $avg = $commentManager->getAverageRatingForReview($r['id']);
        $stars = Utils::generateStars($avg);
        $date = Utils::formatDate($r['created_at']);
        $title = htmlspecialchars($r['title']);
        $img = $r['product_image'] ? "<img src='../{$r['product_image']}' alt='".htmlspecialchars($r['product_name'])."' class='review-image'>" : '';
        $reviewsHtml .= "<div class='review-card'><div class='review-content'>".
                         "<div class='review-header'><h3 class='review-title'>{$title}</h3>".
                         "<div class='review-rating' aria-label='Valutazione {$avg} su 5'>{$stars}</div></div>".
                         "$img".
                         "<div class='review-meta'><span class='review-date'>{$date}</span></div>".
                         "<div class='review-actions'><a href='recensione.php?id={$r['id']}' class='view-btn'>Dettagli</a>".
                         "<form method='post' style='display:inline'><input type='hidden' name='delete_id' value='{$r['id']}'><button type='submit' class='delete-btn'>Elimina</button></form></div>".
                         "</div></div>";
    }
}
$DOM = str_replace('<!--REVIEWS_PLACEHOLDER-->', $reviewsHtml, $DOM);

$pagination = '';
if ($result !== false && $result['total_pages'] > 1) {
    $total = $result['total_pages'];
    $current = $result['page'];
    if ($current > 1) {
        $pagination .= '<a class="pagination-btn" href="?page='.($current-1).'&search='.urlencode($search).'">&laquo;</a>';
    }
    for ($i = 1; $i <= $total; $i++) {
        $class = $i==$current ? 'pagination-btn active' : 'pagination-btn';
        $pagination .= '<a class="'.$class.'" href="?page='.$i.'&search='.urlencode($search).'">'.$i.'</a>';
    }
    if ($current < $total) {
        $pagination .= '<a class="pagination-btn" href="?page='.($current+1).'&search='.urlencode($search).'">&raquo;</a>';
    }
}
$DOM = str_replace('<!--PAGINATION_PLACEHOLDER-->', $pagination, $DOM);

if ($search !== '') {
    $DOM = str_replace('name="search" placeholder="Cerca recensioni..."', 'name="search" placeholder="Cerca recensioni..." value="'.htmlspecialchars($search,ENT_QUOTES).'"', $DOM);
}

echo $DOM;
?>
