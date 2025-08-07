
CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `user_type_id` tinyint UNSIGNED NOT NULL,
  `email` varchar(255)  NOT NULL,
  `password_hash` varchar(255)  NOT NULL,
  `first_name` varchar(50)  DEFAULT NULL,
  `last_name` varchar(50)  DEFAULT NULL,
  `company_name` varchar(100)  DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `base_lang` varchar(5)  NOT NULL DEFAULT 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Users table (common base for all user types)
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_type_id TINYINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    company_name VARCHAR(100),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login TIMESTAMP NULL DEFAULT NULL,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (user_type_id) REFERENCES user_types(user_type_id),
    INDEX idx_user_type (user_type_id),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Companies table (for recruiters)
CREATE TABLE IF NOT EXISTS companies (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    industry VARCHAR(50),
    size VARCHAR(20),
    website VARCHAR(255),
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null
) ENGINE=InnoDB;

-- Recruiter profiles (extends users)
CREATE TABLE IF NOT EXISTS recruiter_profiles (
    id INT UNSIGNED PRIMARY KEY,
    company_id INT UNSIGNED,
    job_title VARCHAR(50),
    department VARCHAR(50),
    phone VARCHAR(20),
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (recruiter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY KEY (company_id) REFERENCES companies(company_id),
    INDEX idx_company (company_id)
) ENGINE=InnoDB;

-- Question categories
CREATE TABLE IF NOT EXISTS question_categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    parent_category_id INT UNSIGNED,
    created_by INT UNSIGNED,
    is_system BOOLEAN NOT NULL DEFAULT FALSE,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (parent_category_id) REFERENCES question_categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_parent_category (parent_category_id)
) ENGINE=InnoDB;

-- Question difficulty levels
CREATE TABLE IF NOT EXISTS difficulty_levels (
    id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL UNIQUE,
    description VARCHAR(100),
    weight TINYINT UNSIGNED NOT NULL,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null
) ENGINE=InnoDB;

INSERT INTO difficulty_levels (name, description, weight) VALUES 
('Beginner', 'Basic knowledge questions', 1),
('Intermediate', 'Moderate difficulty questions', 2),
('Advanced', 'Challenging questions for experts', 3),
('Expert', 'Very difficult questions', 4);

-- Question types
CREATE TABLE IF NOT EXISTS question_types (
    id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL UNIQUE,
    description VARCHAR(100),
    has_options BOOLEAN NOT NULL DEFAULT FALSE,
    has_text_answer BOOLEAN NOT NULL DEFAULT FALSE,
    is_scorable BOOLEAN NOT NULL DEFAULT TRUE,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null
) ENGINE=InnoDB;

INSERT INTO question_types (name, description, has_options, has_text_answer, is_scorable) VALUES 
('MCQ_Single', 'Multiple Choice - Single Answer', TRUE, FALSE, TRUE),
('MCQ_Multiple', 'Multiple Choice - Multiple Answers', TRUE, FALSE, TRUE),
('True_False', 'True or False', TRUE, FALSE, TRUE),
('Short_Answer', 'Short text answer', FALSE, TRUE, FALSE),
('Essay', 'Long form text answer', FALSE, TRUE, FALSE),
('Code_Snippet', 'Programming/code question', FALSE, TRUE, FALSE),
('Fill_Blank', 'Fill in the blank', FALSE, TRUE, TRUE);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    question_text TEXT NOT NULL,
    type_id TINYINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED,
    difficulty_id TINYINT UNSIGNED,
    created_by INT UNSIGNED NOT NULL,
    is_public BOOLEAN NOT NULL DEFAULT FALSE,
    is_ai_generated BOOLEAN NOT NULL DEFAULT FALSE,
    explanation TEXT,
    time_limit_seconds SMALLINT UNSIGNED,
    max_score TINYINT UNSIGNED NOT NULL DEFAULT 1,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (type_id) REFERENCES question_types(type_id),
    FOREIGN KEY KEY (category_id) REFERENCES question_categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY KEY (difficulty_id) REFERENCES difficulty_levels(difficulty_id) ON DELETE SET NULL,
    FOREIGN KEY KEY (created_by) REFERENCES users(user_id),
    INDEX idx_category (category_id),
    INDEX idx_difficulty (difficulty_id),
    INDEX idx_creator (created_by),
    FULLTEXT INDEX ft_question_text (question_text)
) ENGINE=InnoDB;

-- Question options (for MCQ, True/False, etc.)
CREATE TABLE IF NOT EXISTS question_options (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    question_id INT UNSIGNED NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    option_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    explanation TEXT,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    INDEX idx_question (question_id)
) ENGINE=InnoDB;

-- Tags for questions
CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(100),
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null
) ENGINE=InnoDB;

