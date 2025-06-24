<?php
/**
 * Pagina di login dell'applicazione
 * 
 * Questa pagina mostra il form di login e gestisce la visualizzazione
 * dei messaggi di errore in caso di credenziali non valide.
 * 
 * @author DishDiveReview Team
 * @version 1.0
 */

// Inclusione del file di configurazione del database
require_once 'database.php';
$DOM = file_get_contents("../static/login.html");
// Avvio della sessione
SessionManager::start();
// Recupera eventuali messaggi di errore dalla sessione
$errorMessage = '';
if (isset($_SESSION['login_error'])) {
    $errorMessage = $_SESSION['login_error'];
    $contenutoError = '<div class="message error">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
      '.$errorMessage.'</div>';
    unset($_SESSION['login_error']);
    $DOM = str_replace("<!-- Messaggio di errore dinamico -->", $contenutoError, $DOM);
}

echo $DOM;