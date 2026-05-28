-- ============================================================
--  Base de données : portfolio_contact
--  Pour MAMP — importer dans phpMyAdmin
--  URL phpMyAdmin MAMP : http://localhost:8888/phpMyAdmin/
-- ============================================================

-- Créer la base si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `portfolio_contact`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `portfolio_contact`;

-- Table des messages de contact
CREATE TABLE IF NOT EXISTS `messages` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `nom`        VARCHAR(100) NOT NULL,
    `prenom`     VARCHAR(100) DEFAULT NULL,
    `email`      VARCHAR(255) NOT NULL,
    `sujet`      VARCHAR(100) NOT NULL,
    `message`    TEXT         NOT NULL,
    `ip`         VARCHAR(45)  DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `lu`         TINYINT(1)   NOT NULL DEFAULT 0,
    `date_envoi` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email`      (`email`),
    INDEX `idx_date_envoi` (`date_envoi`),
    INDEX `idx_lu`         (`lu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
