<?php
/**
 * Pagina di dettaglio di una recensione
 */
require_once 'database.php';

SessionManager::start();

$reviewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$reviewId) {
    http_response_code(404);
    echo 'Recensione non trovata';
    exit();
}

$reviewManager = new ReviewManager();
$commentManager = new CommentManager();
$review = $reviewManager->getReviewById($reviewId);
if (!$review) {
    http_response_code(404);
    echo 'Recensione non trovata';
    exit();
}

$comments = $commentManager->getComments($reviewId, 5);

$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");
$template = file_get_contents("../static/recensione.html");

$headerLoginHtml = '';
if (!SessionManager::isLoggedIn()) {
    $headerLoginHtml = "<a href='login_form.php' class='login-link' aria-label='Accedi al tuo account'>".
                       "<div class='user-icon-bg'>".
                       "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>".
                       "<path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>".
                       "<circle cx='12' cy='7' r='4'></circle>".
                       "</svg></div><div class='login-text'>Login</div></a>";
} else {
    $username = $_SESSION['username'];
    $profilePhoto = $_SESSION['user_data']['profile_photo'] ?? $_SESSION['profile_photo'] ?? '';
    $icon = $profilePhoto ? "<img src='../{$profilePhoto}' alt='Foto profilo di {$username}' class='user-avatar'>" : "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";
    $headerLoginHtml = "<div class='login-link user-menu' aria-label='Menu utente'><div class='user-icon-bg'>{$icon}</div><div class='login-text'>{$username}</div><div class='user-dropdown'><a href='dashboard.php'>Dashboard</a>";
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $headerLoginHtml .= "<a href='gestione_recensioni.php'>Gestione recensioni</a>";
    }
    $headerLoginHtml .= "<a href='logout.php'>Logout</a></div></div>";
}

$template = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $template);
$template = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $template);
$template = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $template);
$template = str_replace("<!--TITLE_PLACEHOLDER-->", htmlspecialchars($review['title']), $template);
$template = str_replace("<!--AUTHOR_PLACEHOLDER-->", htmlspecialchars($review['username']), $template);
$template = str_replace("<!--EMAIL_PLACEHOLDER-->", htmlspecialchars($review['email']), $template);
$authorPhoto = $review['profile_photo'] ? '../' . $review['profile_photo'] : '../images/icon/user.png';
$template = str_replace("<!--AUTHOR_PHOTO-->", $authorPhoto, $template);
$template = str_replace("<!--PRODUCT_NAME-->", htmlspecialchars($review['product_name']), $template);
$ratingHtml = "<div class='review-rating' aria-label='Valutazione {$review['rating']} su 5'>" . Utils::generateStars($review['rating']) . "</div>";
$template = str_replace("<!--RATING_HTML-->", $ratingHtml, $template);
$date = Utils::formatDate($review['created_at']);
$template = str_replace("<!--DATE_PLACEHOLDER-->", $date, $template);
$imageSrc = $review['product_image'] ? '../' . $review['product_image'] : '';
$template = str_replace("<!--IMAGE_SRC-->", $imageSrc, $template);
$template = str_replace("<!--CONTENT_PLACEHOLDER-->", nl2br(htmlspecialchars($review['content'])), $template);
$template = str_replace("<!--ID_PLACEHOLDER-->", $reviewId, $template);

$commentsHtml = '';
foreach ($comments as $c) {
    $date = Utils::formatDate($c['created_at']);
    $commentsHtml .= "<div class='comment'><div class='comment-author'>".htmlspecialchars($c['name'])."</div>".
                     "<div class='comment-email'>".htmlspecialchars($c['email'])."</div>".
                     "<p class='comment-content'>".htmlspecialchars($c['content'])."</p>".
                     "<div class='comment-date'>{$date}</div></div>";
}
$template = str_replace("<!--COMMENTS_PLACEHOLDER-->", $commentsHtml, $template);

echo $template;
