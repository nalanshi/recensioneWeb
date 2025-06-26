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

// Avvio della sessione
SessionManager::start();

// Caricamento di header e footer comuni
$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");
$homeLink = "<a href='../index.php' class='login-link' aria-label='Torna alla homepage' aria-current='page'>\n  <div class='user-icon-bg'>\n    <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>\n      <path d='M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'></path>\n      <polyline points='9,22 9,12 15,12 15,22'></polyline>\n    </svg>\n  </div>\n  <div class='login-text'>Home</div>\n</a>";
$header = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $homeLink, $header);

// Recupera eventuali messaggi di errore dalla sessione
$errorMessage = '';
if (isset($_SESSION['login_error'])) {
    $errorMessage = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Rimuovi il messaggio dopo averlo letto
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accedi - DishDiveReview</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/auth-styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
  <script src="../js/auth-validation.js" defer></script>
</head>
<body>
  <a href="#main-content" class="skip-link">Salta al contenuto principale</a>

  <?php echo $header; ?>

  <div class="auth-page">
    <div id="main-content" class="auth-container main-content">
      <div class="auth-header">
        <div class="auth-logo">
          DR
        </div>
        <h1 class="auth-title">Accedi al tuo account</h1>
        <p class="auth-subtitle">Benvenuto di nuovo! Inserisci le tue credenziali per continuare.</p>
      </div>

      <!-- Messaggio di errore dinamico -->
      <div id="error-container" aria-live="polite">
        <?php if (!empty($errorMessage)): ?>
          <div class="message error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <?php echo htmlspecialchars($errorMessage); ?>
          </div>
        <?php endif; ?>
      </div>

      <form id="login-form" class="auth-form" action="login.php" method="post" onsubmit="return validateLoginForm()">
        <div class="form-group">
          <label for="username" class="form-label">Username o Email</label>
          <div class="form-input-wrapper">
            <input 
              type="text" 
              id="username" 
              name="username" 
              class="form-input" 
              placeholder="Inserisci username o email"
              autocomplete="username" 
              required 
              aria-required="true"
            >
            <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </div>
          <div id="username-error" class="error-message" aria-live="polite"></div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <div class="form-input-wrapper">
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-input" 
              placeholder="Inserisci la tua password"
              autocomplete="current-password" 
              required 
              aria-required="true"
            >
            <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <circle cx="12" cy="16" r="1"></circle>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <div id="password-error" class="error-message" aria-live="polite"></div>
        </div>

        <div class="checkbox-wrapper">
          <input type="checkbox" id="remember" name="remember" class="checkbox-input">
          <label for="remember" class="checkbox-label">Ricordami per 30 giorni</label>
        </div>

        <button type="submit" class="btn btn-primary">
          Accedi
        </button>
      </form>

      <div class="auth-links">
        <a href="#" class="auth-link">Password dimenticata?</a>
        <a href="#" class="auth-link">Accedi via SMS</a>
      </div>

      <div class="auth-footer">
        <p class="auth-footer-text">
          Non hai un account? 
          <a href="../static/registrazione.html" class="auth-footer-link">Registrati gratuitamente</a>
        </p>
      </div>
    </div>
  </div>
    <?php echo $footer; ?>
</body>
</html>
