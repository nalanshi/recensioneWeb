<?php
/**
 * Pagina di registrazione dell'applicazione
 *
 * Mostra il form di registrazione caricando header e footer
 * comuni e personalizzando il menu in base allo stato di login.
 */
require_once 'database.php';

SessionManager::start();

$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");

if (!SessionManager::isLoggedIn()) {
    $headerLoginHtml = "<a href='login_form.php' class='login-link' aria-label='Login - Accedi al tuo account'>\n" .
        "  <div class='user-icon-bg'>\n" .
        "    <svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'>\n" .
        "      <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>\n" .
        "      <circle cx='12' cy='7' r='4'></circle>\n" .
        "    </svg>\n" .
        "  </div>\n" .
        "  <span class='login-text'>Login</span>\n" .
        "</a>";
} else {
    $username = $_SESSION['username'];
    $profilePhoto = $_SESSION['user_data']['profile_photo'] ?? $_SESSION['profile_photo'] ?? '';
    $icon = $profilePhoto
        ? "<img src='../{$profilePhoto}' alt='Foto profilo di {$username}' class='user-avatar'>"
        : "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='user-icon'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";
    $headerLoginHtml = "<div class='login-link user-menu' role='button' tabindex='0' aria-haspopup='true' aria-expanded='false' aria-label='Menu utente'>\n" .
        "  <div class='user-icon-bg'>\n" .
        "    {$icon}\n" .
        "  </div>\n" .
        "  <span class='login-text'>{$username}</span>\n" .
        "  <div class='user-menu-panel' aria-hidden='true'>\n" .
        "    <a href='dashboard.php'><span lang='en'>Dashboard</span></a>";
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $headerLoginHtml .= "<a href='gestione_recensioni.php'><span>Gestione recensioni</span></a>";
    }
    $headerLoginHtml .= "<a href='logout.php'><span lang='en'>Logout</span></a>\n" .
        "  </div>\n" .
        "</div>";
}

$header = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $header);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrati - ReviewDiver!</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/auth-styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="../js/auth-validation.js" defer></script>
</head>
<body>
  <?php echo $header; ?>
  <div class="auth-page">
    <div id="main-content" class="auth-container main-content">
      <div class="auth-header">
        <div class="auth-logo">
          DR
        </div>
        <h1 class="auth-title">Crea un nuovo <span lang="en">account</span></h1>
        <p class="auth-subtitle">Unisciti alla nostra community di food lovers!</p>
      </div>

      <!-- Messaggio di errore dinamico -->
      <div id="error-container" aria-live="polite"></div>

      <form id="registration-form" class="auth-form" action="registrazione.php" method="post" onsubmit="return validateRegistrationForm()">

        <div class="form-group form-row">
          <div>
            <label for="nome" class="form-label">Nome</label>
            <div class="form-input-wrapper">
              <input
                type="text"
                id="nome"
                name="nome"
                class="form-input"
                placeholder="Il tuo nome"
                autocomplete="given-name"
                required
                aria-required="true"
              >
              <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div id="nome-error" class="error-message" aria-live="polite"></div>
          </div>

          <div>
            <label for="cognome" class="form-label">Cognome</label>
            <div class="form-input-wrapper">
              <input
                type="text"
                id="cognome"
                name="cognome"
                class="form-input"
                placeholder="Il tuo cognome"
                autocomplete="family-name"
                required
                aria-required="true"
              >
              <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <div id="cognome-error" class="error-message" aria-live="polite"></div>
          </div>
        </div>

        <div class="form-group">
          <label for="email" class="form-label"><span lang="en">Email</span></label>
          <div class="form-input-wrapper">
            <input
              type="email"
              id="email"
              name="email"
              class="form-input"
              placeholder="nome@esempio.com"
              autocomplete="email"
              required
              aria-required="true"
            >
            <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
          </div>
          <div id="email-error" class="error-message" aria-live="polite"></div>
        </div>

        <div class="form-group">
          <label for="username" class="form-label"><span lang="en">Username</span></label>
          <div class="form-input-wrapper">
            <input
              type="text"
              id="username"
              name="username"
              class="form-input"
              placeholder="Scegli un username unico"
              autocomplete="username"
              required
              aria-required="true"
            >
            <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="8.5" cy="7" r="4"></circle>
              <path d="M20 8v6"></path>
              <path d="M23 11h-6"></path>
            </svg>
          </div>
          <div id="username-error" class="error-message" aria-live="polite"></div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label"><span lang="en">Password</span></label>
          <div class="form-input-wrapper">
            <input
              type="password"
              id="password"
              name="password"
              class="form-input"
              placeholder="Crea una password sicura"
              autocomplete="new-password"
              required
              aria-required="true"
              aria-describedby="password-requirements"
              onkeyup="checkPasswordStrength()"
            >
            <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <circle cx="12" cy="16" r="1"></circle>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <div class="password-strength">
            <div id="password-strength-bar" class="password-strength-bar"></div>
          </div>
          <div id="password-strength-text" class="password-strength-text"></div>
          <div id="password-requirements" class="password-strength-text">
            Almeno 8 caratteri, una maiuscola, una minuscola e un numero
          </div>
          <div id="password-error" class="error-message" aria-live="polite"></div>
        </div>

        <div class="form-group">
          <label for="confirm-password" class="form-label">Conferma <span lang="en">Password</span></label>
          <div class="form-input-wrapper">
            <input
              type="password"
              id="confirm-password"
              name="confirm-password"
              class="form-input"
              placeholder="Ripeti la password"
              autocomplete="new-password"
              required
              aria-required="true"
            >
            <svg class="form-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <circle cx="12" cy="16" r="1"></circle>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <div id="confirm-password-error" class="error-message" aria-live="polite"></div>
        </div>

        <div class="checkbox-wrapper">
          <input type="checkbox" id="terms" name="terms" class="checkbox-input" required aria-required="true">
          <label for="terms" class="checkbox-label">
            Accetto i <a href="#" target="_blank"><span>Termini e Condizioni</span></a> e la <a href="#" target="_blank"><span lang="en">Privacy Policy</span></a>
          </label>
          <div id="terms-error" class="error-message" aria-live="polite"></div>
        </div>

        <button type="submit" class="btn btn-primary">
          Crea  <span lang="en">Account</span>
        </button>
      </form>

      <div class="auth-footer">
        <p class="auth-footer-text">
          Hai già un <span lang="en">account</span>?
          <a href="login_form.php" class="auth-footer-link"><span>Accedi qui</span></a>
        </p>
      </div>
    </div>
  </div>
  <?php echo $footer; ?>
</body>
</html>
