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


INSERT INTO `users` (`id`,`username`,`email`,`password`,`first_name`,`last_name`,`bio`,`profile_picture`,`role`,`is_banned`,`created_at`,`updated_at`) VALUES ('1','alex_photo','alex@zazagram.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Alex','Rivera','Photographer | Traveler | Coffee addict ☕','default_avatar.png','admin',0,'2026-01-15T10:30:00Z','2026-04-10T08:15:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`,`username`,`email`,`password`,`first_name`,`last_name`,`bio`,`profile_picture`,`role`,`is_banned`,`created_at`,`updated_at`) VALUES ('4','sophie_eats','sophie@zazagram.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Sophie','Martin','Food blogger | Home chef | Paris 🗼','default_avatar.png','user',0,'2026-02-14T12:00:00Z','2026-04-18T10:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`,`username`,`email`,`password`,`first_name`,`last_name`,`bio`,`profile_picture`,`role`,`is_banned`,`created_at`,`updated_at`) VALUES ('5','dev_sam','sam@zazagram.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Sam','Patel','Full-stack dev | Open source | Tea > Coffee','default_avatar.png','user',0,'2026-03-01T08:00:00Z','2026-04-20T14:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`,`username`,`email`,`password`,`first_name`,`last_name`,`bio`,`profile_picture`,`role`,`is_banned`,`created_at`,`updated_at`) VALUES ('6','YouDaBesh','norihynbes@gmail.com','$2y$12$J9ex.g/MKlPrQdYA2MxQX.476RzaWrm4bL8uN/f/qQMJcxMKVsHoK','Norihy','Da Besh','','default_avatar.png','user',0,'2026-04-23T12:46:43Z','2026-04-23T12:46:43Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`,`username`,`email`,`password`,`first_name`,`last_name`,`bio`,`profile_picture`,`role`,`is_banned`,`created_at`,`updated_at`) VALUES ('7','THEBOSSZAZA','zaza@gmail.com','$2y$12$MoPTyGvkl6yM5tpgqJjyTeoT2y59EVKGpQTdAo7Ord1bBno4tLwP2','Zaza','Owner','Oui','default_avatar.png','user',0,'2026-04-23T13:51:15Z','2026-04-23T14:04:27Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `users` (`id`,`username`,`email`,`password`,`first_name`,`last_name`,`bio`,`profile_picture`,`role`,`is_banned`,`created_at`,`updated_at`) VALUES ('8','frek','fe@gmail.com','$2y$10$FZ07UgK46nwUdJqhMQs3l.szuDxEj3FRBUUjtHfaXXFYSNsjnjO/6','Freaky','Moove','','default_avatar.png','user',0,'2026-04-23T18:24:33Z','2026-04-23T18:24:33Z') ON DUPLICATE KEY UPDATE id=id;
ALTER TABLE `users` AUTO_INCREMENT = 9;

INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('1','1','Golden hour at the coast 🌅 Nothing beats this light.','post_1.jpg','warm','2026-04-15T18:30:00Z','2026-04-15T18:30:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('4','4','Homemade croissants this Sunday morning 🥐 Recipe in bio!','post_4.jpg','vintage','2026-04-18T09:15:00Z','2026-04-18T09:15:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('5','5','Just deployed my first open source project 🚀 Check it out!','','none','2026-04-20T15:00:00Z','2026-04-20T15:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('6','1','Street photography in the city. Every face tells a story 📷','post_6.jpg','mono','2026-04-21T14:00:00Z','2026-04-21T14:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('7','6','Hello','','none','2026-04-23T12:47:11Z','2026-04-23T12:47:11Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('8','1','','post_1776951551_1.png','none','2026-04-23T13:39:11Z','2026-04-23T13:39:11Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('9','7','NIGGERS','post_1776953097_7.png','fade','2026-04-23T14:04:57Z','2026-04-23T14:04:57Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `posts` (`id`,`user_id`,`caption`,`image`,`filter`,`created_at`,`updated_at`) VALUES ('10','1','dwd','','none','2026-04-23T19:49:25Z','2026-04-23T19:49:25Z') ON DUPLICATE KEY UPDATE id=id;
ALTER TABLE `posts` AUTO_INCREMENT = 11;

INSERT INTO `comments` (`id`,`post_id`,`user_id`,`content`,`created_at`) VALUES ('3','1','4','This is my wallpaper now 🙌','2026-04-15T20:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `comments` (`id`,`post_id`,`user_id`,`content`,`created_at`) VALUES ('7','4','5','Sunday mornings done right!','2026-04-18T10:45:00Z') ON DUPLICATE KEY UPDATE id=id;
ALTER TABLE `comments` AUTO_INCREMENT = 8;

INSERT INTO `likes` (`id`,`user_id`,`post_id`,`created_at`) VALUES ('3','4','1','2026-04-15T19:15:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `likes` (`id`,`user_id`,`post_id`,`created_at`) VALUES ('4','5','1','2026-04-15T20:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `likes` (`id`,`user_id`,`post_id`,`created_at`) VALUES ('9','1','4','2026-04-18T09:30:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `likes` (`id`,`user_id`,`post_id`,`created_at`) VALUES ('12','1','6','2026-04-21T14:30:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `likes` (`id`,`user_id`,`post_id`,`created_at`) VALUES ('13','4','6','2026-04-21T15:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `likes` (`id`,`user_id`,`post_id`,`created_at`) VALUES ('14','1','9','2026-04-23T18:23:28Z') ON DUPLICATE KEY UPDATE id=id;
ALTER TABLE `likes` AUTO_INCREMENT = 15;

INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('5','4','1','accepted','2026-04-22T11:00:00Z','2026-04-23T13:02:07Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('7','1','5','pending','2026-04-23T13:28:53Z','2026-04-23T13:28:53Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('8','1','6','pending','2026-04-23T13:28:54Z','2026-04-23T13:28:54Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('9','7','1','accepted','2026-04-23T14:05:05Z','2026-04-23T17:59:18Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('10','8','1','pending','2026-04-23T18:24:58Z','2026-04-23T18:24:58Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('11','8','4','pending','2026-04-23T18:24:59Z','2026-04-23T18:24:59Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('12','8','5','pending','2026-04-23T18:24:59Z','2026-04-23T18:24:59Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('13','8','6','pending','2026-04-23T18:25:00Z','2026-04-23T18:25:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `friends` (`id`,`requester_id`,`receiver_id`,`status`,`created_at`,`updated_at`) VALUES ('14','8','7','pending','2026-04-23T18:25:01Z','2026-04-23T18:25:01Z') ON DUPLICATE KEY UPDATE id=id;
ALTER TABLE `friends` AUTO_INCREMENT = 15;

INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('1','2','1','Hey Alex! Love your coastal shot. Where was that?',1,'2026-04-15T20:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('2','1','2','Thanks Mia! It was the Algarve coast in Portugal. Highly recommend!',1,'2026-04-15T20:15:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('3','2','1','Oh wow, that\'s on my bucket list! Did you use a filter on the shot?',1,'2026-04-15T20:20:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('4','1','2','Just the warm filter in Zazagram! No post-processing needed 📷',0,'2026-04-15T20:25:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('5','3','1','Bro, wanna join my morning run crew? 6am every day 💪',1,'2026-04-17T07:30:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('6','4','2','Mia! Can you design a logo for my food blog? 🙏',0,'2026-04-19T11:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('7','1','3','ggrrg',0,'2026-04-23T12:54:29Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('8','1','3','fefef',0,'2026-04-23T13:28:57Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('9','1','3','Hello nigga',0,'2026-04-23T13:29:01Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `messages` (`id`,`sender_id`,`receiver_id`,`content`,`is_read`,`created_at`) VALUES ('10','1','4','feef',0,'2026-04-23T13:38:20Z') ON DUPLICATE KEY UPDATE id=id;
ALTER TABLE `messages` AUTO_INCREMENT = 11;

INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('1','1','2','like','1','post','mia_creates liked your post',1,'2026-04-15T19:05:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('2','1','3','like','1','post','jordan_fit liked your post',1,'2026-04-15T19:10:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('3','1','2','comment','1','post','mia_creates commented on your post',1,'2026-04-15T19:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('4','1','4','friend_request','5','friend','sophie_eats sent you a friend request',1,'2026-04-22T11:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('5','2','5','friend_request','6','friend','dev_sam sent you a friend request',0,'2026-04-23T08:00:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('6','2','1','comment','4','post','alex_photo commented on your post',0,'2026-04-16T10:30:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('7','1','2','message','4','message','mia_creates sent you a message',1,'2026-04-15T20:25:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('8','3','1','comment','3','post','alex_photo commented on your post',0,'2026-04-23T12:53:56Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('9','3','1','message','7','message','alex_photo sent you a message',0,'2026-04-23T12:54:29Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('10','5','1','friend_request','7','friend','alex_photo sent you a friend request',0,'2026-04-23T13:28:53Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('11','6','1','friend_request','8','friend','alex_photo sent you a friend request',0,'2026-04-23T13:28:54Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('12','3','1','message','8','message','alex_photo sent you a message',0,'2026-04-23T13:28:57Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('13','3','1','message','9','message','alex_photo sent you a message',0,'2026-04-23T13:29:01Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('14','4','1','message','10','message','alex_photo sent you a message',0,'2026-04-23T13:38:20Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('15','1','7','friend_request','9','friend','THEBOSSZAZA sent you a friend request',1,'2026-04-23T14:05:05Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('16','7','1','like','9','post','alex_photo liked your post',0,'2026-04-23T18:23:28Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('17','1','8','friend_request','10','friend','frek sent you a friend request',0,'2026-04-23T18:24:58Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('18','4','8','friend_request','11','friend','frek sent you a friend request',0,'2026-04-23T18:24:59Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('19','5','8','friend_request','12','friend','frek sent you a friend request',0,'2026-04-23T18:24:59Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('20','6','8','friend_request','13','friend','frek sent you a friend request',0,'2026-04-23T18:25:00Z') ON DUPLICATE KEY UPDATE id=id;
INSERT INTO `notifications` (`id`,`user_id`,`actor_id`,`type`,`reference_id`,`reference_type`,`message`,`is_read`,`created_at`) VALUES ('21','7','8','friend_request','14','friend','frek sent you a friend request',0,'2026-04-23T18:25:01Z') ON DUPLICATE KEY UPDATE id=id;
ALTER TABLE `notifications` AUTO_INCREMENT = 22;

