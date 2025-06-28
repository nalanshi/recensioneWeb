-- Database Population Script for ReviewDiver Application
-- This script populates the database with sample data for development and testing purposes

-- Clear existing data (optional, comment out if not needed)

-- Reset AUTO_INCREMENT values (commented out as it may cause syntax errors)
-- ALTER TABLE utenti AUTO_INCREMENT = 7;
-- ALTER TABLE reviews AUTO_INCREMENT = 10;

-- Sample Users
-- Note: Passwords are hashed using bcrypt with cost factor 10
-- All sample passwords are 'admin' for testing purposes
INSERT INTO utenti (id, username, email, password, nome, cognome, profile_photo, birth_day, birth_month, birth_year, role, created_at)
VALUES
    (1, 'admin', 'admin@reviewdiver.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Admin', 'User', 'images/profiles/admin.jpg', 15, 5, 1985, 'admin', NOW()),
    (2, 'mario_rossi', 'mario.rossi@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Mario', 'Rossi', 'images/profiles/mario.jpg', 10, 7, 1990, 'user', NOW()),
    (3, 'giulia_bianchi', 'giulia.bianchi@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Giulia', 'Bianchi', 'images/profiles/giulia.jpg', 22, 3, 1988, 'user', NOW()),
    (4, 'luca_verdi', 'luca.verdi@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Luca', 'Verdi', 'images/profiles/luca.jpg', 5, 11, 1995, 'user', NOW()),
    (5, 'sofia_neri', 'sofia.neri@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Sofia', 'Neri', 'images/profiles/sofia.jpg', 18, 9, 1992, 'user', NOW()),
    (6, 'marco_gialli', 'marco.gialli@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Marco', 'Gialli', NULL, 30, 1, 1987, 'user', NOW()),
    (7, 'utente7', 'utente7@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Sette', NULL, 1, 1, 1990, 'user', NOW()),
    (8, 'utente8', 'utente8@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Otto', NULL, 2, 2, 1991, 'user', NOW()),
    (9, 'utente9', 'utente9@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Nove', NULL, 3, 3, 1992, 'user', NOW()),
    (10, 'utente10', 'utente10@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Dieci', NULL, 4, 4, 1993, 'user', NOW()),
    (11, 'utente11', 'utente11@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Undici', NULL, 5, 5, 1994, 'user', NOW()),
    (12, 'utente12', 'utente12@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Dodici', NULL, 6, 6, 1995, 'user', NOW()),
    (13, 'utente13', 'utente13@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Tredici', NULL, 7, 7, 1996, 'user', NOW()),
    (14, 'utente14', 'utente14@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Quattordici', NULL, 8, 8, 1997, 'user', NOW()),
    (15, 'utente15', 'utente15@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Quindici', NULL, 9, 9, 1998, 'user', NOW()),
    (16, 'utente16', 'utente16@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Sedici', NULL, 10, 10, 1999, 'user', NOW());

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
    (9, 6, 'Tablet con ottimo rapporto qualità-prezzo', 'Non è il top di gamma, ma per quello che costa offre prestazioni molto buone. Il display è luminoso e la batteria dura a lungo.', 4, 'Tablet Ultra Slim', 'images/products/product1.jpg', NOW()),
    (10, 7, 'Mouse ergonomico', 'Un mouse molto comodo per lavorare tutto il giorno.', 4, 'Mouse Ergonomico', NULL, NOW()),
    (11, 8, 'Tastiera meccanica', 'Ottimo feedback dei tasti e retroilluminazione.', 5, 'Tastiera Meccanica', NULL, NOW()),
    (12, 9, 'Monitor 4K', 'Schermo nitido e colori vibranti, ma un po'' caro.', 4, 'Monitor 4K', NULL, NOW()),
    (13, 10, 'Router veloce', 'Installazione semplice e copertura eccellente.', 5, 'Router Wi-Fi', NULL, NOW()),
    (14, 11, 'Stampante economica', 'Buona per l''uso domestico, ma un po'' rumorosa.', 3, 'Stampante Basic', NULL, NOW()),
    (15, 12, 'Webcam HD', 'Immagine chiara e microfono discreto.', 4, 'Webcam HD', NULL, NOW()),
    (16, 13, 'Hard disk esterno', 'Tanta capacità a un prezzo contenuto.', 5, 'HDD 2TB', NULL, NOW()),
    (17, 14, 'Chiavetta USB', 'Velocità di trasferimento buona e design compatto.', 4, 'USB 64GB', NULL, NOW()),
    (18, 15, 'Altoparlante Bluetooth', 'Suono potente e connessione stabile.', 5, 'Speaker BT', NULL, NOW()),
    (19, 16, 'Controller per PC', 'Impugnatura comoda e compatibilità perfetta.', 4, 'Controller Pro', NULL, NOW());

-- Sample Comments
INSERT INTO comments (review_id, name, email, star, content, created_at)
VALUES
    (1, 'Antonio', 'antonio@example.com', 5, 'Bel prodotto!', NOW()),
    (2, 'Beatrice', 'bea@example.com', 4, 'Molto utile!', NOW()),
    (3, 'Carlo', 'carlo@example.com', 5, 'Fantastico', NOW()),
    (4, 'Diana', 'diana@example.com', 3, 'Non male', NOW()),
    (5, 'Enrico', 'enrico@example.com', 4, 'Buon prodotto', NOW()),
    (6, 'Federica', 'federica@example.com', 5, 'Lo adoro!', NOW()),
    (7, 'Giorgio', 'giorgio@example.com', 2, 'Mi aspettavo di più', NOW()),
    (8, 'Helena', 'helena@example.com', 4, 'Soddisfatta', NOW()),
    (9, 'Ivan', 'ivan@example.com', 5, 'Eccellente', NOW()),
    (10, 'Lara', 'lara@example.com', 3, 'Così così', NOW()),
    (11, 'Mauro', 'mauro@example.com', 4, 'Buona scelta', NOW());

-- Confirmation message
SELECT 'Database populated successfully with sample data.' AS message;
