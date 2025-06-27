<?php
/**
 * Pagina delle classifiche
 * 
 * Questa pagina carica il template HTML delle classifiche e lo personalizza in base allo stato di login dell'utente.
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
$DOM = file_get_contents("../static/classifiche.html");
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
  $headerLoginHtml = "<a href='login_form.php' class='login-link' aria-label='Accedi al tuo account'>
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
    <div class='login-link user-menu' role='button' tabindex='0' aria-haspopup='true' aria-expanded='false' aria-label='Menu utente'>
    <div class='user-icon-bg'>
      {$icon}
    </div>
    <div class='login-text'>{$username}</div>
    <div class='user-dropdown'>
      <a href='dashboard.php'><span lang='en'>Dashboard</span></a>";
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $headerLoginHtml .= "
          <a href='gestione_recensioni.php'>Gestione recensioni</a>";
    }
    $headerLoginHtml .= "
          <a href='logout.php'><span lang='en'>Logout</span></a>
        </div>
    </div>";
  }
// Sostituzione dei placeholder nel template
$DOM = str_replace("<!--LOGIN_PLACEHOLDER-->", $contenutoLogin, $DOM);
$DOM = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $DOM);

// Recupera i prodotti migliori calcolando la media delle recensioni
$reviewManager = new ReviewManager();
$topReviews = $reviewManager->getTopProducts(10);

$podiumHtml = '';
$rowsHtml = '';
$position = 1;
foreach ($topReviews as $review) {
    $stars = Utils::generateStars(round($review['avg_rating']));
    $ratingNum = number_format($review['avg_rating'], 1);
    $title = htmlspecialchars($review['product_name']);
    $img = $review['product_image'] ? "<img src='../{$review['product_image']}' alt='{$title}' class='podium-image'>" : '';
    $rowImg = $review['product_image'] ? "<img src='../{$review['product_image']}' alt='{$title}' class='product-image'>" : '';
    $rankClass = '';
    if ($position == 1) { $rankClass = 'gold'; }
    elseif ($position == 2) { $rankClass = 'silver'; }
    elseif ($position == 3) { $rankClass = 'bronze'; }

    if ($position <= 3) {
        $podiumHtml .= "<div class='podium-item " . ($position == 1 ? 'first' : ($position == 2 ? 'second' : 'third')) . "' tabindex='0' role='link' data-review-id='{$review['review_id']}' aria-label='Dettagli {$title}'>".
                        "<div class='podium-rank {$rankClass}'>{$position}°</div>".
                        $img.
                        "<div class='podium-title'>{$title}</div>".
                        "<div class='podium-rating' aria-label='Valutazione {$ratingNum} su 5'>{$stars}</div>".
                        "</div>";
    }

    $rowsHtml .= "<tr class='ranking-row' tabindex='0' role='link' data-review-id='{$review['review_id']}' aria-label='Dettagli {$title}'>".
                 "<td class='rank-position {$rankClass}'>{$position}</td>".
                 "<td class='product-info'>{$rowImg}<div class='product-details'><h4>{$title}</h4></div></td>".
                 "<td class='rating-display'><div class='rating-stars'>{$stars}</div><span class='rating-number'>{$ratingNum}</span></td>".
                 "</tr>";

    $position++;
}

$DOM = str_replace('<!--PODIUM_PLACEHOLDER-->', $podiumHtml, $DOM);
$DOM = str_replace('<!--RANKING_ROWS_PLACEHOLDER-->', $rowsHtml, $DOM);

// Output della pagina
echo $DOM;
?>

