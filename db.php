<?php
const DB_HOST = '127.0.0.1';
const DB_NAME = 'bucketlist_memories';
const DB_USER = 'root';
const DB_PASS = '';

function db() {
  static $pdo;
  if ($pdo instanceof PDO) return $pdo;

  try {
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

function getVisibleMemories() {
  $statement = db()->query(
    "SELECT m.*, u.username AS author
     FROM memories m
     JOIN users u ON u.id = m.user_id
    WHERE m.visible = 1 AND m.status = 'approved'
     ORDER BY m.created_at DESC"
  );
  return array_map('hydrateMemory', $statement->fetchAll());
}

function getMemoryById($id) {
  $statement = db()->prepare(
    "SELECT m.*, u.username AS author
     FROM memories m JOIN users u ON u.id = m.user_id
    WHERE m.id = ? AND m.visible = 1 AND m.status = 'approved'"
  );
  $statement->execute([$id]);
  $memory = $statement->fetch();
  return $memory ? hydrateMemory($memory) : null;
}

function getAllMemories() {
  $statement = db()->query(
    "SELECT m.*, u.username AS author
     FROM memories m JOIN users u ON u.id = m.user_id
     ORDER BY m.created_at DESC"
  );
  return array_map('hydrateMemory', $statement->fetchAll());
}

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

function hydrateMemory($memory) {
  $pdo = db();
  $media = $pdo->prepare('SELECT path FROM memory_media WHERE memory_id = ? ORDER BY id');
  $media->execute([$memory['id']]);
  $memory['media'] = array_column($media->fetchAll(), 'path');

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

function userByUsername($username) {
  $statement = db()->prepare('SELECT * FROM users WHERE username = ?');
  $statement->execute([strtolower($username)]);
  return $statement->fetch() ?: null;
}
