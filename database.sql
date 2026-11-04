CREATE DATABASE IF NOT EXISTS `student_hub_db`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `student_hub_db`;

DROP TABLE IF EXISTS `event_registrations`;
DROP TABLE IF EXISTS `feedback`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `gender` ENUM('female', 'male', 'other') NOT NULL,
    `course` VARCHAR(100) NULL DEFAULT 'Computer Science',
    `role` ENUM('student', 'admin') DEFAULT 'student',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_username` (`username`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `course` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `category` ENUM('Workshop', 'Competition', 'Seminar', 'Other') NOT NULL DEFAULT 'Workshop',
    `event_date` DATE NOT NULL,
    `description` TEXT NULL,
    `poster` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_events_category` (`category`),
    INDEX `idx_events_date` (`event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_registrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `event_id` INT UNSIGNED NOT NULL,
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('confirmed', 'waitlisted', 'cancelled') DEFAULT 'confirmed',
    UNIQUE KEY `uk_user_event` (`user_id`, `event_id`),
    CONSTRAINT `fk_registrations_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_registrations_event`
        FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feedback` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL,
    `feedback_text` TEXT NOT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_feedback_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_name` VARCHAR(100) NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`name`, `username`, `email`, `mobile`, `password`, `gender`, `course`, `role`) VALUES
('Administrator', 'admin', 'admin@studenthub.com', '9876543210', '$2y$10$mbZcoLe4A9gcrVHSK87/ROQ0WRHYPtMqiRYIp9a1NDPj12XXsZ1Hi', 'other', NULL, 'admin'),
('Student Demo', 'student', 'student@studenthub.com', '9000000001', '$2y$10$PTOOU.yUh7Hhqjw6Dz8mU.lLTIhdve9t.aYGLe.qNXf19oYJXjU7y', 'male', 'AIML', 'student'),
('Manthan Patel', 'manthan', 'manthan@example.com', '9000000009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'male', 'Computer Science', 'student');

INSERT INTO `students` (`id`, `name`, `email`, `course`) VALUES
(3, 'OM SUTARIYA', '25aiml072@charusat.edu.in', 'AIML');

INSERT INTO `events` (`name`, `category`, `event_date`, `description`) VALUES
('AI Workshop', 'Workshop', '2026-08-20', 'Hands-on session on Artificial Intelligence and Machine Learning fundamentals.'),
('Hackathon', 'Competition', '2026-08-22', '24-hour inter-college coding marathon to build creative software solutions.'),
('Flutter Workshop', 'Workshop', '2026-08-25', 'Build cross-platform mobile apps using Flutter & Dart.'),
('Coding Contest', 'Competition', '2026-08-28', 'Competitive programming challenge testing data structures and algorithms.'),
('Web Seminar', 'Seminar', '2026-09-01', 'Modern web architecture, micro frontends, and backend engineering.'),
('Python Workshop', 'Workshop', '2026-09-03', 'Master modern Python programming from syntax to libraries.'),
('Tech Talk', 'Seminar', '2026-09-05', 'Guest lecture from industry veterans on career paths in tech.'),
('Java Contest', 'Competition', '2026-09-08', 'Object-oriented programming challenge in Java.'),
('Cyber Security Workshop', 'Workshop', '2026-09-10', 'Ethical hacking, defense techniques, and network security.'),
('AI Seminar', 'Seminar', '2026-09-12', 'Exploration of large multimodal models and generative agent frameworks.');

INSERT INTO `audit_logs` (`user_name`, `action`, `ip_address`) VALUES 
('Administrator', 'Admin logged in', '127.0.0.1'),
('Administrator', 'Added new student: OM SUTARIYA', '127.0.0.1'),
('Administrator', 'Added event: Cloud Computing Workshop', '127.0.0.1'),
('Administrator', 'Updated student record ID #3', '127.0.0.1');
