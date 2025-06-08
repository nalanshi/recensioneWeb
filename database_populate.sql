-- Database Population Script for ReviewDiver Application
-- This script populates the database with sample data for development and testing purposes

-- Clear existing data (optional, comment out if not needed)
DELETE FROM user_settings where user_id != -1;
DELETE FROM review_likes where user_id != -1;
DELETE FROM reviews where user_id != -1;
DELETE FROM utenti where id != -1;

-- Reset AUTO_INCREMENT values (commented out as it may cause syntax errors)
-- ALTER TABLE utenti AUTO_INCREMENT = 7;
-- ALTER TABLE reviews AUTO_INCREMENT = 10;
-- ALTER TABLE review_likes AUTO_INCREMENT = 15;

-- Sample Users
-- Note: Passwords are hashed using bcrypt with cost factor 10
-- All sample passwords are 'Password123' for testing purposes
INSERT INTO utenti (id, username, email, password, nome, cognome, profile_photo, birth_day, birth_month, birth_year, role, created_at)
VALUES
    (1, 'admin', 'admin@reviewdiver.com', '$2y$10$w37S7ZfpSIPhMZ8wFtWKTOZiCPSVN4gTvacaXs.eQHezFSHnNCyG.', 'Admin', 'User', 'images/profiles/admin.jpg', 15, 5, 1985, 'admin', NOW()),
    (2, 'mario_rossi', 'mario.rossi@example.com', '$2y$10$w37S7ZfpSIPhMZ8wFtWKTOZiCPSVN4gTvacaXs.eQHezFSHnNCyG.', 'Mario', 'Rossi', 'images/profiles/mario.jpg', 10, 7, 1990, 'user', NOW()),
    (3, 'giulia_bianchi', 'giulia.bianchi@example.com', '$2y$10$w37S7ZfpSIPhMZ8wFtWKTOZiCPSVN4gTvacaXs.eQHezFSHnNCyG.', 'Giulia', 'Bianchi', 'images/profiles/giulia.jpg', 22, 3, 1988, 'user', NOW()),
    (4, 'luca_verdi', 'luca.verdi@example.com', '$2y$10$w37S7ZfpSIPhMZ8wFtWKTOZiCPSVN4gTvacaXs.eQHezFSHnNCyG.', 'Luca', 'Verdi', 'images/profiles/luca.jpg', 5, 11, 1995, 'user', NOW()),
    (5, 'sofia_neri', 'sofia.neri@example.com', '$2y$10$w37S7ZfpSIPhMZ8wFtWKTOZiCPSVN4gTvacaXs.eQHezFSHnNCyG.', 'Sofia', 'Neri', 'images/profiles/sofia.jpg', 18, 9, 1992, 'user', NOW()),
    (6, 'marco_gialli', 'marco.gialli@example.com', '$2y$10$w37S7ZfpSIPhMZ8wFtWKTOZiCPSVN4gTvacaXs.eQHezFSHnNCyG.', 'Marco', 'Gialli', NULL, 30, 1, 1987, 'user', NOW());

-- Sample Reviews
-- Note: In a real MySQL environment, you would use DATE_SUB(NOW(), INTERVAL n DAY) for historical dates
-- For compatibility, we're using NOW() for all timestamps
INSERT INTO reviews (id, user_id, title, content, rating, product_name, product_image, created_at)
VALUES
    -- Mario's reviews
    (1, 2, 'Ottimo smartphone!', 'Ho acquistato questo smartphone un mese fa e sono rimasto molto soddisfatto. La fotocamera è eccezionale e la batteria dura tutto il giorno. Lo consiglio vivamente!', 5, 'Smartphone XYZ Pro', 'images/products/product1.jpg', NOW()),
    (2, 2, 'Buona fotocamera ma costosa', 'La qualità delle foto è ottima, ma il prezzo è un po'' alto rispetto alla concorrenza. Comunque, sono soddisfatto dell''acquisto.', 4, 'Fotocamera DSLR 4K', 'images/products/product2.jpg', NOW()),

    -- Giulia's reviews
    (3, 3, 'Cuffie eccezionali!', 'La cancellazione del rumore è fantastica, il suono è cristallino e la batteria dura tantissimo. Le uso tutti i giorni per lavorare e ascoltare musica.', 5, 'Cuffie Wireless Premium', 'images/products/product3.jpg', NOW()),
    (4, 3, 'Tablet perfetto per lo studio', 'Leggero, veloce e con un ottimo display. Lo uso per prendere appunti all''università e per leggere libri. La batteria dura tutta la giornata.', 5, 'Tablet Ultra Slim', 'images/products/product1.jpg', NOW()),

    -- Luca's reviews
    (5, 4, 'Action camera buona ma non eccezionale', 'La qualità video è buona, ma la stabilizzazione potrebbe essere migliore. Comunque, per il prezzo è un buon prodotto.', 3, 'Action Camera HD', 'images/products/product2.jpg', NOW()),
    (6, 4, 'Smartphone con qualche difetto', 'Il design è bello e le prestazioni sono buone, ma la fotocamera in condizioni di scarsa luminosità non è all''altezza. La batteria dura poco se si usano app pesanti.', 3, 'Smartphone XYZ Pro', 'images/products/product1.jpg', NOW()),

    -- Sofia's reviews
    (7, 5, 'Fotocamera professionale eccellente', 'Uso questa fotocamera per lavoro e sono estremamente soddisfatta. La qualità delle immagini è eccezionale e le funzionalità professionali sono complete.', 5, 'Fotocamera DSLR 4K', 'images/products/product2.jpg', NOW()),

    -- Marco's reviews
    (8, 6, 'Cuffie comode ma audio migliorabile', 'Molto comode da indossare anche per lunghi periodi, ma la qualità audio non è eccezionale. La cancellazione del rumore funziona bene.', 4, 'Cuffie Wireless Premium', 'images/products/product3.jpg', NOW()),
    (9, 6, 'Tablet con ottimo rapporto qualità-prezzo', 'Non è il top di gamma, ma per quello che costa offre prestazioni molto buone. Il display è luminoso e la batteria dura a lungo.', 4, 'Tablet Ultra Slim', 'images/products/product1.jpg', NOW());

