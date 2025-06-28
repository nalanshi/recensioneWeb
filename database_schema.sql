-- Database Schema for ReviewDiver Application
-- Based on analysis of PHP files
-- Adapted for MySQL

-- Database: recensioni_db (from database.php)

-- Disable foreign key checks to allow dropping tables in any order
SET FOREIGN_KEY_CHECKS = 0;

-- Drop old tables if they exist
DROP TABLE IF EXISTS user_settings;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS utenti;
CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- Stores hashed passwords
    nome VARCHAR(50) NOT NULL,       -- First name
    cognome VARCHAR(50) NOT NULL,    -- Last name
    profile_photo VARCHAR(255),      -- Path to profile photo
    birth_day INT,                   -- Day of birth
    birth_month INT,                 -- Month of birth
    birth_year INT,                  -- Year of birth
    role VARCHAR(20) DEFAULT 'user', -- User role (user, admin, etc.)
    remember_token VARCHAR(255),     -- For "remember me" functionality
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- Account creation timestamp
    updated_at TIMESTAMP NULL DEFAULT NULL,            -- Last update timestamp
    deleted_at TIMESTAMP NULL DEFAULT NULL             -- Soft delete timestamp (NULL if active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    rating INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_image VARCHAR(255),      -- Path to product image
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    deleted_at TIMESTAMP NULL DEFAULT NULL,            -- Soft delete timestamp (NULL if active)
    FOREIGN KEY (user_id) REFERENCES utenti(id),
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    star TINYINT NOT NULL DEFAULT 1,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_comment (review_id, email),
    FOREIGN KEY (review_id) REFERENCES reviews(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Indexes for better performance
CREATE INDEX idx_reviews_user_id ON reviews(user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_reviews_created_at ON reviews(created_at);
CREATE INDEX idx_comments_review_id ON comments(review_id);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
