-- ============================================================================
-- SkillSwap Database Schema
-- Database & Security Lead: Muhammad Ibrahim Khan (A24CS4079)
-- SCSM2223 Cross-Platform Application Development
-- ============================================================================

CREATE DATABASE IF NOT EXISTS skillswap;
USE skillswap;

-- ============================================================================
-- TABLE: User
-- Description: Core user table supporting Learner, Tutor, and Admin roles
-- ============================================================================
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    facility VARCHAR(100) NOT NULL COMMENT 'e.g., Computing, Engineering, Business',
    photo_url TEXT,
    bio TEXT,
    role ENUM('Learner', 'Tutor', 'Admin') NOT NULL DEFAULT 'Learner',
    wallet_balance DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'For mock payment ledger',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_facility (facility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: Skill
-- Description: Catalog of skills available on the platform
-- ============================================================================
CREATE TABLE skill (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL COMMENT 'e.g., Programming, Mathematics, Languages, Design, Soft skills',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: UserSkill
-- Description: Junction table: links users to skills with rate and proficiency level
-- ============================================================================
CREATE TABLE user_skill (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    hourly_rate DECIMAL(8, 2) NOT NULL,
    level VARCHAR(50) NOT NULL COMMENT 'e.g., Beginner, Intermediate, Advanced',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skill(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_skill (user_id, skill_id),
    INDEX idx_user_id (user_id),
    INDEX idx_skill_id (skill_id),
    INDEX idx_hourly_rate (hourly_rate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: Booking
-- Description: Tutoring session bookings with lifecycle tracking
-- ============================================================================
CREATE TABLE booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    learner_id INT NOT NULL,
    tutor_id INT NOT NULL,
    skill_id INT NOT NULL,
    scheduled_at DATETIME NOT NULL COMMENT 'When the session is scheduled',
    duration INT NOT NULL COMMENT 'Duration in minutes',
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
    total DECIMAL(10, 2) NOT NULL COMMENT 'Cost + duration * hourly_rate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (learner_id) REFERENCES user(id) ON DELETE RESTRICT,
    FOREIGN KEY (tutor_id) REFERENCES user(id) ON DELETE RESTRICT,
    FOREIGN KEY (skill_id) REFERENCES skill(id) ON DELETE RESTRICT,
    INDEX idx_learner_id (learner_id),
    INDEX idx_tutor_id (tutor_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: Review
-- Description: Ratings and reviews for completed bookings
-- ============================================================================
CREATE TABLE review (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES booking(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLE: Message
-- Description: In-app messaging between learners and tutors
-- ============================================================================
CREATE TABLE message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = unread, 1 = read',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES user(id) ON DELETE CASCADE,
    INDEX idx_sender_id (sender_id),
    INDEX idx_receiver_id (receiver_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_is_read (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SAMPLE DATA: Skills Catalog
-- ============================================================================
INSERT INTO skill (name, category) VALUES
('Python', 'Programming'),
('JavaScript', 'Programming'),
('Java', 'Programming'),
('C++', 'Programming'),
('Vue.js', 'Programming'),
('Laravel', 'Programming'),
('REST API', 'Programming'),
('Calculus', 'Mathematics'),
('Linear Algebra', 'Mathematics'),
('Statistics', 'Mathematics'),
('English', 'Languages'),
('Mandarin', 'Languages'),
('Spanish', 'Languages'),
('Photoshop', 'Design'),
('UI/UX Design', 'Design'),
('Public Speaking', 'Soft skills'),
('Data Structures', 'Programming'),
('Algorithms', 'Programming'),
('MySQL', 'Programming'),
('OOP', 'Programming');

-- ============================================================================
-- SAMPLE DATA: Demo Users
-- ============================================================================
-- Password: password123 (hashed using bcrypt)
INSERT INTO user (name, email, password_hash, facility, bio, role) VALUES
('Faiz Amer', 'faiz@utm.edu.my', '$2y$10$YourHashedPasswordHere1', 'Computing', 'Year 3 FSKTM. Specialises in Vue.js, Laravel, and REST API design. 2 years industry internship.', 'Tutor'),
('Nur Rina', 'nur.rina@utm.edu.my', '$2y$10$YourHashedPasswordHere2', 'Computing', 'Year 4 FSKTM. Algorithms, Data Structures, and Python expert.', 'Tutor'),
('Ahmad Khairul', 'ahmad.khairul@utm.edu.my', '$2y$10$YourHashedPasswordHere3', 'Computing', 'Year 2 FC. OOP in Java and C++. Competed in ACM-ICPC regionals 2024.', 'Tutor'),
('Aisha Mohamed', 'aisha.m@utm.edu.my', '$2y$10$YourHashedPasswordHere4', 'Computing', 'First-year student looking for programming help.', 'Learner'),
('Hakim Reza', 'hakim.r@utm.edu.my', '$2y$10$YourHashedPasswordHere5', 'Engineering', 'Calculus and Physics tutor.', 'Tutor'),
('Admin User', 'admin@utm.edu.my', '$2y$10$YourHashedPasswordHere6', 'Administration', 'Platform administrator.', 'Admin');

-- ============================================================================
-- SAMPLE DATA: User Skills (Tutors)
-- ============================================================================
INSERT INTO user_skill (user_id, skill_id, hourly_rate, level) VALUES
(1, 5, 25.00, 'Advanced'),  -- Faiz: Vue.js
(1, 6, 25.00, 'Advanced'),  -- Faiz: Laravel
(1, 7, 25.00, 'Advanced'),  -- Faiz: REST API
(2, 18, 20.00, 'Advanced'), -- Nur Rina: Data Structures
(2, 17, 20.00, 'Advanced'), -- Nur Rina: Algorithms
(2, 1, 20.00, 'Advanced'),  -- Nur Rina: Python
(3, 3, 15.00, 'Advanced'),  -- Ahmad: Java
(3, 4, 15.00, 'Advanced'),  -- Ahmad: C++
(3, 20, 15.00, 'Advanced'), -- Ahmad: OOP
(5, 8, 18.00, 'Advanced');  -- Hakim: Calculus

-- ============================================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================================
-- Already defined in table creation statements above

COMMIT;