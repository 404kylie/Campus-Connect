-- Sample data for CampusConnect database

-- Insert sample admin data
INSERT INTO admin (name, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert sample students
INSERT INTO student (studentID, email, password, name, department) VALUES 
(1, 'john.doe@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', 'Computer Science'),
(2, 'jane.smith@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', 'Engineering'),
(3, 'bob.wilson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Wilson', 'Business'),
(4, 'alice.brown@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Brown', 'Computer Science'),
(5, 'charlie.davis@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Charlie Davis', 'Engineering');

-- Insert sample officers (with department representatives)
INSERT INTO officer (officerID, email, password, name, department, isRepresentative) VALUES 
(1, 'cs.rep@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sarah Johnson', 'Computer Science', TRUE),
(2, 'eng.rep@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Michael Chen', 'Engineering', TRUE),
(3, 'bus.rep@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Lisa Rodriguez', 'Business', TRUE),
(4,'cs.officer@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mark Thompson', 'Computer Science', FALSE),
(5,'eng.officer@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily White', 'Engineering', FALSE);

-- Insert sample announcements
INSERT INTO announcement (officerID, date, time, subject, content, department) VALUES 
(1, '2024-06-01', '09:00:00', 'Welcome to New Semester', 'Welcome back students! We hope you have a great semester ahead.', 'Computer Science'),
(1, '2024-06-02', '14:30:00', 'Lab Schedule Update', 'Please note that the programming lab schedule has been updated. Check the portal for new timings.', 'Computer Science'),
(2, '2024-06-01', '10:15:00', 'Engineering Workshop', 'Join usa for a hands-on workshop on modern engineering practices this Friday.', 'Engineering'),
(2, '2024-06-03', '16:00:00', 'Project Submission Deadline', 'Reminder: All final year projects must be submitted by June 15th, 2024.', 'Engineering'),
(3, '2024-06-02', '11:00:00', 'Business Seminar', 'Guest speaker from Fortune 500 company will be presenting on Monday.', 'Business'),
(1, '2024-06-04', '13:20:00', 'General Notice', 'Library will be closed for maintenance on June 8th, 2024.', NULL);

-- Insert sample chat messages (with sender_type)
INSERT INTO chat (officerID, studentID, date, time, message, sender_type) VALUES 
-- Conversation between John Doe (student) and Dr. Sarah Johnson (CS rep)
(1, 1, '2024-06-01', '10:30:00', 'Hello Dr. Johnson, I have a question about the new lab schedule.', 'student'),
(1, 1, '2024-06-01', '10:35:00', 'Hi John! Sure, what would you like to know about the lab schedule?', 'officer'),
(1, 1, '2024-06-01', '10:37:00', 'Are the lab sessions still 2 hours long or have they been extended?', 'student'),
(1, 1, '2024-06-01', '10:40:00', 'They remain 2 hours long, but we have added more sessions throughout the week.', 'officer'),
(1, 1, '2024-06-01', '10:42:00', 'That\'s great! Thank you for the clarification.', 'student'),

-- Conversation between Jane Smith (student) and Prof. Michael Chen (Engineering rep)
(2, 2, '2024-06-02', '14:15:00', 'Professor Chen, I need help with my project proposal.', 'student'),
(2, 2, '2024-06-02', '14:20:00', 'Of course Jane. What specific area do you need assistance with?', 'officer'),
(2, 2, '2024-06-02', '14:22:00', 'I\'m struggling with the technical feasibility section.', 'student'),
(2, 2, '2024-06-02', '14:25:00', 'Let\'s schedule a meeting this week to discuss it in detail. Are you free Thursday afternoon?', 'officer'),
(2, 2, '2024-06-02', '14:27:00', 'Yes, Thursday afternoon works perfectly. Thank you!', 'student'),

-- Conversation between Bob Wilson (student) and Dr. Lisa Rodriguez (Business rep)
(3, 3, '2024-06-03', '09:45:00', 'Dr. Rodriguez, I missed the business seminar announcement. Can you provide details?', 'student'),
(3, 3, '2024-06-03', '09:50:00', 'Hi Bob! The seminar is on Monday at 2 PM in the main auditorium. The speaker is from Microsoft.', 'officer'),
(3, 3, '2024-06-03', '09:52:00', 'Excellent! Is registration required?', 'student'),
(3, 3, '2024-06-03', '09:55:00', 'Yes, please register through the student portal by Sunday evening.', 'officer'),

-- More recent conversation between Alice Brown (student) and Dr. Sarah Johnson (CS rep)
(1, 4, '2024-06-04', '16:30:00', 'Hi Dr. Johnson, I have a question about the upcoming exam schedule.', 'student'),
(1, 4, '2024-06-04', '16:35:00', 'Hello Alice! The exam schedule will be released next week. Keep an eye on announcements.', 'officer'),

-- Conversation between Charlie Davis (student) and Prof. Michael Chen (Engineering rep)
(2, 5, '2024-06-05', '11:20:00', 'Professor, when is the workshop you mentioned in the announcement?', 'student'),
(2, 5, '2024-06-05', '11:25:00', 'Hi Charlie! The workshop is this Friday from 2 PM to 5 PM in Lab 3.', 'officer'),
(2, 5, '2024-06-05', '11:27:00', 'Perfect, I\'ll be there. Should I bring anything specific?', 'student'),
(2, 5, '2024-06-05', '11:30:00', 'Just bring your laptop and a notebook. We\'ll provide all the materials.', 'officer');