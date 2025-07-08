-- Update admin credentials in existing database
-- Run this script if you have already imported the schema.sql file

-- Update the admin user credentials
UPDATE users 
SET email = 'resrpawal@gmail.com', 
    password = '$2y$10$O9vmpQqVaNzTkksp8oKGr.k.qt.7F3tYTf8h0uMsadJ.ZJ4JBXIEC'
WHERE user_type = 'admin' AND email = 'admin@booknest.com';

-- Alternative: If the above doesn't work, delete old admin and insert new one
-- DELETE FROM users WHERE user_type = 'admin' AND email = 'admin@booknest.com';
-- INSERT INTO users (name, email, password, user_type) VALUES 
-- ('Admin User', 'resrpawal@gmail.com', '$2y$10$O9vmpQqVaNzTkksp8oKGr.k.qt.7F3tYTf8h0uMsadJ.ZJ4JBXIEC', 'admin');

-- Verify the change
SELECT user_id, name, email, user_type FROM users WHERE user_type = 'admin';