-- Question-tag mapping
CREATE TABLE IF NOT EXISTS question_tags (
    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    tag_id INT UNSIGNED NOT NULL,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null
    
    FOREIGN KEY KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    FOREIGN KEY KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- Tests/Assessments
CREATE TABLE IF NOT EXISTS tests (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT UNSIGNED NOT NULL,
    time_limit_minutes SMALLINT UNSIGNED,
    is_public BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    pass_threshold TINYINT UNSIGNED,
    show_score BOOLEAN NOT NULL DEFAULT TRUE,
    show_answers BOOLEAN NOT NULL DEFAULT FALSE,
    randomize_questions BOOLEAN NOT NULL DEFAULT FALSE,
    allow_backtracking BOOLEAN NOT NULL DEFAULT TRUE,
    instructions TEXT,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (created_by) REFERENCES users(user_id),
    INDEX idx_creator (created_by),
    FULLTEXT INDEX ft_test_title (title)
) ENGINE=InnoDB;

-- Test questions mapping
CREATE TABLE IF NOT EXISTS test_questions (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    test_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    question_order INT UNSIGNED NOT NULL DEFAULT 0,
    section_name VARCHAR(50),
    weight TINYINT UNSIGNED NOT NULL DEFAULT 1,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
    FOREIGN KEY KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE,
    UNIQUE KEY uk_test_question (test_id, question_id),
    INDEX idx_test (test_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB;

-- Test invitations
CREATE TABLE IF NOT EXISTS test_invitations (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    test_id INT UNSIGNED NOT NULL,
    invited_by INT UNSIGNED NOT NULL,
    invitee_email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    status ENUM('sent', 'opened', 'started', 'completed', 'expired') NOT NULL DEFAULT 'sent',
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    first_opened_at TIMESTAMP NULL DEFAULT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    custom_message TEXT,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (test_id) REFERENCES tests(test_id) ON DELETE CASCADE,
    FOREIGN KEY KEY (invited_by) REFERENCES users(user_id),
    INDEX idx_test (test_id),
    INDEX idx_inviter (invited_by),
    INDEX idx_token (token),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Test attempts
CREATE TABLE IF NOT EXISTS test_attempts (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    test_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    invitation_id INT UNSIGNED,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    time_spent_seconds INT UNSIGNED,
    score DECIMAL(5,2),
    max_score DECIMAL(5,2) NOT NULL,
    percentage DECIMAL(5,2),
    is_passed BOOLEAN,
    ip_address VARCHAR(45),
    user_agent TEXT,
    proctoring_flags JSON,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (test_id) REFERENCES tests(test_id),
    FOREIGN KEY KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY KEY (invitation_id) REFERENCES test_invitations(invitation_id) ON DELETE SET NULL,
    INDEX idx_test (test_id),
    INDEX idx_user (user_id),
    INDEX idx_invitation (invitation_id)
) ENGINE=InnoDB;

-- Question responses
CREATE TABLE IF NOT EXISTS question_responses (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    question_type_id TINYINT UNSIGNED NOT NULL,
    response_text TEXT,
    response_options JSON,
    is_correct BOOLEAN,
    score DECIMAL(5,2),
    max_score DECIMAL(5,2) NOT NULL,
    time_spent_seconds INT UNSIGNED,
    feedback TEXT,
    graded_by INT UNSIGNED,
    graded_at TIMESTAMP NULL DEFAULT NULL,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (attempt_id) REFERENCES test_attempts(attempt_id) ON DELETE CASCADE,
    FOREIGN KEY KEY (question_id) REFERENCES questions(question_id),
    FOREIGN KEY KEY (question_type_id) REFERENCES question_types(type_id),
    FOREIGN KEY KEY (graded_by) REFERENCES users(user_id),
    INDEX idx_attempt (attempt_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB;

-- Selected options for MCQ responses
CREATE TABLE IF NOT EXISTS response_selected_options (
    id INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
    option_id INT UNSIGNED NOT NULL,
    is_correct BOOLEAN NOT NULL,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null

    FOREIGN KEY KEY (response_id) REFERENCES question_responses(response_id) ON DELETE CASCADE,
    FOREIGN KEY KEY (option_id) REFERENCES question_options(option_id)
) ENGINE=InnoDB;

-- AI generation requests
CREATE TABLE IF NOT EXISTS ai_generation_requests (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    requested_by INT UNSIGNED NOT NULL,
    request_type ENUM('question', 'answer', 'explanation', 'feedback') NOT NULL,
    parameters JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    completed_at TIMESTAMP NULL DEFAULT NULL,
    result_count INT UNSIGNED,
    error_message TEXT,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (requested_by) REFERENCES users(user_id),
    INDEX idx_requester (requested_by),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- AI generated content
CREATE TABLE IF NOT EXISTS ai_generated_content (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED,
    content_type ENUM('question', 'option', 'explanation', 'feedback') NOT NULL,
    content_text TEXT NOT NULL,
    metadata JSON,
    is_used BOOLEAN NOT NULL DEFAULT FALSE,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (request_id) REFERENCES ai_generation_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY KEY (question_id) REFERENCES questions(question_id) ON DELETE SET NULL,
    INDEX idx_request (request_id),
    INDEX idx_question (question_id)
) ENGINE=InnoDB;

-- System notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    notification_type ENUM('test_invite', 'test_completed', 'ai_generation', 'system') NOT NULL,
    related_id INT UNSIGNED,
    read_at TIMESTAMP NULL DEFAULT NULL,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read_status (is_read)
) ENGINE=InnoDB;

-- User activity logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    base_lang varchar(5) not null default 'en',
    active varchar(5) not null default '1',
    created_at TIMESTAMP default current_timestamp,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME default null,
    FOREIGN KEY KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_activity_type (activity_type)
) ENGINE=InnoDB;

CREATE TABLE `cheating_data` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL comment "user type will be candidate only",
  `test_id` int NOT NULL,
  `process_time` timestamp  NOT NULL,
  `event_type` varchar(200)  DEFAULT NULL,
  `confidence` float(10,2)  DEFAULT NULL,
  `duration_seconds` int  DEFAULT NULL,
  `face_detected` varchar(5) DEFAULT NULL comment "Yes / No",
  `Screenshot_Path` varchar(200)  NOT NULL DEFAULT 'en',
  `base_lang` varchar(5) not null default 'en',
  `active` varchar(5)  NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB ;
