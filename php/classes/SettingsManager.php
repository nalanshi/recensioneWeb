<?php
/**
 * Classe per la gestione delle impostazioni utente
 */
class SettingsManager {
    private $db;

    public function __construct() {
        $this->db = DatabaseConfig::getConnection();
    }

    /**
     * Ottiene le impostazioni dell'utente
     */
    public function getUserSettings($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT setting_key, setting_value
                FROM user_settings 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $settings = $stmt->fetchAll();

            // Converte in array associativo
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting["setting_key"]] = $setting["setting_value"];
            }

            // Impostazioni predefinite se non esistono
            $defaults = [
                "theme" => "light",
                "language" => "it",
                "email_notifications" => "1",
                "review_notifications" => "1",
                "profile_visibility" => "1",
                "show_email" => "0"
            ];

            return array_merge($defaults, $result);
        } catch (PDOException $e) {
            error_log("Errore getUserSettings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aggiorna le impostazioni dell'utente
     */
    public function updateSettings($userId, $settings) {
        try {
            $this->db->beginTransaction();

            foreach ($settings as $key => $value) {
                $stmt = $this->db->prepare("
                    INSERT INTO user_settings (user_id, setting_key, setting_value, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_at = VALUES(updated_at)
                ");
                $stmt->execute([$userId, $key, $value]);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Errore updateSettings: " . $e->getMessage());
            return false;
        }
    }
}