-- Sample Review Likes
-- Note: In a real MySQL environment, you would use DATE_SUB(NOW(), INTERVAL n DAY) for historical dates
-- For compatibility, we're using NOW() for all timestamps
INSERT INTO review_likes (id, review_id, user_id, created_at)
VALUES
    -- Likes for Mario's reviews
    (1, 1, 3, NOW()), -- Giulia likes Mario's smartphone review
    (2, 1, 4, NOW()), -- Luca likes Mario's smartphone review
    (3, 1, 5, NOW()), -- Sofia likes Mario's smartphone review
    (4, 2, 5, NOW()), -- Sofia likes Mario's camera review

    -- Likes for Giulia's reviews
    (5, 3, 2, NOW()), -- Mario likes Giulia's headphones review
    (6, 3, 4, NOW()), -- Luca likes Giulia's headphones review
    (7, 3, 6, NOW()), -- Marco likes Giulia's headphones review
    (8, 4, 2, NOW()), -- Mario likes Giulia's tablet review

    -- Likes for Luca's reviews
    (9, 5, 6, NOW()), -- Marco likes Luca's action camera review

    -- Likes for Sofia's reviews
    (10, 7, 2, NOW()), -- Mario likes Sofia's camera review
    (11, 7, 3, NOW()), -- Giulia likes Sofia's camera review
    (12, 7, 4, NOW()),  -- Luca likes Sofia's camera review

    -- Likes for Marco's reviews
    (13, 8, 3, NOW()),  -- Giulia likes Marco's headphones review
    (14, 9, 5, NOW());  -- Sofia likes Marco's tablet review

-- Sample User Settings
-- Note: In a real MySQL environment, you would use DATE_SUB(NOW(), INTERVAL n DAY) for historical dates
-- For compatibility, we're using NOW() for all timestamps
INSERT INTO user_settings (user_id, setting_key, setting_value, updated_at)
VALUES
    -- Admin settings
    (1, 'theme', 'dark', NOW()),
    (1, 'language', 'it', NOW()),
    (1, 'email_notifications', '1', NOW()),
    (1, 'review_notifications', '1', NOW()),
    (1, 'profile_visibility', '1', NOW()),
    (1, 'show_email', '0', NOW()),

    -- Mario's settings
    (2, 'theme', 'light', NOW()),
    (2, 'language', 'it', NOW()),
    (2, 'email_notifications', '1', NOW()),
    (2, 'review_notifications', '1', NOW()),

    -- Giulia's settings
    (3, 'theme', 'auto', NOW()),
    (3, 'language', 'en', NOW()),
    (3, 'profile_visibility', '0', NOW()),

    -- Luca's settings
    (4, 'theme', 'dark', NOW()),
    (4, 'email_notifications', '0', NOW()),

    -- Sofia's settings
    (5, 'language', 'fr', NOW()),
    (5, 'show_email', '1', NOW()),

    -- Marco's settings (using defaults for most)
    (6, 'review_notifications', '0', NOW());

-- Confirmation message
SELECT 'Database populated successfully with sample data.' AS message;
