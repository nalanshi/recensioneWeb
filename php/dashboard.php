<?php
/**
 * Dashboard PHP - Connessione tra HTML e Database
 * Utilizza file_get_contents e str_replace per sostituire i campi dinamici
 */

require_once 'database.php';
require_once 'classes/SessionManager.php';
require_once 'classes/Utils.php';
// Avvia la sessione
SessionManager::start();

// Verifica che l'utente sia loggato
if (!SessionManager::isLoggedIn()) {
    SessionManager::requireLogin();
}

$userId = SessionManager::getUserId();
$userManager = new UserManager();

try {
    // Ottieni i dati dell'utente dal database
    $userData = $userManager->getUserById($userId);
    
    if (!$userData) {
        // Se l'utente non esiste, logout e redirect
        SessionManager::logout();
        SessionManager::requireLogin();
    }
    
    // Leggi il file HTML della dashboard
    $htmlContent = file_get_contents('../static/dashboard.html');
    
    if ($htmlContent === false) {
        throw new Exception("Impossibile leggere il file dashboard.html");
    }
    
    // Prepara i dati per la sostituzione
    $firstName = htmlspecialchars($userData['first_name'] ?? '');
    $lastName = htmlspecialchars($userData['last_name'] ?? '');
    $fullName = trim($firstName . ' ' . $lastName);
    $email = htmlspecialchars($userData['email'] ?? '');
    $username = htmlspecialchars($userData['username'] ?? '');
    $birthDay = $userData['birth_day'] ?? 1;
    $birthMonth = $userData['birth_month'] ?? 1;
    $birthYear = $userData['birth_year'] ?? 2000;
    
    // Gestione dell'immagine profilo
    $profileImage = '../images/icon/user.png'; // Immagine di default
    if (!empty($userData['profile_photo'])) {
        $photoPath = '../' . $userData['profile_photo'];
        if (file_exists($photoPath)) {
            $profileImage = $photoPath;
        }else{
            $profileImage = '../images/icon/user.png';
        }
    }
    
    // Array di sostituzioni per i campi del profilo
    $replacements = [        
        // Nome e cognome nei campi del form
        'value="cognome"' => 'value="' . $lastName . '"',
        'value= "nome"' => 'value="' . $firstName . '"',
        
        // Email
        'value="email"' => 'value="' . $email . '"',
        
        // Username
        'value="alexshu2002"' => 'value="' . $username . '"',
        '@' => '@' . $username,
        
        // Immagini profilo (tutte le occorrenze)
        '../images/icon/user.png' => $profileImage,
    ];
    
    // Applica le sostituzioni
    foreach ($replacements as $search => $replace) {
        $htmlContent = str_replace($search, $replace, $htmlContent);
    }
    
    // Sostituzioni più specifiche per i select della data di nascita
    // Giorno
    $htmlContent = preg_replace_callback('/<select id="birthDay"[^>]*>(.*?)<\/select>/s', function($matches) use ($birthDay) {
        $options = '';
        for ($i = 1; $i <= 31; $i++) {
            $selected = ($i == $birthDay) ? ' selected' : '';
            $options .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>\n';
        }
        return '<select id="birthDay" name="birthDay">\n' . $options . '</select>';
    }, $htmlContent);

    // Mese
    $months = [
        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
    ];
    $htmlContent = preg_replace_callback('/<select id="birthMonth"[^>]*>(.*?)<\/select>/s', function($matches) use ($birthMonth, $months) {
        $options = '';
        foreach ($months as $num => $name) {
            $selected = ($num == $birthMonth) ? ' selected' : '';
            $options .= '<option value="' . $num . '"' . $selected . '>' . $name . '</option>\n';
        }
        return '<select id="birthMonth" name="birthMonth">\n' . $options . '</select>';
    }, $htmlContent);

    // Anno
    $currentYear = date('Y');
    $htmlContent = preg_replace_callback('/<select id="birthYear"[^>]*>(.*?)<\/select>/s', function($matches) use ($birthYear, $currentYear) {
        $options = '';
        for ($i = $currentYear; $i >= 1900; $i--) {
            $selected = ($i == $birthYear) ? ' selected' : '';
            $options .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>\n';
        }
        return '<select id="birthYear" name="birthYear">\n' . $options . '</select>';
    }, $htmlContent);
    
    // Aggiungi script per gestire l'upload dell'immagine e l'aggiornamento del profilo
    $script = <<<JS
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const changePhotoBtn = document.getElementById("changePhotoBtn");
        const photoInput = document.getElementById("photoInput");
        const profilePhoto = document.getElementById("profilePhoto");
        const userAvatar = document.getElementById("userAvatar");
        const profileForm = document.getElementById("profileForm");

        // Funzione per aggiornare le immagini nella pagina
        function updateProfileImages(newSrc) {
            if (profilePhoto) profilePhoto.src = newSrc + "?t=" + Date.now();
            if (userAvatar) userAvatar.src = newSrc + "?t=" + Date.now();
        }

        // Gestione upload immagine profilo
        if (changePhotoBtn && photoInput) {
            changePhotoBtn.addEventListener("click", function() {
                photoInput.click();
            });
            
            photoInput.addEventListener("change", function(e) {
                const file = e.target.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append("profile_image", file);

                    fetch("profile_api.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log("Immagine caricata con successo");
                            updateProfileImages(data.image_path);
                        } else {
                            alert("Errore durante il caricamento: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Errore:", error);
                        alert("Errore durante il caricamento dell'immagine");
                    });
                }
            });
        }

        // Gestione aggiornamento dati profilo
        if (profileForm) {
            profileForm.addEventListener("submit", function(e) {
                e.preventDefault();

                const formData = new FormData(profileForm);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                // Aggiungi il token CSRF
                data['csrf_token'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                fetch("profile_api.php", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Profilo aggiornato con successo!");
                        // Aggiorna il nome utente visualizzato se necessario
                        document.getElementById("userName").textContent = document.getElementById("firstName").value + " " + document.getElementById("lastName").value;
                        document.querySelector(".profile-name").textContent = document.getElementById("firstName").value + " " + document.getElementById("lastName").value;
                    } else {
                        alert("Errore durante l'aggiornamento: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Errore:", error);
                    alert("Errore durante l'aggiornamento del profilo");
                });
            });
        }
    });
    </script>
JS;
    
    // Inserisci lo script prima della chiusura del body
    $htmlContent = str_replace('</body>', $script . '</body>', $htmlContent);
    
    // Aggiungi meta tag per CSRF token
    $csrfToken = Utils::generateCSRFToken();
    $csrfMeta = '<meta name="csrf-token" content="' . $csrfToken . '">';
    $htmlContent = str_replace('<meta name="viewport"', $csrfMeta . "\n    " . '<meta name="viewport"', $htmlContent);
    
    // Output dell'HTML modificato
    echo $htmlContent;
    
} catch (Exception $e) {
    error_log("Errore dashboard.php: " . $e->getMessage());
    
    // Mostra pagina di errore
    echo '<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Errore - ReviewDiver</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .error-container { max-width: 500px; margin: 0 auto; }
            .error-title { color: #e74c3c; font-size: 24px; margin-bottom: 20px; }
            .error-message { color: #666; margin-bottom: 30px; }
            .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1 class="error-title">Errore nel caricamento della dashboard</h1>
            <p class="error-message">Si è verificato un errore durante il caricamento dei tuoi dati. Riprova più tardi.</p>
            <a href="../index.php" class="btn">Torna alla home</a>
        </div>
    </body>
    </html>';
}
?>

