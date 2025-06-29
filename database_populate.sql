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
    (16, 'utente16', 'utente16@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Sedici', NULL, 10, 10, 1999, 'user', NOW()),
    (17, 'utente17', 'utente17@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Diciassette', NULL, 11, 11, 2000, 'user', NOW()),
    (18, 'utente18', 'utente18@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Diciotto', NULL, 12, 12, 2001, 'user', NOW()),
    (19, 'utente19', 'utente19@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Diciannove', NULL, 13, 1, 2002, 'user', NOW()),
    (20, 'utente20', 'utente20@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Venti', NULL, 14, 2, 2003, 'user', NOW()),
    (21, 'utente21', 'utente21@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Ventuno', NULL, 15, 3, 2004, 'user', NOW()),
    (22, 'utente22', 'utente22@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Ventidue', NULL, 16, 4, 2005, 'user', NOW()),
    (23, 'utente23', 'utente23@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Ventitre', NULL, 17, 5, 2006, 'user', NOW()),
    (24, 'utente24', 'utente24@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Ventiquattro', NULL, 18, 6, 2007, 'user', NOW()),
    (25, 'utente25', 'utente25@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Venticinque', NULL, 19, 7, 2008, 'user', NOW()),
    (26, 'utente26', 'utente26@example.com', '$2b$10$5gjvYUVBrpxLiyQquloevOb1T8BKKL/HqH8nvBkmdZ95dSdO7siq6', 'Utente', 'Ventisei', NULL, 20, 8, 2009, 'user', NOW());


-- Sample Reviews
-- Note: In a real MySQL environment, you would use DATE_SUB(NOW(), INTERVAL n DAY) for historical dates
-- For compatibility, we're using NOW() for all timestamps
INSERT INTO reviews (id, user_id, title, content, product_name, product_image, created_at)
VALUES
    -- Admin's reviews
    (1, 1, 'Ottimo smartphone!', 'Ho acquistato questo smartphone un mese fa e sono rimasto molto soddisfatto. La fotocamera è eccezionale e la batteria dura tutto il giorno. Lo consiglio vivamente!', 'Smartphone XYZ Pro', 'images/products/product1.jpg', NOW()),
    (2, 1, 'Buona fotocamera ma costosa', 'La qualità delle foto è ottima, ma il prezzo è un po'' alto rispetto alla concorrenza. Comunque, sono soddisfatto dell''acquisto.', 'Fotocamera DSLR 4K', 'images/products/product2.jpg', NOW()),

    (3, 1, 'Cuffie eccezionali!', 'La cancellazione del rumore è fantastica, il suono è cristallino e la batteria dura tantissimo. Le uso tutti i giorni per lavorare e ascoltare musica.', 'Cuffie Wireless Premium', 'images/products/product3.jpg', NOW()),
    (4, 1, 'Tablet perfetto per lo studio', 'Leggero, veloce e con un ottimo display. Lo uso per prendere appunti all''università e per leggere libri. La batteria dura tutta la giornata.', 'Tablet Ultra Slim', 'images/products/product1.jpg', NOW()),

    (5, 1, 'Action camera buona ma non eccezionale', 'La qualità video è buona, ma la stabilizzazione potrebbe essere migliore. Comunque, per il prezzo è un buon prodotto.', 'Action Camera HD', 'images/products/product2.jpg', NOW()),
    (6, 1, 'Smartphone con qualche difetto', 'Il design è bello e le prestazioni sono buone, ma la fotocamera in condizioni di scarsa luminosità non è all''altezza. La batteria dura poco se si usano app pesanti.', 'Smartphone XYZ Pro', 'images/products/product1.jpg', NOW()),

    (7, 1, 'Fotocamera professionale eccellente', 'Uso questa fotocamera per lavoro e sono estremamente soddisfatta. La qualità delle immagini è eccezionale e le funzionalità professionali sono complete.', 'Fotocamera DSLR 4K', 'images/products/product2.jpg', NOW()),

    (8, 1, 'Cuffie comode ma audio migliorabile', 'Molto comode da indossare anche per lunghi periodi, ma la qualità audio non è eccezionale. La cancellazione del rumore funziona bene.', 'Cuffie Wireless Premium', 'images/products/product3.jpg', NOW()),
    (9, 1, 'Tablet con ottimo rapporto qualità-prezzo', 'Non è il top di gamma, ma per quello che costa offre prestazioni molto buone. Il display è luminoso e la batteria dura a lungo.', 'Tablet Ultra Slim', 'images/products/product1.jpg', NOW()),
    (10, 1, 'Mouse ergonomico', 'Un mouse molto comodo per lavorare tutto il giorno.', 'Mouse Ergonomico', NULL, NOW()),
    (11, 1, 'Tastiera meccanica', 'Ottimo feedback dei tasti e retroilluminazione.', 'Tastiera Meccanica', NULL, NOW()),
    (12, 1, 'Monitor 4K', 'Schermo nitido e colori vibranti, ma un po'' caro.', 'Monitor 4K', NULL, NOW()),
    (13, 1, 'Router veloce', 'Installazione semplice e copertura eccellente.', 'Router Wi-Fi', NULL, NOW()),
    (14, 1, 'Stampante economica', 'Buona per l''uso domestico, ma un po'' rumorosa.', 'Stampante Basic', NULL, NOW()),
    (15, 1, 'Webcam HD', 'Immagine chiara e microfono discreto.', 'Webcam HD', NULL, NOW()),
    (16, 1, 'Hard disk esterno', 'Tanta capacità a un prezzo contenuto.', 'HDD 2TB', NULL, NOW()),
    (17, 1, 'Chiavetta USB', 'Velocità di trasferimento buona e design compatto.', 'USB 64GB', NULL, NOW()),
    (18, 1, 'Altoparlante Bluetooth', 'Suono potente e connessione stabile.', 'Speaker BT', NULL, NOW()),
    (19, 1, 'Controller per PC', 'Impugnatura comoda e compatibilità perfetta.', 'Controller Pro', NULL, NOW());

