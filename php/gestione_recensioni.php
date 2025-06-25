<?php
/**
 * Pagina di gestione delle recensioni (solo admin)
 *
 * Consente di pubblicare, modificare ed eliminare recensioni
 */

require_once 'database.php';

SessionManager::start();
SessionManager::requireLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Accesso negato';
    exit();
}

$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");
$DOM = file_get_contents("../static/gestione_recensioni.html");

$DOM = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $DOM);
$DOM = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $DOM);

$username = $_SESSION['username'];

$profilePhoto = $_SESSION['user_data']['profile_photo'] ?? $_SESSION['profile_photo'] ?? '';
$icon = $profilePhoto ? "<img src='../{$profilePhoto}' alt='Foto profilo di {$username}' class='user-avatar'>" : "<svg width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' id='userIcon'><path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path><circle cx='12' cy='7' r='4'></circle></svg>";
$headerLoginHtml = "<div class='login-link user-menu' aria-label='Menu utente'>
                        <div class='user-icon-bg'>
                          {$icon}
                        </div>
                        <div class='login-text'>{$username}</div>
                        <div class='user-dropdown'>
                          <a href='dashboard.php'>Dashboard</a>
                          <a href='gestione_recensioni.php'>Gestione recensioni</a>
                          <a href='logout.php'>Logout</a>
                        </div>
                      </div>";

$DOM = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $DOM);

echo $DOM;
?>
