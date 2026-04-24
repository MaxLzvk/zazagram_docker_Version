CREATE DATABASE IF NOT EXISTS zazagram CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zazagram;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) DEFAULT '',
  last_name VARCHAR(100) DEFAULT '',
  bio TEXT,
  profile_picture VARCHAR(255) DEFAULT 'default_avatar.png',
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  is_banned TINYINT(1) NOT NULL DEFAULT 0,
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  caption TEXT,
  image VARCHAR(255) NOT NULL,
  filter VARCHAR(50) DEFAULT 'none',
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL,
  INDEX (user_id),
  CONSTRAINT posts_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  created_at VARCHAR(32) NOT NULL,
  INDEX (post_id),
  INDEX (user_id),
  CONSTRAINT comments_post_fk FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT comments_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  post_id INT NOT NULL,
  created_at VARCHAR(32) NOT NULL,
  UNIQUE KEY unique_user_post_like (user_id, post_id),
  INDEX (post_id),
  CONSTRAINT likes_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT likes_post_fk FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS friends (
  id INT AUTO_INCREMENT PRIMARY KEY,
  requester_id INT NOT NULL,
  receiver_id INT NOT NULL,
  status ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL,
  UNIQUE KEY unique_friend_pair (requester_id, receiver_id),
  INDEX (receiver_id),
  CONSTRAINT friends_requester_fk FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT friends_receiver_fk FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  content TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at VARCHAR(32) NOT NULL,
  INDEX (sender_id),
  INDEX (receiver_id),
  CONSTRAINT messages_sender_fk FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT messages_receiver_fk FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  actor_id INT NULL,
  type VARCHAR(50) NOT NULL,
  reference_id INT NULL,
  reference_type VARCHAR(50) NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at VARCHAR(32) NOT NULL,
  INDEX (user_id),
  INDEX (actor_id),
  CONSTRAINT notifications_user_fk FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT notifications_actor_fk FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
