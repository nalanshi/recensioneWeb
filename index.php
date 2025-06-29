<?php
/**
 * Pagina principale dell'applicazione
 * 
 * Questa pagina carica il template HTML e lo personalizza in base allo stato di login dell'utente.
 * 
 * @author DishDiveReview Team
 * @version 1.1
 */

// Inclusione del file di configurazione del database
require_once 'php/database.php';

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
$header = file_get_contents("static/header.html");
// Corregge i percorsi nel menù quando l'header è incluso dalla root
if (dirname($_SERVER['PHP_SELF']) === '/') {
    $header = str_replace("../index.php", "index.php", $header);
    $header = str_replace("../php/", "php/", $header);
    $header = str_replace("../images/", "images/", $header);
}
$footer = file_get_contents("static/footer.html");

// Caricamento del template HTML
$DOM = file_get_contents("static/index.html");
// Correzione del percorso del foglio di stile per la versione PHP della pagina
$DOM = str_replace("../css/style.css", "css/style.css", $DOM);
$DOM = str_replace("../css/pages.css", "css/pages.css", $DOM);
// Inserimento header e footer
// Il footer viene inserito subito, l'header dopo la personalizzazione
$DOM = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $DOM);

// Personalizzazione del contenuto in base allo stato di login
$contenutoLogin = "";
$headerLoginHtml = "";

if (!SessionManager::isLoggedIn()) {
  // Utente non loggato
  $contenutoLogin = "";

  // Link di login per l'header
  $headerLoginHtml = "<a href='php/login.php' class='login-link' aria-label='Login - Accedi al tuo account'>
                        <div class='user-icon-bg'>
                          <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>
                            <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>
                            <circle cx='12' cy='7' r='4'></circle>
                          </svg>
                        </div>
                        <div class='login-text'>Login</div>
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
        <div class='login-text'>{$username}</div>
      </summary>
      <div class='user-menu-panel'>
      <a href='php/dashboard.php'><span lang='en'>Dashboard</span></a>";
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $headerLoginHtml .= "
          <a href='php/gestione_recensioni.php'><span>Gestione recensioni</span></a>";
    }
    $headerLoginHtml .= "
          <a href='php/logout.php'><span lang='en'>Logout</span></a>
        </div>
    </details>";
  }

// Sostituzione dei placeholder nel template
$DOM = str_replace("<!--LOGIN_PLACEHOLDER-->", $contenutoLogin, $DOM);
$header = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $header);
$header = Utils::markCurrentNavLink($header, basename($_SERVER['PHP_SELF']));
$DOM = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $DOM);

// Recupero ultime 10 recensioni
$reviewManager = new ReviewManager();
$commentManager = new CommentManager();
$result = $reviewManager->getAllReviews(1, 10);
$reviewsHtml = '';
if ($result !== false) {
    foreach ($result['reviews'] as $review) {
        $avgRating = $commentManager->getAverageRatingForReview($review['id']);
        $stars = Utils::generateStars($avgRating);
        $excerpt = strlen($review['content']) > 150 ? substr($review['content'], 0, 150) . '...' : $review['content'];
        $date = Utils::formatDate($review['created_at']);
        $altProduct = htmlspecialchars($review['product_name']);
        $img = $review['product_image'] ? "<img src='../{$review['product_image']}' alt='{$altProduct}' class='review-image'>" : '';
        $title = htmlspecialchars($review['title']);
        $user = htmlspecialchars($review['username']);
        $reviewsHtml .= "<a href='php/recensione.php?id={$review['id']}' class='review-card-main' title='Clicca per visualizzare la recensione del prodotto'>" .
                         $img .
                         "<div class='review-content'>" .
                         "<div class='review-header'><h3 class='review-title'>{$title}</h3>" .
                         "<div class='review-rating' aria-label='Valutazione {$avgRating} su 5'>{$stars}</div></div>" .
                         "<div class='review-meta'><span class='review-author'>{$user}</span><span>•</span><span class='review-date'>{$date}</span></div>" .
                         "<p class='review-excerpt'>" . htmlspecialchars($excerpt) . "</p>" .
                         "</div></a>";
    }
}
$DOM = str_replace('<!--REVIEWS_PLACEHOLDER-->', $reviewsHtml, $DOM);

// Output della pagina
echo $DOM;
?>
