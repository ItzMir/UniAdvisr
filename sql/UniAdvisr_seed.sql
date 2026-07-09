START TRANSACTION; -- This ensures that all the following insertions are treated as a single unit of work. If any insertion fails, the entire transaction can be rolled back to maintain data integrity.

-- 1. ROLES
INSERT IGNORE INTO `roles` (`id`, `code`, `name`) VALUES
(1, 'student', 'Student'),
(2, 'faculty', 'Faculty'),
(3, 'admin', 'Admin');


-- 2. DEPARTMENTS
INSERT IGNORE INTO `departments` (`id`, `code`, `name`, `description`, `is_active`) VALUES
(1, 'CSE', 'Computer Science & Engineering', 'Programming, AI, systems, networking and software engineering.', 1),
(2, 'EEE', 'Electrical & Electronic Engineering', 'Power systems, electronics, communication and control.', 1),
(3, 'BBA', 'Business Administration', 'Management, finance, marketing and business strategy.', 1);


-- 3. USERS
INSERT IGNORE INTO `users` (`id`, `role_id`, `name`, `email`, `password_hash`, `phone`, `profile_photo`, `is_active`) VALUES
(1, 3, 'Admin User', 'admin@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801889000001', NULL, 1),
(2, 2, 'Dr. Mahmudul Hasan', 'mahmudul@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801889000002', NULL, 1),
(3, 2, 'Prof. Sahrirar Sarkar', 'sahrirar@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801889000003', NULL, 1),
(4, 2, 'Dr. Imran Hossain', 'imran@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801889000004', NULL, 1),
(5, 2, 'Abir Hasan', 'abir@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801889000005', NULL, 1),
(6, 1, 'Rafi Rahman', 'rafi.student@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801771111111', NULL, 1),
(7, 1, 'Nusrat Jahan', 'nusrat.student@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801772222222', NULL, 1),
(8, 1, 'Tanvir Ahmed', 'tanvir.student@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801773333333', NULL, 1),
(9, 1, 'Maya Islam', 'maya.student@uniadvisr.com', '$2y$12$AaKcYTS/KtdFev6h0xgCae1ZN//F4HKO4x81QSZXSDbtAIp3j6rfW', '+8801774444444', NULL, 1);


-- 4. STUDENT PROFILES
INSERT IGNORE INTO `student_profiles` (`user_id`, `student_code`, `department_id`, `program_name`, `batch_year`, `class_section`, `semester`, `cgpa`, `phone`, `address`, `profile_completion`) VALUES
(6, 'STU001', 1, 'BSc in CSE', 2023, 'A', '5th Semester', 3.45, '+8801771111111', 'Dhaka', 75),
(7, 'STU002', 2, 'BSc in EEE', 2023, 'B', '5th Semester', 3.20, '+8801772222222', 'Gazipur', 80),
(8, 'STU003', 1, 'BSc in CSE', 2022, 'A', '7th Semester', 3.10, '+8801773333333', 'Narayanganj', 60),
(9, 'STU004', 3, 'BBA', 2024, 'C', '3rd Semester', 3.60, '+8801774444444', 'Tangail', 55);


-- 5. FACULTY PROFILES
INSERT IGNORE INTO `faculty_profiles` (`user_id`, `employee_code`, `department_id`, `designation`, `specialization`, `office_location`, `office_phone`, `max_daily_sessions`, `bio`) VALUES
(2, 'FAC001', 1, 'Associate Professor', 'Artificial Intelligence', 'CSE Building, Room 304', '+8801550000001', 12, 'Advisor for AI, machine learning and project supervision.'),
(3, 'FAC002', 2, 'Professor', 'Power Systems', 'EEE Building, Room 212', '+8801550000002', 12, 'Advisor for power electronics and electrical systems.'),
(4, 'FAC003', 1, 'Assistant Professor', 'Data Science', 'CSE Building, Room 215', '+8801550000003', 12, 'Focuses on data analysis and software project mentoring.'),
(5, 'FAC004', 3, 'Lecturer', 'Finance', 'Business Faculty, Room 110', '+8801550000004', 12, 'Specialist in business analytics and finance guidance.');


-- 6. ADMIN PROFILES
INSERT IGNORE INTO `admin_profiles` (`user_id`, `job_title`, `office_phone`) VALUES
(1, 'System Administrator', '+8801559999999');


