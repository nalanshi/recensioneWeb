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

// Caricamento di header e footer comuni
$header = file_get_contents("static/header.html");
$footer = file_get_contents("static/footer.html");

// Caricamento del template HTML
$DOM = file_get_contents("static/index.html");
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
  $headerLoginHtml = "<a href='php/login_form.php' class='login-link' aria-label='Accedi al tuo account'>
                        <div class='user-icon-bg'>
                          <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='userIcon'>
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
  $headerLoginHtml = "<div class='login-link user-menu' aria-label='Menu utente'>
                        <div class='user-icon-bg'>
                          <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='userIcon'>
                            <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>
                            <circle cx='12' cy='7' r='4'></circle>
                          </svg>
                        </div>
                        <div class='login-text'>{$username}</div>
                        <div class='user-dropdown'>
                          <a href='php/dashboard.php'>Dashboard</a>";
  if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
      $headerLoginHtml .= "\n                          <a href='php/gestione_recensioni.php'>Gestione recensioni</a>";
  }
  $headerLoginHtml .= "\n                          <a href='php/logout.php'>Logout</a>
                        </div>
                      </div>";
}

// Sostituzione dei placeholder nel template
$DOM = str_replace("<!--LOGIN_PLACEHOLDER-->", $contenutoLogin, $DOM);
$DOM = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $DOM);

// Output della pagina
echo $DOM;
?>
