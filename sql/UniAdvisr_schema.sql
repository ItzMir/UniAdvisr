-- UniAdvisr Database Schema

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0; 
-- Drop existing tables if they exist
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `appointment_status_history`;
DROP TABLE IF EXISTS `appointment_documents`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `faculty_unavailability`;
DROP TABLE IF EXISTS `faculty_availability_rules`;
DROP TABLE IF EXISTS `student_faculty_assignments`;
DROP TABLE IF EXISTS `essay_reviews`;
DROP TABLE IF EXISTS `essays`;
DROP TABLE IF EXISTS `admin_profiles`;
DROP TABLE IF EXISTS `faculty_profiles`;
DROP TABLE IF EXISTS `student_profiles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `system_settings`;

--1. roles table
CREATE TABLE `roles` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; -- innodb engine is used for fk relationship support, utf8mb4 charset is used for better unicode support, utf8mb4_unicode_ci collation is used for case-insensitive comparisons

--2. departments table
CREATE TABLE `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_departments_code` (`code`),
  UNIQUE KEY `uq_departments_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--3. users table
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` SMALLINT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(25) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role_active` (`role_id`, `is_active`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--4. student_profiles table
CREATE TABLE `student_profiles` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `student_code` VARCHAR(30) NOT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `program_name` VARCHAR(120) DEFAULT NULL,
  `batch_year` YEAR DEFAULT NULL,
  `class_section` VARCHAR(20) DEFAULT NULL,
  `semester` VARCHAR(20) DEFAULT NULL,
  `cgpa` DECIMAL(4,2) DEFAULT NULL,
  `phone` VARCHAR(25) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `profile_completion` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_student_code` (`student_code`),
  KEY `idx_student_department` (`department_id`),
  CONSTRAINT `fk_student_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_student_profiles_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--5. faculty_profiles table
CREATE TABLE `faculty_profiles` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `employee_code` VARCHAR(30) NOT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `designation` VARCHAR(120) DEFAULT NULL,
  `specialization` VARCHAR(255) DEFAULT NULL,
  `office_location` VARCHAR(120) DEFAULT NULL,
  `office_phone` VARCHAR(25) DEFAULT NULL,
  `max_daily_sessions` SMALLINT UNSIGNED NOT NULL DEFAULT 12,
  `bio` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_faculty_employee_code` (`employee_code`),
  KEY `idx_faculty_department` (`department_id`),
  CONSTRAINT `fk_faculty_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_faculty_profiles_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--6. admin_profiles table
CREATE TABLE `admin_profiles` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `job_title` VARCHAR(120) DEFAULT 'Administrator',
  `office_phone` VARCHAR(25) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_admin_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--7. student_faculty_assignments table
CREATE TABLE `student_faculty_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_user_id` BIGINT UNSIGNED NOT NULL,
  `faculty_user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_by_user_id` BIGINT UNSIGNED NOT NULL,
  `assignment_type` ENUM('class_teacher','advisor','temporary') NOT NULL DEFAULT 'advisor',
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_faculty_active` (`student_user_id`, `faculty_user_id`, `start_date`),
  KEY `idx_assignment_faculty_primary` (`faculty_user_id`, `is_primary`),
  KEY `idx_assignment_student` (`student_user_id`, `is_primary`),
  CONSTRAINT `fk_assignment_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_assignment_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_assignment_admin` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--8. faculty_availability_rules table
CREATE TABLE `faculty_availability_rules` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `faculty_user_id` BIGINT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '0=Sunday ... 6=Saturday',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faculty_weekly_rule` (`faculty_user_id`, `day_of_week`, `start_time`, `end_time`),
  KEY `idx_faculty_availability_faculty` (`faculty_user_id`, `day_of_week`, `is_active`),
  CONSTRAINT `fk_faculty_availability_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--9. faculty_unavailability table
CREATE TABLE `faculty_unavailability` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `faculty_user_id` BIGINT UNSIGNED NOT NULL,
  `unavailable_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_unavailability_faculty_date` (`faculty_user_id`, `unavailable_date`, `start_time`),
  CONSTRAINT `fk_faculty_unavailability_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--10. appointments table
CREATE TABLE `appointments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_user_id` BIGINT UNSIGNED NOT NULL,
  `faculty_user_id` BIGINT UNSIGNED NOT NULL,
  `created_by_user_id` BIGINT UNSIGNED NOT NULL,
  `appointment_date` DATE NOT NULL,
  `preferred_section` VARCHAR(50) DEFAULT NULL,
  `topic` VARCHAR(255) NOT NULL,
  `student_note` TEXT DEFAULT NULL,
  `status` ENUM('pending','approved','declined','cancelled_by_student','cancelled_by_faculty','in_progress','completed','no_show','rescheduled') NOT NULL DEFAULT 'pending',
  `token_number` INT UNSIGNED DEFAULT NULL,
  `token_code` VARCHAR(12) NOT NULL,
  `queue_position` INT UNSIGNED DEFAULT NULL,
  `duration_minutes` TINYINT UNSIGNED NOT NULL DEFAULT 15,
  `scheduled_start_at` DATETIME DEFAULT NULL,
  `scheduled_end_at` DATETIME DEFAULT NULL,
  `actual_start_at` DATETIME DEFAULT NULL,
  `actual_end_at` DATETIME DEFAULT NULL,
  `approved_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `cancelled_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `cancelled_reason` VARCHAR(255) DEFAULT NULL,
  `completed_early` TINYINT(1) NOT NULL DEFAULT 0,
  `priority_flag` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faculty_date_token` (`faculty_user_id`, `appointment_date`, `token_number`),
  UNIQUE KEY `uq_token_code` (`token_code`),
  UNIQUE KEY `uq_faculty_date_queue` (`faculty_user_id`, `appointment_date`, `queue_position`),
  KEY `idx_student_appointment_date` (`student_user_id`, `appointment_date`, `status`),
  KEY `idx_faculty_appointment_status` (`faculty_user_id`, `appointment_date`, `status`, `scheduled_start_at`),
  KEY `idx_status_time` (`status`, `scheduled_start_at`),
  KEY `idx_token_number` (`faculty_user_id`, `appointment_date`, `token_number`),
  KEY `idx_queue_position` (`faculty_user_id`, `appointment_date`, `queue_position`),
  CONSTRAINT `fk_appointment_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_faculty` FOREIGN KEY (`faculty_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_appointment_approver` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_appointment_canceller` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--11. appointment_documents table
CREATE TABLE `appointment_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED DEFAULT NULL,
  `uploaded_by_user_id` BIGINT UNSIGNED NOT NULL,
  `document_type` ENUM('preferred_section','supporting_document','essay','other') NOT NULL DEFAULT 'supporting_document',
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `file_size` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_document_appointment` (`appointment_id`, `document_type`),
  KEY `idx_document_uploader` (`uploaded_by_user_id`, `created_at`),
  CONSTRAINT `fk_document_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_document_uploader` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--12. appointment_status_history table
CREATE TABLE `appointment_status_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appointment_id` BIGINT UNSIGNED NOT NULL,
  `changed_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `old_status` VARCHAR(40) DEFAULT NULL,
  `new_status` VARCHAR(40) NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_history_appointment` (`appointment_id`, `created_at`),
  CONSTRAINT `fk_history_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--13. essays table
CREATE TABLE `essays` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_user_id` BIGINT UNSIGNED NOT NULL,
  `reviewed_by_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `status` ENUM('draft','submitted','pending_review','reviewed','revision_requested','archived') NOT NULL DEFAULT 'submitted',
  `review_comments` TEXT DEFAULT NULL,
  `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_essays_student_status` (`student_user_id`, `status`, `submitted_at`),
  KEY `idx_essays_reviewer` (`reviewed_by_user_id`, `status`),
  CONSTRAINT `fk_essay_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_essay_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--14. essay_reviews table
CREATE TABLE `essay_reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `essay_id` BIGINT UNSIGNED NOT NULL,
  `reviewed_by_user_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('reviewed','revision_requested') NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_essay_reviews_essay` (`essay_id`, `created_at`),
  CONSTRAINT `fk_essay_reviews_essay` FOREIGN KEY (`essay_id`) REFERENCES `essays` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_essay_reviews_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--15. notifications table
CREATE TABLE `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(40) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_read` (`user_id`, `is_read`, `created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--16. activity_logs table
CREATE TABLE `activity_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` BIGINT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(120) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_user_created` (`user_id`, `created_at`),
  KEY `idx_activity_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--17. system_settings table
CREATE TABLE `system_settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1; -- Re-enable foreign key checks after creating all tables