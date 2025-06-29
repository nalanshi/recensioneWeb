<?php
/**
 * Pagina dashboard utente
 */

require_once 'database.php';

SessionManager::start();
SessionManager::requireLogin();

// Sincronizza i dati dell'utente con il database per ottenere l'ultima foto profilo
if (SessionManager::isLoggedIn()) {
    $userManager = new UserManager();
    $user = $userManager->getUserById(SessionManager::getUserId());
    if ($user) {
        $_SESSION['user_data']['profile_photo'] = $user['profile_photo'];
        $_SESSION['profile_photo'] = $user['profile_photo'];
    }
}

$userManager = new UserManager();
$commentManager = new CommentManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'profile') {
        $data = [
            'last_name' => trim($_POST['lastName'] ?? ''),
            'first_name' => trim($_POST['firstName'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'birth_day' => (int)($_POST['birthDay'] ?? 0),
            'birth_month' => (int)($_POST['birthMonth'] ?? 0),
            'birth_year' => (int)($_POST['birthYear'] ?? 0)
        ];
        $userManager->updateProfile(SessionManager::getUserId(), $data);
        header('Location: dashboard.php');
        exit();
    } elseif ($action === 'password') {
        $userManager->changePassword(SessionManager::getUserId(), $_POST['currentPassword'] ?? '', $_POST['newPassword'] ?? '');
        header('Location: dashboard.php');
        exit();
    } elseif ($action === 'edit_comment') {
        $commentManager->updateComment((int)($_POST['comment_id'] ?? 0), ['rating'=>(int)($_POST['rating'] ?? 1),'content'=>trim($_POST['content'] ?? '')], $_SESSION['username']);
        header('Location: dashboard.php?section=commenti');
        exit();
    } elseif ($action === 'delete_comment') {
        $commentManager->deleteComment((int)$_POST['delete_comment'], $_SESSION['username']);
        header('Location: dashboard.php?section=commenti');
        exit();
    }
}

$header = file_get_contents("../static/header.html");
$footer = file_get_contents("../static/footer.html");
$footer = str_replace('<footer', '<footer class="hidden" id="dashboardFooter"', $footer);
  $DOM = file_get_contents("../static/dashboard.html");

  if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
      $DOM = preg_replace('/<!--\s*Sezione pericolosa\s*-->.*?<\\/div>\s*/s', '', $DOM);
  }

$DOM = str_replace("<!-- FOOTER_PLACEHOLDER -->", $footer, $DOM);

$username = $_SESSION['username'];

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
$header = str_replace("<!-- HEADER_LOGIN_PLACEHOLDER -->", $headerLoginHtml, $header);
$header = Utils::markCurrentNavLink($header, basename($_SERVER['PHP_SELF']));
$DOM = str_replace("<!-- HEADER_PLACEHOLDER -->", $header, $DOM);

$userData = $userManager->getUserById(SessionManager::getUserId());
if ($userData) {
    $DOM = str_replace('id="lastName"', 'id="lastName" value="'.htmlspecialchars($userData['last_name']).'"', $DOM);
    $DOM = str_replace('id="firstName"', 'id="firstName" value="'.htmlspecialchars($userData['first_name']).'"', $DOM);
    $DOM = str_replace('id="email"', 'id="email" value="'.htmlspecialchars($userData['email']).'"', $DOM);
    $DOM = str_replace('id="username"', 'id="username" value="'.htmlspecialchars($userData['username']).'"', $DOM);
    if ($userData['profile_photo']) {
        $DOM = str_replace('id="profilePhoto"', 'id="profilePhoto" src="../'.$userData['profile_photo'].'"', $DOM);
    }
    $DOM = preg_replace('/id="profileUsername">.*?<\/p>/', 'id="profileUsername">@'.htmlspecialchars($userData['username']).'</p>', $DOM);

    $dayOptions = '';
    for ($i=1; $i<=31; $i++) { $sel = ($userData['birth_day']==$i)?' selected':''; $dayOptions .= "<option value='$i'$sel>$i</option>"; }
    $DOM = str_replace('<select id="birthDay" name="birthDay"></select>', '<select id="birthDay" name="birthDay">'.$dayOptions.'</select>', $DOM);

    $monthOptions = '';
    for ($i=1; $i<=12; $i++) { $sel = ($userData['birth_month']==$i)?' selected':''; $monthOptions .= "<option value='$i'$sel>$i</option>"; }
    $DOM = str_replace('<select id="birthMonth" name="birthMonth"></select>', '<select id="birthMonth" name="birthMonth">'.$monthOptions.'</select>', $DOM);

    $yearOptions = '';
    $currentYear = (int)date('Y');
    for ($i=$currentYear; $i>=1900; $i--) { $sel = ($userData['birth_year']==$i)?' selected':''; $yearOptions .= "<option value='$i'$sel>$i</option>"; }
    $DOM = str_replace('<select id="birthYear" name="birthYear"></select>', '<select id="birthYear" name="birthYear">'.$yearOptions.'</select>', $DOM);
}

$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'rating' => (int)($_GET['rating'] ?? 0)
];
$comments = $commentManager->getUserComments($_SESSION['username'],$page,10,$filters);
$commentsHtml = '';
if ($comments && !empty($comments['comments'])) {
    foreach ($comments['comments'] as $c) {
        $stars = Utils::generateStars($c['rating']);
        $date = Utils::formatDate($c['created_at']);
        $commentsHtml .= "<div class='review-item'><div class='review-content'>".
                         "<div class='review-header'><h3 class='review-title'>".htmlspecialchars($c['title'])."</h3><span class='review-date'>{$date}</span></div>".
                         "<div class='review-rating'>{$stars}</div>".
                         "<p class='review-text'>".htmlspecialchars($c['content'])."</p>".
                         "<div class='review-actions'><form method='post' style='display:inline'><input type='hidden' name='delete_comment' value='{$c['id']}'><input type='hidden' name='action' value='delete_comment'><button type='submit' class='btn-action btn-danger'>Elimina</button></form></div>".
                         "</div></div>";
    }
}
$DOM = str_replace('<!--COMMENTS_PLACEHOLDER-->', $commentsHtml, $DOM);

$paginationHtml = '';
if ($comments && $comments['total_pages'] > 1) {
    $total=$comments['total_pages'];$current=$comments['page'];
    $base='search='.urlencode($filters['search']).'&rating='.$filters['rating'];
    if ($current>1){$paginationHtml.='<a class="pagination-btn" href="?page='.($current-1).'&'.$base.'">&laquo;</a>';}
    for($i=1;$i<=$total;$i++){ $class=$i==$current?'pagination-btn active':'pagination-btn'; $paginationHtml.='<a class="'.$class.'" href="?page='.$i.'&'.$base.'">'.$i.'</a>'; }
    if($current<$total){$paginationHtml.='<a class="pagination-btn" href="?page='.($current+1).'&'.$base.'">&raquo;</a>';}
}
$DOM = str_replace('<!--PAGINATION_PLACEHOLDER-->', $paginationHtml, $DOM);

if ($filters['search'] !== '') {
    $DOM = str_replace('name="search" placeholder="Cerca nei tuoi commenti..."', 'name="search" placeholder="Cerca nei tuoi commenti..." value="'.htmlspecialchars($filters['search'],ENT_QUOTES).'"', $DOM);
}
if ($filters['rating']) {
    $DOM = str_replace('value="'.$filters['rating'].'">', 'value="'.$filters['rating'].'" selected>', $DOM);
}

echo $DOM;

