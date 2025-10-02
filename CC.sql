-- MySQL Script for Campus Connect Database

-- Step 1: Create the Database
CREATE DATABASE IF NOT EXISTS campus_connect;
USE campus_connect;

-- Step 2: Create Tables

-- Universities Table
CREATE TABLE IF NOT EXISTS universities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Store hashed passwords (bcrypt)
    university_id INT,
    student_id VARCHAR(50),
    batch YEAR,
    profile_picture VARCHAR(255) DEFAULT '/assets/user_profile.jpg',
    cover_photo VARCHAR(255) DEFAULT '/assets/default_cover.jpg',
    bio TEXT,
    interests TEXT,
    linkedin VARCHAR(255),
    twitter VARCHAR(255),
    role ENUM('student', 'admin') DEFAULT 'student',
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    id_picture VARCHAR(255) DEFAULT '/assets/default_id.jpg', -- Path to student ID image for signup review
    approved_by INT, -- Tracks which admin approved/rejected the user
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Trigger to enforce student data constraint
DELIMITER //
CREATE TRIGGER check_student_data_before_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'student' AND (NEW.university_id IS NULL OR NEW.student_id IS NULL OR NEW.batch IS NULL) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Students must have university_id, student_id, and batch.';
    END IF;
END;
//

CREATE TRIGGER check_student_data_before_update
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'student' AND (NEW.university_id IS NULL OR NEW.student_id IS NULL OR NEW.batch IS NULL) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Students must have university_id, student_id, and batch.';
    END IF;
END;
//
DELIMITER ;

-- Groups Table
CREATE TABLE IF NOT EXISTS groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('study', 'sports', 'hobbies', 'clubs', 'professional'),
    privacy ENUM('public', 'private') DEFAULT 'public',
    image VARCHAR(255) DEFAULT '/assets/default_group.jpg',
    creator_id INT,
    members_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Group Members Table
CREATE TABLE IF NOT EXISTS group_members (
    group_id INT,
    user_id INT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

--Group Messages Table

CREATE TABLE IF NOT EXISTS group_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT,
    file_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Events Table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME,
    category ENUM('academic', 'social', 'sports', 'cultural', 'professional'),
    location VARCHAR(255),
    attendance ENUM('in-person', 'virtual') DEFAULT 'in-person',
    organizer VARCHAR(255),
    image VARCHAR(255) DEFAULT '/assets/default_event.jpg',
    creator_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Event RSVPs Table
CREATE TABLE IF NOT EXISTS event_rsvps (
    event_id INT,
    user_id INT,
    rsvp_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Posts Table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages Table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    message TEXT NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seen BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);



-- Connections Table
CREATE TABLE IF NOT EXISTS connections (
    user_id1 INT,
    user_id2 INT,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id1, user_id2),
    FOREIGN KEY (user_id1) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id2) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('like', 'comment', 'connection_request', 'event_rsvp', 'group_join', 'message', 'signup_status'),
    content TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    seen BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Reports Table
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT,
    reported_id INT,
    type ENUM('user', 'group', 'event', 'post', 'message'),
    reason TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(50) NOT NULL,
    user_id INT,
    details TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Step 3: Indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_university_id ON users(university_id);
