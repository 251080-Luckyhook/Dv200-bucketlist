CREATE DATABASE IF NOT EXISTS bucketlist_memories CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bucketlist_memories;

-- One account can have many memories. The admin setting is saved with the account.
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Each memory belongs to one account. Its status tells us if an admin has approved it.
-- The feed's country, year, and category filters run in the browser.
CREATE TABLE memories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  country VARCHAR(100) NOT NULL,
  year_completed VARCHAR(10) NOT NULL,
  category VARCHAR(50) NOT NULL,
  story TEXT NOT NULL,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  -- Saved like total; the app recounts likes when someone likes a memory.
  likes INT UNSIGNED NOT NULL DEFAULT 0,
  cover VARCHAR(40) NOT NULL DEFAULT 'cover-yellow',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  -- Deleting the account also deletes its memories. Uploaded files are removed separately.
  CONSTRAINT fk_memories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_memories_filter (country, year_completed, category, visible, status)
) ENGINE=InnoDB;

-- Keep uploads separate so one memory can have several photos, videos, or audio files.
CREATE TABLE memory_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  memory_id BIGINT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  media_type VARCHAR(30) NOT NULL,
  CONSTRAINT fk_media_memory FOREIGN KEY (memory_id) REFERENCES memories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tags can be shared by many memories. memory_tags connects each memory to its tags.
CREATE TABLE tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE memory_tags (
  memory_id BIGINT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (memory_id, tag_id),
  FOREIGN KEY (memory_id) REFERENCES memories(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Each memory can have several adventure ideas, each with a vote total.
CREATE TABLE next_adventures (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  memory_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  vote_count INT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (memory_id) REFERENCES memories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- One person can vote only once on each memory. This rule is saved in the database.
CREATE TABLE votes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  memory_id BIGINT UNSIGNED NOT NULL,
  adventure_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_vote_per_user_memory (memory_id, user_id),
  FOREIGN KEY (memory_id) REFERENCES memories(id) ON DELETE CASCADE,
  FOREIGN KEY (adventure_id) REFERENCES next_adventures(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- This key stops a person liking the same memory more than once.
CREATE TABLE likes (
  memory_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (memory_id, user_id),
  FOREIGN KEY (memory_id) REFERENCES memories(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Each comment is linked to its memory and its author. There is no report/flag feature.
CREATE TABLE comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  memory_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(500) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (memory_id) REFERENCES memories(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- A saved query that shows each memory with its adventure ideas and vote totals.
CREATE VIEW memory_vote_summary AS
SELECT m.id AS memory_id, m.title, na.title AS adventure, na.vote_count
FROM memories m JOIN next_adventures na ON na.memory_id = m.id;
