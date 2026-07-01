-- Seed roles
INSERT INTO roles (id, name) VALUES (1, 'Learner'), (2, 'Tutor'), (3, 'Admin');

-- Seed skills
INSERT INTO skills (name, category) VALUES 
  ('Mathematics', 'Academic'),
  ('Physics', 'Academic'),
  ('Web Development', 'Tech'),
  ('Data Science', 'Tech'),
  ('Spanish', 'Languages'),
  ('French', 'Languages');

-- Seed users (password is 'password')
INSERT INTO users (email, password_hash, first_name, last_name, bio, faculty, year, is_active)
VALUES 
  ('alice@example.com', '$2y$10$VzYK2XlLvQjTJLPeGaMZ.OGqLVhFLVHr4VKU4tJYNWDR1JF3Hcvwy', 'Alice', 'Smith', 'Math enthusiast', 'Science', '3', 1),
  ('bob@example.com', '$2y$10$VzYK2XlLvQjTJLPeGaMZ.OGqLVhFLVHr4VKU4tJYNWDR1JF3Hcvwy', 'Bob', 'Johnson', 'Physics tutor', 'Science', '4', 1),
  ('carol@example.com', '$2y$10$VzYK2XlLvQjTJLPeGaMZ.OGqLVhFLVHr4VKU4tJYNWDR1JF3Hcvwy', 'Carol', 'Williams', 'Web dev expert', 'Engineering', '2', 1),
  ('admin@example.com', '$2y$10$VzYK2XlLvQjTJLPeGaMZ.OGqLVhFLVHr4VKU4tJYNWDR1JF3Hcvwy', 'Admin', 'User', 'Platform Admin', 'Admin', null, 1);

-- Assign roles to users
INSERT INTO user_roles (user_id, role_id) VALUES 
  (1, 1),  -- Alice is Learner
  (2, 1), (2, 2),  -- Bob is Learner + Tutor
  (3, 1), (3, 2),  -- Carol is Learner + Tutor
  (4, 3);  -- Admin is Admin

-- Create user_skills (tutors offering skills)
INSERT INTO user_skills (user_id, skill_id, hourly_rate, experience_level, description)
VALUES
  (2, 1, 15.00, 'Expert', 'I teach advanced mathematics'),
  (2, 2, 18.00, 'Expert', 'Physics tutor with 5 years experience'),
  (3, 3, 20.00, 'Intermediate', 'Web development using modern frameworks');

-- Create wallets for users
INSERT INTO wallets (user_id, balance) VALUES 
  (1, 100.00),
  (2, 500.00),
  (3, 250.00),
  (4, 0.00);
