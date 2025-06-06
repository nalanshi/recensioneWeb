<?php
session_set_cookie_params(3600);
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    #----------------------------------------------------------------------------------------------
    #Tenta la connessione qui !!!! dobbiamo fare una funzione separata in un file separato
    #Dati di database da cambiare
    $host = 'localhost';
    $port = '5432';
    $dbname = 'progettotecweb';
    $userdbname = 'root';
    $passwordDB = '';
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $userdbname, $passwordDB);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $con = true;
    } catch (PDOException $e) {
        echo "Errore di connessione: " . $e->getMessage();
        $con = false;
    }
    #----------------------------------------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $con == true) {   #consigliato anche fare una funzione dove richiamiamo la funzione(?)
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare("");                                                  #Da inserire ancora la query
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['Pass'])){
                #Da ancora cambiare
                /*
                $_SESSION['username'] = $username;
                $_SESSION['ID_Cliente'] = $user['ID_Cliente'];
                $_SESSION['is_logged_in'] = true;
                $_SESSION['ruolo'] = $user['Ruolo'];
                if($user['Ruolo'] == 'Cliente')
                    header("Location: private.php");
                else 
                    header("Location: admin.php");
                exit();
                */
            } 
            else{
                /*echo "Password errata.            ";
                echo "La tua password : $password";
                echo "password effettiva : ".$user['Pass'];*/
                header("Location: permission_denied.php");
            }

        } else {
            echo "Utente non trovato.";
        }
    } 
}
?>