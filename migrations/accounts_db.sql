-- Migration pour base de données des comptes bancaires
CREATE DATABASE IF NOT EXISTS tp3_accounts;
USE tp3_accounts;

CREATE TABLE IF NOT EXISTS compte_bancaires (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_compte VARCHAR(50) UNIQUE NOT NULL,
    iban VARCHAR(50) UNIQUE NOT NULL,
    bic VARCHAR(20) NOT NULL,
    solde DECIMAL(15,2) DEFAULT 0.00,
    user_id INT NOT NULL,
    statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Index pour performance
CREATE INDEX idx_compte_bancaires_user_id ON compte_bancaires(user_id);
CREATE INDEX idx_compte_bancaires_numero ON compte_bancaires(numero_compte);
CREATE INDEX idx_compte_bancaires_iban ON compte_bancaires(iban);
CREATE INDEX idx_compte_bancaires_statut ON compte_bancaires(statut);
