-- Migration pour base de données des utilisateurs
CREATE DATABASE IF NOT EXISTS tp3_users;
USE tp3_users;

CREATE TABLE IF NOT EXISTS user_apis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index pour performance
CREATE INDEX idx_user_apis_email ON user_apis(email);
CREATE INDEX idx_user_apis_role ON user_apis(role);
