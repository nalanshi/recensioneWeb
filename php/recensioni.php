<?php
/**
 * Pagina delle recensioni
 * 
 * Questa pagina carica il template HTML delle recensioni e lo personalizza in base allo stato di login dell'utente.
 * 
 * @author DishDiveReview Team
 * @version 1.0
 */

// Inclusione del file di configurazione del database

require_once 'database.php';

// Avvio della sessione
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

// Caricamento di header e footer comuni
$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");

// Caricamento del template HTML
$DOM = file_get_contents("../static/recensioni.html");
// Correzione del percorso dei fogli di stile in base alla posizione dello script
$cssBase = dirname($_SERVER['PHP_SELF']) === '/' ? 'css/' : '../css/';
$DOM = str_replace("../css/style.css", $cssBase . 'style.css', $DOM);
$DOM = str_replace("../css/pages.css", $cssBase . 'pages.css', $DOM);
// Inserimento header e footer
$DOM = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $DOM);
$DOM = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $DOM);

// Personalizzazione del contenuto in base allo stato di login
$contenutoLogin = "";
$headerLoginHtml = "";

if (!SessionManager::isLoggedIn()) {
  // Utente non loggato
  $contenutoLogin = "";

  // Link di login per l'header
  $headerLoginHtml = "<a href='login.php' class='login-link' aria-label='Login - Accedi al tuo account'>
                        <div class='user-icon-bg'>
                          <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>
                            <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>
                            <circle cx='12' cy='7' r='4'></circle>
                          </svg>
                        </div>
                        <span class='login-text'>Login</span>
                      </a>";
} else {
  // Utente loggato
  $username = $_SESSION['username'];
  $nome = isset($_SESSION['user_data']['nome']) ? $_SESSION['user_data']['nome'] : $username;

  // Nessun contenuto per il placeholder di login
  $contenutoLogin = "";

  // Menu utente per l'header
  $profilePhoto = $_SESSION['user_data']['profile_photo'] ?? $_SESSION['profile_photo'] ?? '';
  $icon = $profilePhoto ? "<img src='../{$profilePhoto}' alt='Foto profilo di {$username}' class='user-avatar'>" : "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";

    $headerLoginHtml = "
    <details class='user-menu'>
      <summary class='login-link' aria-label='Menu utente'>
        <div class='user-icon-bg'>
          {$icon}
        </div>
        <span class='login-text'>{$username}</span>
      </summary>
      <div class='user-menu-panel'>
      <a href='dashboard.php'><span lang='en'>Dashboard</span></a>";
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $headerLoginHtml .= "
          <a href='gestione_recensioni.php'><span>Gestione recensioni</span></a>";
    }
    $headerLoginHtml .= "
          <a href='logout.php'><span lang='en'>Logout</span></a>
        </div>
    </details>";
  }

// Sostituzione dei placeholder nel template
$DOM = str_replace("<!--LOGIN_PLACEHOLDER-->", $contenutoLogin, $DOM);
$DOM = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $DOM);

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$filters = [
    'rating' => isset($_GET['rating']) ? (int)$_GET['rating'] : 0,
    'search' => trim($_GET['search'] ?? ''),
    'sort'   => $_GET['sort'] ?? 'recent'
];

$reviewManager = new ReviewManager();
$commentManager = new CommentManager();
$result = $reviewManager->getFilteredReviews($page, 10, $filters);

$reviewsHtml = '';
if ($result !== false && !empty($result['reviews'])) {
    foreach ($result['reviews'] as $review) {
        $rating = $review['rating'] ?? $commentManager->getAverageRatingForReview($review['id']);
        $stars = Utils::generateStars($rating);
        $date = Utils::formatDate($review['created_at']);
        $altProduct = htmlspecialchars($review['product_name']);
        $img = $review['product_image'] ? "<img src='../{$review['product_image']}' alt='{$altProduct}' class='review-image'>" : '';
        $title = htmlspecialchars($review['title']);
        $user = htmlspecialchars($review['username']);
        $excerpt = strlen($review['content']) > 150 ? substr($review['content'],0,150).'...' : $review['content'];
        $reviewsHtml .= "<a href='recensione.php?id={$review['id']}' class='review-card-main'>".
                        "<div class='review-content'>".
                        "<div class='review-header'><h3 class='review-title'>{$title}</h3>".
                        "<div class='review-rating' aria-label='Valutazione {$rating} su 5'>{$stars}</div></div>".
                        "$img".
                        "<div class='review-meta'><span class='review-author'>{$user}</span><span>•</span><span class='review-date'>{$date}</span></div>".
                        "<p class='review-excerpt'>".htmlspecialchars($excerpt)."</p>".
                        "</div></a>";
    }
}
$DOM = str_replace('<!--REVIEWS_PLACEHOLDER-->', $reviewsHtml, $DOM);

$paginationHtml = '';
if ($result !== false && $result['total_pages'] > 1) {
    $total = $result['total_pages'];
    $current = $result['page'];
    $baseParams = ['rating'=>$filters['rating'],'search'=>$filters['search'],'sort'=>$filters['sort']];
    if ($current > 1) {
        $params = array_merge($baseParams,['page'=>$current-1]);
        $paginationHtml .= '<a class="pagination-btn" href="?'.http_build_query($params).'">&laquo;</a>';
    }
    for ($i = 1; $i <= $total; $i++) {
        $params = array_merge($baseParams,['page'=>$i]);
        $class = $i==$current ? 'pagination-btn active' : 'pagination-btn';
        $paginationHtml .= '<a class="'.$class.'" href="?'.http_build_query($params).'">'.$i.'</a>';
    }
    if ($current < $total) {
        $params = array_merge($baseParams,['page'=>$current+1]);
        $paginationHtml .= '<a class="pagination-btn" href="?'.http_build_query($params).'">&raquo;</a>';
    }
}
$DOM = str_replace('<!--PAGINATION_PLACEHOLDER-->', $paginationHtml, $DOM);

if ($result !== false && empty($result['reviews'])) {
    // keep no reviews message
} else {
    $DOM = preg_replace('/<div class="no-reviews".*?\/div>\s*/s','',$DOM,1);
}

// Preimposta valori dei filtri
if ($filters['rating']) {
    $DOM = str_replace("value=\"{$filters['rating']}\"", "value=\"{$filters['rating']}\" selected", $DOM);
}
$DOM = str_replace("name=\"search\" class=\"filter-input\"", "name=\"search\" class=\"filter-input\" value=\"".htmlspecialchars($filters['search'],ENT_QUOTES)."\"", $DOM);
if ($filters['sort'] === 'rating') {
    $DOM = str_replace('value="rating"', 'value="rating" selected', $DOM);
} else {
    $DOM = str_replace('value="recent"', 'value="recent" selected', $DOM);
}

// Output della pagina
echo $DOM;
?>

