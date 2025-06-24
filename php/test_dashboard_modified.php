<?php
/**
 * Test per verificare il funzionamento della dashboard modificata
 */

require_once 'database.php';

echo "<h1>Test Dashboard Modificata</h1>";

// Test connessione database
echo "<h2>1. Test Connessione Database</h2>";
try {
    $db = DatabaseConfig::getConnection();
    echo "<p style='color: green;'>✓ Connessione al database riuscita!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Errore connessione database: " . $e->getMessage() . "</p>";
    exit;
}

// Test UserManager
echo "<h2>2. Test UserManager</h2>";
try {
    $userManager = new UserManager();
    echo "<p style='color: green;'>✓ UserManager inizializzato correttamente</p>";
    
    // Prova a ottenere un utente (assumendo che esista un utente con ID 1)
    $userData = $userManager->getUserById(1);
    if ($userData) {
        echo "<p style='color: green;'>✓ Dati utente trovati per ID 1:</p>";
        echo "<pre>";
        print_r($userData);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠ Nessun utente trovato con ID 1. Assicurati che ci sia almeno un utente nel database.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Errore UserManager: " . $e->getMessage() . "</p>";
}

// Test directory immagini
echo "<h2>3. Test Directory Immagini</h2>";
$imageDir = '../images/profileimage/';
if (is_dir($imageDir)) {
    echo "<p style='color: green;'>✓ Directory immagini esiste: $imageDir</p>";
    
    if (is_writable($imageDir)) {
        echo "<p style='color: green;'>✓ Directory immagini è scrivibile</p>";
    } else {
        echo "<p style='color: red;'>✗ Directory immagini non è scrivibile. Controlla i permessi.</p>";
    }
    
    $files = scandir($imageDir);
    $imageFiles = array_filter($files, function($file) {
        return !in_array($file, ['.', '..']) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
    });
    
    if (!empty($imageFiles)) {
        echo "<p style='color: green;'>✓ Immagini trovate nella directory:</p>";
        echo "<ul>";
        foreach ($imageFiles as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠ Nessuna immagine trovata nella directory</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Directory immagini non esiste. Creazione in corso...</p>";
    if (mkdir($imageDir, 0755, true)) {
        echo "<p style='color: green;'>✓ Directory creata con successo</p>";
    } else {
        echo "<p style='color: red;'>✗ Impossibile creare la directory</p>";
    }
}

// Test file dashboard.html
echo "<h2>4. Test File Dashboard</h2>";
$dashboardHtml = '../static/dashboard.html';
if (file_exists($dashboardHtml)) {
    echo "<p style='color: green;'>✓ File dashboard.html trovato</p>";
    
    if (is_readable($dashboardHtml)) {
        echo "<p style='color: green;'>✓ File dashboard.html è leggibile</p>";
    } else {
        echo "<p style='color: red;'>✗ File dashboard.html non è leggibile</p>";
    }
} else {
    echo "<p style='color: red;'>✗ File dashboard.html non trovato in $dashboardHtml</p>";
}

// Test SessionManager
echo "<h2>5. Test SessionManager</h2>";
try {
    SessionManager::start();
    echo "<p style='color: green;'>✓ Sessione avviata correttamente</p>";
    
    // Simula login per test
    $_SESSION['user_id'] = 1;
    
    if (SessionManager::isLoggedIn()) {
        echo "<p style='color: green;'>✓ Controllo login funziona</p>";
        echo "<p>User ID: " . SessionManager::getUserId() . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Controllo login non funziona</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Errore SessionManager: " . $e->getMessage() . "</p>";
}

// Test Utils
echo "<h2>6. Test Utils</h2>";
try {
    $testData = [
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'email' => 'mario.rossi@example.com',
        'birth_day' => 15,
        'birth_month' => 3,
        'birth_year' => 1990
    ];
    
    $errors = Utils::validateProfileData($testData);
    if (empty($errors)) {
        echo "<p style='color: green;'>✓ Validazione dati profilo funziona</p>";
    } else {
        echo "<p style='color: red;'>✗ Errori validazione: " . implode(', ', $errors) . "</p>";
    }
    
    $csrfToken = Utils::generateCSRFToken();
    if (!empty($csrfToken)) {
        echo "<p style='color: green;'>✓ Generazione CSRF token funziona</p>";
    } else {
        echo "<p style='color: red;'>✗ Generazione CSRF token fallita</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Errore Utils: " . $e->getMessage() . "</p>";
}

echo "<h2>7. Link per Test Manuali</h2>";
echo "<p><a href='dashboard.php' target='_blank'>🔗 Apri Dashboard (richiede login)</a></p>";
echo "<p><a href='profile_api.php' target='_blank'>🔗 Test Profile API (richiede login)</a></p>";

echo "<h2>Riepilogo</h2>";
echo "<p>Se tutti i test sopra mostrano ✓, il sistema dovrebbe funzionare correttamente.</p>";
echo "<p>Per testare completamente:</p>";
echo "<ol>";
echo "<li>Assicurati di avere almeno un utente nel database</li>";
echo "<li>Effettua il login</li>";
echo "<li>Accedi alla dashboard</li>";
echo "<li>Prova a modificare i dati del profilo</li>";
echo "<li>Prova a caricare un'immagine profilo</li>";
echo "</ol>";
?>