CREATE INDEX idx_groups_name ON groups(name);
CREATE INDEX idx_events_date ON events(date);
CREATE INDEX idx_messages_sender_receiver ON messages(sender_id, receiver_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_reports_type_status ON reports(type, status);

-- Step 4: Triggers

-- Update group member count
DELIMITER //
CREATE TRIGGER update_group_members_count_after_insert
AFTER INSERT ON group_members
FOR EACH ROW
BEGIN
    UPDATE groups SET members_count = members_count + 1 WHERE id = NEW.group_id;
END;
//

CREATE TRIGGER update_group_members_count_after_delete
AFTER DELETE ON group_members
FOR EACH ROW
BEGIN
    UPDATE groups SET members_count = members_count - 1 WHERE id = OLD.group_id;
END;
//

-- Log user approval/rejection
CREATE TRIGGER log_user_approval
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.status = 'pending' AND NEW.status IN ('approved','rejected') THEN
        INSERT INTO audit_logs (admin_id, action, user_id, details, timestamp)
        VALUES (
            NEW.approved_by,
            CASE NEW.status
                WHEN 'approved' THEN 'approve_user'
                WHEN 'rejected' THEN 'reject_user'
            END,
            NEW.id,
            CONCAT('user ', NEW.full_name, ' status changed to ', NEW.status),
            CURRENT_TIMESTAMP
        );
    END IF;
END;
//
DELIMITER ;

-- Step 5: Sample Data Inserts

-- Universities
INSERT INTO universities (name, code) VALUES
('Hilcoe', 'HILCOE'),
('Addis Ababa University', 'AAU'),
('Adama Science and Technology University', 'ASTU')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Admin User
INSERT INTO users (full_name, email, password, role, status) VALUES
('Admin User', 'admin@campusconnect.com', '$2b$12$z7Q8zY5X8Z1Y2X3Y4Z5Y6.u7Q8zY9A0B1C2D3E4F5G6H7I8J9K0L', 'admin', 'approved')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password = VALUES(password),
    role = VALUES(role),
    status = VALUES(status);

-- Sample Users
INSERT INTO users (full_name, email, password, university_id, student_id, batch, status, id_picture, role) VALUES
('Tamirat Negus', 'Tamirat@example.com', '$2b$12$examplehashedpassword12345', 1, 'S123456', 2024, 'pending', '/assets/tamirat_id.jpg', 'student'),
('Kirubel Aber', 'Kirkubel@example.com', '$2b$12$examplehashedpassword67890', 2, 'S789012', 2023, 'pending', '/assets/kirubel_id.jpg', 'student'),
('Mike Fililmon', 'mike@example.com', '$2b$12$examplehashedpassword11223', 3, 'S345678', 2024, 'pending', '/assets/mike_id.jpg', 'student'),
('Biruk Aman', 'Biruk@example.com', '$2b$12$examplehashedpassword44556', 1, 'S901234', 2023, 'approved', '/assets/biruk_id.jpg', 'student')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password = VALUES(password),
    university_id = VALUES(university_id),
    student_id = VALUES(student_id),
    batch = VALUES(batch),
    status = VALUES(status),
    id_picture = VALUES(id_picture),
    role = VALUES(role);

-- Sample Groups
INSERT INTO groups (name, description, category, privacy, creator_id, image) VALUES
('Coding Club', 'Learn to code together!', 'study', 'public', 1, '/assets/group4.jpg'),
('Debate Team', 'Debate and discuss topics.', 'clubs', 'private', 1, '/assets/studygroups.jpg')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    category = VALUES(category),
    privacy = VALUES(privacy),
    image = VALUES(image);

-- Sample Group Members
INSERT INTO group_members (group_id, user_id) VALUES
(1, 4),
(2, 4)
ON DUPLICATE KEY UPDATE joined_at = CURRENT_TIMESTAMP;

-- Sample Events
INSERT INTO events (title, description, date, time, category, location, attendance, organizer, creator_id, image) VALUES
('Hackathon 2025', 'Annual coding hackathon.', '2025-09-15', '09:00:00', 'professional', 'Main Hall', 'in-person', 'Student Union', 1, '/assets/event1.jpg'),
('Career Fair', 'Meet employers.', '2025-10-20', '10:00:00', 'professional', 'Auditorium', 'in-person', 'Career Services', 1, '/assets/event2.jpg')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    date = VALUES(date),
    time = VALUES(time),
    category = VALUES(category),
    location = VALUES(location),
    attendance = VALUES(attendance),
    organizer = VALUES(organizer),
    image = VALUES(image);

-- Sample Event RSVPs
INSERT INTO event_rsvps (event_id, user_id) VALUES
(1, 4),
(2, 4)
ON DUPLICATE KEY UPDATE rsvp_at = CURRENT_TIMESTAMP;

-- Sample Posts
INSERT INTO posts (user_id, content) VALUES
(1, 'Welcome to Campus Connect!'),
(4, 'Excited for the upcoming Hackathon!')
ON DUPLICATE KEY UPDATE content = VALUES(content);

-- Sample Messages
INSERT INTO messages (sender_id, receiver_id, message) VALUES
(1, 4, 'Hello, Alice! Welcome to Campus Connect.'),
(4, 1, 'Thanks, Admin! Excited to be here.')
ON DUPLICATE KEY UPDATE message = VALUES(message);

-- Sample Connections
INSERT INTO connections (user_id1, user_id2, status) VALUES
(1, 4, 'accepted')
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- Sample Notifications
INSERT INTO notifications (user_id, type, content) VALUES
(4, 'signup_status', 'Your signup has been approved!'),
(4, 'message', 'New message from Admin User'),
(4, 'event_rsvp', 'You RSVPed to Hackathon 2025')
ON DUPLICATE KEY UPDATE content = VALUES(content);

-- Sample Reports
INSERT INTO reports (reporter_id, reported_id, type, reason) VALUES
(4, 3, 'user', 'Inappropriate profile content')
ON DUPLICATE KEY UPDATE reason = VALUES(reason);

-- Sample Audit Log
INSERT INTO audit_logs (admin_id, action, user_id, details) VALUES
(1, 'approve_user', 4, 'User Alice Brown approved')
ON DUPLICATE KEY UPDATE details = VALUES(details);

-- Script Complete: Database is fully set up for Campus Connect.
