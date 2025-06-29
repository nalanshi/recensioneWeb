<?php
require_once 'database.php';

SessionManager::start();

// Sync user profile photo
if (SessionManager::isLoggedIn()) {
    $userManager = new UserManager();
    $user = $userManager->getUserById(SessionManager::getUserId());
    if ($user) {
        $_SESSION['user_data']['profile_photo'] = $user['profile_photo'];
        $_SESSION['profile_photo'] = $user['profile_photo'];
    }
}

http_response_code(404);

$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");
$template = file_get_contents("../static/404.html");

// Adjust CSS paths if called from root
$cssBase = dirname($_SERVER['PHP_SELF']) === '/' ? 'css/' : '../css/';
$template = str_replace("../css/style.css", $cssBase . 'style.css', $template);
$template = str_replace("../css/pages.css", $cssBase . 'pages.css', $template);

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
    $headerLoginHtml = "<details class='user-menu'>\n".
                       "<summary class='login-link' aria-label='Menu utente'>\n".
                       "<div class='user-icon-bg'>{$icon}</div>\n".
                       "<span class='login-text'>{$username}</span>\n".
                       "</summary>\n".
                       "<div class='user-menu-panel'>\n".
                       "<a href='dashboard.php'><span lang='en'>Dashboard</span></a>";
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $headerLoginHtml .= "<a href='gestione_recensioni.php'><span>Gestione recensioni</span></a>";
    }
    $headerLoginHtml .= "<a href='logout.php'><span lang='en'>Logout</span></a>\n".
                       "</div>\n".
                       "</details>";
}

$header = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $header);
$header = Utils::markCurrentNavLink($header, basename($_SERVER['PHP_SELF']));

$template = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $template);
$template = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $template);

echo $template;
?>
