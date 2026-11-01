<?php
// Database settings for local development. Use private settings for a live site.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'bucketlist_memories';
const DB_USER = 'root';
const DB_PASS = '';

// Opens the database connection once and reuses it.
function db() {
  static $pdo;
  if ($pdo instanceof PDO) return $pdo;

  try {
    // PDO connects PHP to MySQL and lets us run prepared queries.
    $pdo = new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
      DB_USER,
      DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
  } catch (PDOException $exception) {
    http_response_code(500);
    exit('Database connection failed. Import schema.sql in phpMyAdmin and check db.php settings.');
  }
  return $pdo;
}

// Gets memories that are approved and set to be visible.
function getVisibleMemories() {
  // Match each memory with its author. Memories without a matching account are left out.
  // Only approved, visible memories are shown to everyone.
  $statement = db()->query(
    "SELECT m.*, u.username AS author
     FROM memories m
     JOIN users u ON u.id = m.user_id
    WHERE m.visible = 1 AND m.status = 'approved'
     ORDER BY m.created_at DESC"
  );
  return array_map('hydrateMemory', $statement->fetchAll());
}

// Gets one approved, visible memory for its detail page.
function getMemoryById($id) {
  // Apply the same rules as the public feed.
  $statement = db()->prepare(
    "SELECT m.*, u.username AS author
     FROM memories m JOIN users u ON u.id = m.user_id
    WHERE m.id = ? AND m.visible = 1 AND m.status = 'approved'"
  );
  $statement->execute([$id]);
  $memory = $statement->fetch();
  return $memory ? hydrateMemory($memory) : null;
}

// Gets all memories so the admin can review them.
function getAllMemories() {
  // Include pending, approved, and rejected memories.
  $statement = db()->query(
    "SELECT m.*, u.username AS author
     FROM memories m JOIN users u ON u.id = m.user_id
     ORDER BY m.created_at DESC"
  );
  return array_map('hydrateMemory', $statement->fetchAll());
}

// Gets one memory for the admin, even if it is not approved.
function getMemoryForAdmin($id) {
  $statement = db()->prepare(
    "SELECT m.*, u.username AS author
     FROM memories m JOIN users u ON u.id = m.user_id
     WHERE m.id = ?"
  );
  $statement->execute([$id]);
  $memory = $statement->fetch();
  return $memory ? hydrateMemory($memory) : null;
}

// Adds a memory's photos, tags, adventure ideas, votes, and comments to its details.
function hydrateMemory($memory) {
  $pdo = db();
  // These details are saved in their own tables and connected to the memory by its ID.
  $media = $pdo->prepare('SELECT path FROM memory_media WHERE memory_id = ? ORDER BY id');
  $media->execute([$memory['id']]);
  $memory['media'] = array_column($media->fetchAll(), 'path');

  // The link table tells us which tags belong to this memory.
  $tags = $pdo->prepare('SELECT t.name FROM tags t JOIN memory_tags mt ON mt.tag_id = t.id WHERE mt.memory_id = ? ORDER BY t.name');
  $tags->execute([$memory['id']]);
  $memory['tags'] = array_column($tags->fetchAll(), 'name');

  $adventures = $pdo->prepare('SELECT id, title, vote_count FROM next_adventures WHERE memory_id = ? ORDER BY id');
  $adventures->execute([$memory['id']]);
  $memory['next_adventures'] = [];
  $memory['votes'] = [];
  foreach ($adventures as $adventure) {
    $memory['next_adventures'][] = $adventure['title'];
    $memory['votes'][] = (int) $adventure['vote_count'];
  }

  $comments = $pdo->prepare('SELECT c.body AS text, u.username AS author FROM comments c JOIN users u ON u.id = c.user_id WHERE c.memory_id = ? ORDER BY c.created_at');
  $comments->execute([$memory['id']]);
  $memory['comments_list'] = $comments->fetchAll();
  $memory['comments'] = count($memory['comments_list']);
  $memory['likes'] = (int) $memory['likes'];
  return $memory;
}

// Finds an account by username.
function userByUsername($username) {
  // The question mark safely inserts the username into the query.
  $statement = db()->prepare('SELECT * FROM users WHERE username = ?');
  $statement->execute([strtolower($username)]);
  return $statement->fetch() ?: null;
}
