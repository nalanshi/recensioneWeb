<?php
/**
 * Pagina di dettaglio di una recensione
 */
require_once 'database.php';

SessionManager::start();

// Sincronizza i dati dell'utente con il database per ottenere l'ultima foto profilo
if (SessionManager::isLoggedIn()) {
    $userManager = new UserManager();
    $user = $userManager->getUserById(SessionManager::getUserId());
    if ($user) {
        $_SESSION['user_data']['profile_photo'] = $user['profile_photo'];
        $_SESSION['profile_photo'] = $user['profile_photo'];
    }
}

$reviewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$reviewId) {
    http_response_code(404);
    echo 'Recensione non trovata';
    exit();
}

$reviewManager = new ReviewManager();
$commentManager = new CommentManager();
$userComment = null;
if (SessionManager::isLoggedIn()) {
    $username = $_SESSION['user_data']['username'] ?? '';
    $email = $_SESSION['user_data']['email'] ?? '';
    if ($username) {
        $userComment = $commentManager->getUserCommentForReview($reviewId, $username);
    }
}
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
    $headerLoginHtml = "<a href='login.php' class='login-link' aria-label='Login - Accedi al tuo account'>".
                       "<div class='user-icon-bg'>".
                       "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>".
                       "<path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>".
                       "<circle cx='12' cy='7' r='4'></circle>".
                       "</svg></div><span class='login-text'>Login</span></a>";
} else {
    $username = $_SESSION['username'];
    $profilePhoto = $_SESSION['user_data']['profile_photo'] ?? $_SESSION['profile_photo'] ?? '';
    $icon = $profilePhoto ? "<img src='../{$profilePhoto}' alt='Foto profilo di {$username}' class='user-avatar'>" : "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";
    $headerLoginHtml = "<div class='login-link user-menu' aria-label='Menu utente'><div class='user-icon-bg'>{$icon}</div><span class='login-text'>{$username}</span><div class='user-menu-panel' aria-hidden='true'><a href='dashboard.php'><span lang='en'>Dashboard</span></a>";
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $headerLoginHtml .= "<a href='gestione_recensioni.php'><span>Gestione recensioni</span></a>";
    }
    $headerLoginHtml .= "<a href='logout.php'><span lang='en'>Logout</span></a></div></div>";
}

$template = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $template);
$template = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $template);
$template = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $template);
$template = str_replace("<!--TITLE_PLACEHOLDER-->", htmlspecialchars($review['title']), $template);
$template = str_replace("<!--AUTHOR_PLACEHOLDER-->", htmlspecialchars($review['username']), $template);
$template = str_replace("<!--EMAIL_PLACEHOLDER-->", htmlspecialchars($review['email']), $template);
$authorPhoto = $review['profile_photo'] ? '../' . $review['profile_photo'] : '../images/icon/user.png';
$template = str_replace("<!--AUTHOR_PHOTO-->", $authorPhoto, $template);
$altProduct = htmlspecialchars($review['product_name']);
$template = str_replace('alt="Immagine di <!--PRODUCT_NAME-->"', 'alt="Immagine di ' . $altProduct . '"', $template);
$template = str_replace("<!--PRODUCT_NAME-->", htmlspecialchars($review['product_name']), $template);
$ratingHtml = "<div class='review-rating' aria-label='Valutazione {$review['rating']} su 5'>" . Utils::generateStars($review['rating']) . "</div>";
$template = str_replace("<!--RATING_HTML-->", $ratingHtml, $template);
$date = Utils::formatDate($review['created_at']);
$template = str_replace("<!--DATE_PLACEHOLDER-->", $date, $template);
$imageSrc = $review['product_image'] ? '../' . $review['product_image'] : '';
$template = str_replace("<!--IMAGE_SRC-->", $imageSrc, $template);
$template = str_replace("<!--CONTENT_PLACEHOLDER-->", nl2br(htmlspecialchars($review['content'])), $template);
$template = str_replace("<!--ID_PLACEHOLDER-->", $reviewId, $template);

$opinioneTitle = $userComment ? 'Questo è il tuo opinione' : 'Lascia un tuo opinione';
$template = str_replace('Lascia un tuo opinione', $opinioneTitle, $template);

$star = $userComment['star'] ?? 1;
$options = '';
for ($i = 5; $i >= 1; $i--) {
    $selected = $star == $i ? ' selected' : '';
    $options .= "<option value='{$i}'{$selected}>{$i}</option>";
}
$template = str_replace('<!--STAR_OPTIONS-->', $options, $template);

$commentContent = $userComment ? htmlspecialchars($userComment['content']) : '';
$template = str_replace('<!--COMMENT_CONTENT-->', $commentContent, $template);
$buttonText = $userComment ? 'Aggiorna' : 'Invia';
$template = str_replace('>Invia<', ">{$buttonText}<", $template);

if ($userComment) {
    $commentDate = Utils::formatDate($userComment['created_at']);
    $starsHtml = Utils::generateStars($userComment['star']);
    $commentInfo = "<div class='comment'><div class='comment-author'>{$_SESSION['username']}</div><div class='comment-email'>{$email}</div><div class='comment-rating' aria-label='Valutazione {$userComment['star']} su 5'>{$starsHtml}</div><p class='comment-content'>" . htmlspecialchars($userComment['content']) . "</p><div class='comment-date'>{$commentDate}</div></div>";
    $template = str_replace('<div id="user-comment-info"></div>', $commentInfo, $template);
}

$commentsHtml = '';
foreach ($comments as $c) {
    $date = Utils::formatDate($c['created_at']);
    $stars = Utils::generateStars($c['star']);
    $commentsHtml .= "<div class='comment'><div class='comment-author'>".htmlspecialchars($c['username'])."</div>".
                     "<div class='comment-email'>".htmlspecialchars($c['email'])."</div>".
                     "<div class='comment-rating' aria-label='Valutazione {$c['star']} su 5'>{$stars}</div>".
                     "<p class='comment-content'>".htmlspecialchars($c['content'])."</p>".
                     "<div class='comment-date'>{$date}</div></div>";
}
$template = str_replace("<!--COMMENTS_PLACEHOLDER-->", $commentsHtml, $template);

if (!SessionManager::isLoggedIn()) {
    $loginMsg = "<p class='login-notice'>Clicca in alto a destra per accedere e iniziare a dare un tuo opinione.</p>";
    $template = preg_replace('/<form id="comment-form".*?<\/form>/s', $loginMsg, $template);
    $template = str_replace('<div id="user-comment-info"></div>', '', $template);
}

echo $template;
