<?php
/**
 * Funzioni di utilità
 */
class Utils {
    /**
     * Valida i dati del profilo
     */
    public static function validateProfileData($data) {
        $errors = [];

        if (empty($data["first_name"]) || strlen($data["first_name"]) < 2) {
            $errors[] = "Il nome deve contenere almeno 2 caratteri";
        }

        if (empty($data["last_name"]) || strlen($data["last_name"]) < 2) {
            $errors[] = "Il cognome deve contenere almeno 2 caratteri";
        }

        if (empty($data["email"]) || !filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email non valida";
        }

        if (!empty($data["birth_day"]) && ($data["birth_day"] < 1 || $data["birth_day"] > 31)) {
            $errors[] = "Giorno di nascita non valido";
        }

        if (!empty($data["birth_month"]) && ($data["birth_month"] < 1 || $data["birth_month"] > 12)) {
            $errors[] = "Mese di nascita non valido";
        }

        if (!empty($data["birth_year"]) && ($data["birth_year"] < 1900 || $data["birth_year"] > date("Y"))) {
            $errors[] = "Anno di nascita non valido";
        }

        return $errors;
    }

    /**
     * Valida la password
     */
    public static function validatePassword($password) {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "La password deve contenere almeno 8 caratteri";
        }

        if (!preg_match("/[A-Z]/", $password)) {
            $errors[] = "La password deve contenere almeno una lettera maiuscola";
        }

        if (!preg_match("/[0-9]/", $password)) {
            $errors[] = "La password deve contenere almeno un numero";
        }

        return $errors;
    }

    /**
     * Gestisce l\"upload della foto profilo
     */
    public static function handlePhotoUpload($file, $userId) {
        $uploadDir = __DIR__ . "/../images/profileimage/"; // Modificato per usare la directory corretta
        $allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Verifica tipo file
        if (!in_array($file["type"], $allowedTypes)) {
            return ["success" => false, "message" => "Tipo di file non consentito."];
        }

        // Verifica dimensione file
        if ($file["size"] > $maxSize) {
            return ["success" => false, "message" => "Il file è troppo grande. Dimensione massima 5MB."];
        }

        // Verifica errori di upload
        if ($file["error"] !== UPLOAD_ERR_OK) {
            return ["success" => false, "message" => "Errore durante l\"upload del file: " . $file["error"]];
        }

        // Genera un nome file univoco
        $fileExtension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $fileName = uniqid("profile_") . "." . $fileExtension;
        $targetPath = $uploadDir . $fileName;

        // Crea la directory se non esiste
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Sposta il file caricato
        if (move_uploaded_file($file["tmp_name"], $targetPath)) {
            return ["success" => true, "path" => "images/profileimage/" . $fileName];
        } else {
            return ["success" => false, "message" => "Impossibile spostare il file caricato."];
        }
    }

    /**
     * Sanifica l\"input
     */
    public static function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }

    /**
     * Genera un token CSRF
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
        return $_SESSION["csrf_token"];
    }

    /**
     * Verifica un token CSRF
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
    }
}
?>