-- 7. STUDENT-FACULTY ASSIGNMENTS
INSERT IGNORE INTO `student_faculty_assignments` (`id`, `student_user_id`, `faculty_user_id`, `assigned_by_user_id`, `assignment_type`, `is_primary`, `start_date`, `end_date`, `notes`) VALUES
(1, 6, 2, 1, 'class_teacher', 1, CURDATE(), NULL, 'Primary class teacher'),
(2, 6, 4, 1, 'advisor', 0, CURDATE(), NULL, 'Secondary advisor for project support'),
(3, 7, 3, 1, 'class_teacher', 1, CURDATE(), NULL, 'Class teacher assignment'),
(4, 8, 2, 1, 'class_teacher', 1, CURDATE(), NULL, 'Primary assignment'),
(5, 8, 5, 1, 'advisor', 0, CURDATE(), NULL, 'Business counseling backup'),
(6, 9, 5, 1, 'class_teacher', 1, CURDATE(), NULL, 'Business faculty advisor');


-- 8. FACULTY AVAILABILITY RULES
INSERT IGNORE INTO `faculty_availability_rules` (`id`, `faculty_user_id`, `day_of_week`, `start_time`, `end_time`, `is_active`) VALUES
(1, 2, 1, '09:00:00', '12:00:00', 1),
(2, 2, 3, '14:00:00', '17:00:00', 1),
(3, 3, 0, '10:00:00', '13:00:00', 1),
(4, 4, 2, '09:30:00', '12:30:00', 1),
(5, 5, 4, '10:00:00', '13:00:00', 1);


-- 9. FACULTY UNAVAILABILITY
INSERT IGNORE INTO `faculty_unavailability` (`id`, `faculty_user_id`, `unavailable_date`, `start_time`, `end_time`, `reason`) VALUES
(1, 2, CURDATE(), '11:00:00', '12:00:00', 'Department meeting'),
(2, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '11:00:00', 'Conference session');


-- 10. APPOINTMENTS
INSERT IGNORE INTO `appointments`
(`id`, `student_user_id`, `faculty_user_id`, `created_by_user_id`, `appointment_date`, `preferred_section`, `topic`, `student_note`, `status`, `token_number`, `token_code`, `queue_position`, `duration_minutes`, `scheduled_start_at`, `scheduled_end_at`, `actual_start_at`, `actual_end_at`, `approved_by_user_id`, `approved_at`, `cancelled_by_user_id`, `cancelled_reason`, `completed_early`, `priority_flag`) VALUES
(1, 6, 2, 6, CURDATE(), 'A', 'Course selection guidance', 'Need help choosing electives for next semester.', 'approved', 1, 'T00000001', 1, 15, CONCAT(CURDATE(), ' 10:00:00'), CONCAT(CURDATE(), ' 10:15:00'), NULL, NULL, 2, CONCAT(CURDATE(), ' 08:30:00'), NULL, NULL, 0, 1),
(2, 6, 4, 6, CURDATE(), 'A', 'Research internship', 'Looking for a research internship topic.', 'completed', 2, 'T00000002', 2, 15, CONCAT(CURDATE(), ' 10:15:00'), CONCAT(CURDATE(), ' 10:30:00'), CONCAT(CURDATE(), ' 10:15:00'), CONCAT(CURDATE(), ' 10:23:00'), 4, CONCAT(CURDATE(), ' 09:00:00'), NULL, NULL, 1, 0),
(3, 7, 3, 7, CURDATE(), 'B', 'Thesis topic discussion', 'Need to finalize my thesis idea.', 'pending', 1, 'T00000003', 1, 15, CONCAT(CURDATE(), ' 11:00:00'), CONCAT(CURDATE(), ' 11:15:00'), NULL, NULL, NULL, NULL, NULL, NULL, 0, 0),
(4, 8, 2, 8, CURDATE(), 'A', 'Project assistance', 'Project milestones and timeline review.', 'approved', 3, 'T00000004', 3, 15, CONCAT(CURDATE(), ' 10:30:00'), CONCAT(CURDATE(), ' 10:45:00'), NULL, NULL, 2, CONCAT(CURDATE(), ' 08:45:00'), NULL, NULL, 0, 0),
(5, 9, 5, 9, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'C', 'Section change request', 'May need section transfer guidance.', 'pending', NULL, 'T00000005', NULL, 15, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0),
(6, 6, 2, 6, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'A', 'Internship planning', 'Preparing for summer internship applications.', 'approved', 1, 'T00000006', 1, 15, CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 2 DAY), ' 09:15:00'), NULL, NULL, 2, CONCAT(CURDATE(), ' 09:10:00'), NULL, NULL, 0, 0);


