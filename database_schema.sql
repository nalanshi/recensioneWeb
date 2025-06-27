-- Database Schema for ReviewDiver Application
-- Based on analysis of PHP files
-- Adapted for MySQL

-- Database: recensioni_db (from database.php)

-- Table: utenti (users)
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

-- Table: reviews (user reviews)
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

-- Table: review_likes (tracks likes on reviews)
CREATE TABLE review_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,            -- Soft delete timestamp (NULL if active)
    FOREIGN KEY (review_id) REFERENCES reviews(id),
    FOREIGN KEY (user_id) REFERENCES utenti(id),
    UNIQUE KEY unique_like (review_id, user_id)  -- Prevent duplicate likes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: comments (comments on reviews)
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

-- Table: user_settings (user preferences)
CREATE TABLE user_settings (
    user_id INT NOT NULL,
    setting_key VARCHAR(50) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, setting_key),
    FOREIGN KEY (user_id) REFERENCES utenti(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings values (from SettingsManager class)
-- theme: 'light', 'dark', 'auto'
-- language: 'it', 'en', 'es', 'fr'
-- email_notifications: '0', '1'
-- review_notifications: '0', '1'
-- profile_visibility: '0', '1'
-- show_email: '0', '1'

-- Indexes for better performance
CREATE INDEX idx_reviews_user_id ON reviews(user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_reviews_created_at ON reviews(created_at);
CREATE INDEX idx_review_likes_review_id ON review_likes(review_id);
CREATE INDEX idx_review_likes_user_id ON review_likes(user_id);
CREATE INDEX idx_comments_review_id ON comments(review_id);