-- Sample Comments
INSERT INTO comments (review_id, username, email, rating, content, created_at)
VALUES
    (1, 'antonio97', 'antonio@example.com', 5, 'Bel prodotto!', NOW()),
    (2, 'bea90', 'bea@example.com', 4, 'Molto utile!', NOW()),
    (3, 'carloUser', 'carlo@example.com', 5, 'Fantastico', NOW()),
    (4, 'diana88', 'diana@example.com', 3, 'Non male', NOW()),
    (5, 'enrico33', 'enrico@example.com', 4, 'Buon prodotto', NOW()),
    (6, 'federica22', 'federica@example.com', 5, 'Lo adoro!', NOW()),
    (7, 'giorgio7', 'giorgio@example.com', 2, 'Mi aspettavo di più', NOW()),
    (8, 'helena9', 'helena@example.com', 4, 'Soddisfatta', NOW()),
    (9, 'ivan_iv', 'ivan@example.com', 5, 'Eccellente', NOW()),
    (10, 'lara_the_best', 'lara@example.com', 3, 'Così così', NOW()),
    (11, 'mauro75', 'mauro@example.com', 4, 'Buona scelta', NOW()),
    (12, 'nicole88', 'nicole@example.com', 5, 'Davvero ottimo', NOW()),
    (13, 'oscar_os', 'oscar@example.com', 4, 'Soddisfacente', NOW()),
    (14, 'paola90', 'paola@example.com', 3, 'Nella media', NOW()),
    (15, 'quintino77', 'quintino@example.com', 5, 'Fantastico', NOW()),
    (16, 'rosanna66', 'rosanna@example.com', 4, 'Buon valore', NOW()),
    (17, 'samir22', 'samir@example.com', 2, 'Deludente', NOW()),
    (18, 'tania83', 'tania@example.com', 5, 'Mi piace molto', NOW()),
    (19, 'ugo_user', 'ugo@example.com', 4, 'Merita il prezzo', NOW()),
    (1, 'valeria99', 'valeria@example.com', 3, 'Non male', NOW()),
    (2, 'walter88', 'walter@example.com', 5, 'Consigliato', NOW());

-- Confirmation message
SELECT 'Database populated successfully with sample data.' AS message;