-- 11. APPOINTMENT DOCUMENTS
INSERT IGNORE INTO `appointment_documents` (`id`, `appointment_id`, `uploaded_by_user_id`, `document_type`, `original_name`, `stored_name`, `file_path`, `mime_type`, `file_size`) VALUES
(1, 1, 6, 'preferred_section', 'preferred-section.pdf', 'doc_001_preferred_section.pdf', 'uploads/attachments/doc_001_preferred_section.pdf', 'application/pdf', 182400),
(2, 3, 7, 'supporting_document', 'thesis-notes.pdf', 'doc_002_thesis_notes.pdf', 'uploads/attachments/doc_002_thesis_notes.pdf', 'application/pdf', 248200);


-- 12. APPOINTMENT STATUS HISTORY
INSERT IGNORE INTO `appointment_status_history` (`id`, `appointment_id`, `changed_by_user_id`, `old_status`, `new_status`, `reason`) VALUES
(1, 1, 2, 'pending', 'approved', 'Approved by class teacher'),
(2, 2, 4, 'approved', 'in_progress', 'Student arrived on time'),
(3, 2, 4, 'in_progress', 'completed', 'Finished early and next student called'),
(4, 4, 2, 'pending', 'approved', 'Slot confirmed');


-- 13. ESSAYS
INSERT IGNORE INTO `essays` (`id`, `student_user_id`, `reviewed_by_user_id`, `title`, `description`, `file_path`, `status`, `review_comments`, `submitted_at`, `reviewed_at`) VALUES
(1, 6, 4, 'JavaScript Introduction', 'Introductory essay for web programming.', 'uploads/essays/js_intro.pdf', 'pending_review', NULL, NOW(), NULL),
(2, 7, 3, 'Power Systems Report', 'Short report on electrical power systems.', 'uploads/essays/power_report.pdf', 'reviewed', 'Good structure. Add more references.', NOW(), NOW()),
(3, 8, NULL, 'Project Proposal', 'Initial project proposal draft.', 'uploads/essays/project_proposal.pdf', 'revision_requested', 'Please improve the methodology section.', NOW(), NULL);


-- 14. ESSAY REVIEWS
INSERT IGNORE INTO `essay_reviews` (`id`, `essay_id`, `reviewed_by_user_id`, `status`, `comments`) VALUES
(1, 2, 3, 'reviewed', 'Good structure. Add more references.'),
(2, 3, 2, 'revision_requested', 'Please improve the methodology section.');


-- 15. NOTIFICATIONS
INSERT IGNORE INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `read_at`) VALUES
(1, 6, 'appointment', 'Your appointment with Dr. Mahmudul Hasan is confirmed for today at 10:00 AM.', 0, NULL),
(2, 6, 'token', 'Your token T001 with Dr. Mahmudul Hasan is scheduled for today.', 0, NULL),
(3, 6, 'essay', 'Your essay "JavaScript Introduction" is pending review by Dr. Imran Hossain.', 0, NULL),
(4, 7, 'appointment', 'Your thesis discussion appointment is pending approval.', 0, NULL),
(5, 1, 'system', 'New faculty accounts have been created successfully.', 1, NOW());


-- 16. ACTIVITY LOGS
INSERT IGNORE INTO `activity_logs` (`id`, `user_id`, `entity_type`, `entity_id`, `action`, `details`, `ip_address`, `user_agent`) VALUES
(1, 1, 'faculty', 2, 'create_faculty_account', 'Added faculty member Dr. Mahmudul Hasan', '127.0.0.1', 'Seed script'),
(2, 1, 'assignment', 1, 'assign_student_faculty', 'Assigned students to faculty members', '127.0.0.1', 'Seed script'),
(3, 6, 'appointment', 1, 'create_booking', 'Student created a counseling request', '127.0.0.1', 'Seed script');


-- 17. SYSTEM SETTINGS
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('default_session_minutes', '15'),
('queue_call_mode', 'strict_token_order'),
('allow_early_call_resequence', '1'),
('max_file_upload_mb', '10'),
('brand_name', 'UniAdvisr');

COMMIT; -- saves all the above changes to the database