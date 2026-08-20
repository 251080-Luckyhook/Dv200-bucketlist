<?php
require_once __DIR__ . '/auth.php';

function escapeOutput($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$memoryId = $_GET['id'] ?? '';
$actionMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  requireLogin();
  if (isset($_POST['like_memory'])) {
    $like = db()->prepare('INSERT IGNORE INTO likes (memory_id, user_id) VALUES (?, ?)');
    $like->execute([$memoryId, currentUser()['id']]);
    db()->prepare('UPDATE memories SET likes = (SELECT COUNT(*) FROM likes WHERE memory_id = ?) WHERE id = ?')->execute([$memoryId, $memoryId]);

    header('Location: memory.php?id=' . urlencode($memoryId));
    exit;
  }

  if (isset($_POST['comment'])) {
    $commentText = trim($_POST['comment']);
    if ($commentText !== '') {
      db()->prepare('INSERT INTO comments (memory_id, user_id, body) VALUES (?, ?, ?)')->execute([$memoryId, currentUser()['id'], $commentText]);
      header('Location: memory.php?id=' . urlencode($memoryId));
      exit;
    }
  }

  if (isset($_POST['vote_adventure'])) {
    $voteIndex = filter_var($_POST['vote_adventure'], FILTER_VALIDATE_INT);
    $adventureId = false;
    if ($voteIndex !== false && $voteIndex >= 0) {
      $adventureStatement = db()->prepare('SELECT id FROM next_adventures WHERE memory_id = ? ORDER BY id LIMIT 1 OFFSET ' . (int) $voteIndex);
      $adventureStatement->execute([$memoryId]);
      $adventureId = $adventureStatement->fetchColumn();
    }
    if ($adventureId) {
      $pdo = db();
      try {
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO votes (memory_id, adventure_id, user_id) VALUES (?, ?, ?)')->execute([$memoryId, $adventureId, currentUser()['id']]);
        $pdo->prepare('UPDATE next_adventures SET vote_count = vote_count + 1 WHERE id = ?')->execute([$adventureId]);
        $pdo->commit();
      } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
      }
    }

    header('Location: memory.php?id=' . urlencode($memoryId));
    exit;
  }
}

$memory = isAdmin() ? getMemoryForAdmin($memoryId) : getMemoryById($memoryId);

if ($memory === null) {
  header('Location: index.php');
  exit;
}

$mediaFiles = $memory['media'] ?? [];
$heroImage = '';
foreach ($mediaFiles as $mediaPath) {
  $mediaExtension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
  if (in_array($mediaExtension, ['jpg', 'jpeg', 'png'], true)) {
    $heroImage = $mediaPath;
    break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo escapeOutput($memory['title']); ?> — Bucket List Memories</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
</head>
<body>

  <!-- Navbar -->
  <nav class="site-navbar d-flex align-items-center justify-content-between">
    <a class="brand" href="index.php">
      <span class="star">&#9733;</span>
      Bucket List Memories
    </a>
    <div class="d-none d-md-flex">
      <a class="nav-link active" href="index.php">Feed</a>
      <a class="nav-link" href="add-memory.php">Add memory</a>
      <a class="nav-link" href="votes.php">vote's</a>
      <a class="nav-link" href="passport.php">My passport</a>
    </div>
    <?php if (isAdmin()): ?><a class="nav-link" href="admin.php">Admin</a><?php endif; ?>
    <?php if (currentUser()): ?>
      <div class="account-menu">
        <button type="button" class="avatar-circle" aria-label="Open account menu"><?php echo userInitials(); ?></button>
        <div class="account-popup">
          <span>Signed in as <?php echo htmlspecialchars(currentUser()['username'], ENT_QUOTES, 'UTF-8'); ?></span>
          <form method="post" action="logout.php"><button type="submit" class="btn btn-pill-outline">Sign out</button></form>
        </div>
      </div>
    <?php else: ?>
      <a href="login.php" class="avatar-circle text-decoration-none"><?php echo userInitials(); ?></a>
    <?php endif; ?>
  </nav>

  <div class="memory-page-card">
    <div class="detail-hero<?php echo $heroImage !== '' ? ' has-hero-media' : ''; ?>">
      <?php if ($heroImage !== ''): ?>
        <img class="detail-hero-media" src="<?php echo escapeOutput($heroImage); ?>" alt="<?php echo escapeOutput($memory['title']); ?>" />
      <?php endif; ?>
      <div class="stamp-circle"><?php echo escapeOutput(strtoupper($memory['title'])); ?><br /><?php echo escapeOutput(strtoupper(substr($memory['country'], 0, 3))); ?> &middot; <?php echo escapeOutput($memory['year']); ?></div>
      <h1><?php echo escapeOutput($memory['title']); ?></h1>
      <div class="meta-line">
        &#128205; <?php echo escapeOutput($memory['country']); ?> &nbsp;&nbsp;|&nbsp;&nbsp; <?php echo escapeOutput($memory['category']); ?> &nbsp;&nbsp; Posted by <?php echo escapeOutput($memory['author'] ?? 'You'); ?>
      </div>
    </div>

    <div class="detail-body">
      <div class="mb-3">
        <span class="tag-pill"><?php echo escapeOutput($memory['category']); ?></span>
        <?php foreach ($memory['tags'] ?? [] as $tag): ?>
          <span class="tag-pill"><?php echo escapeOutput($tag); ?></span>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($mediaFiles)): ?>
        <div class="memory-detail-media mb-4">
          <?php foreach ($mediaFiles as $mediaIndex => $mediaPath): ?>
            <?php $mediaExtension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION)); ?>
            <div class="detail-media-item" data-media-index="<?php echo $mediaIndex; ?>" <?php echo $mediaIndex === 0 ? 'data-active' : ''; ?>>
              <?php if ($mediaExtension === 'mp4'): ?>
                <video src="<?php echo escapeOutput($mediaPath); ?>" controls></video>
              <?php elseif ($mediaExtension === 'mp3'): ?>
                <audio src="<?php echo escapeOutput($mediaPath); ?>" controls></audio>
              <?php else: ?>
                <img src="<?php echo escapeOutput($mediaPath); ?>" alt="<?php echo escapeOutput($memory['title']); ?>" />
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (count($mediaFiles) > 1): ?>
            <div class="detail-media-controls">
              <button id="previousDetailMedia" type="button" class="btn btn-pill-outline" aria-label="Previous uploaded file">&#8592;</button>
              <span id="detailMediaCounter">1 / <?php echo count($mediaFiles); ?></span>
              <button id="nextDetailMedia" type="button" class="btn btn-pill-outline" aria-label="Next uploaded file">&#8594;</button>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <p class="mb-4"><?php echo nl2br(escapeOutput($memory['story'])); ?></p>

      <?php if (!empty($memory['next_adventures'])): ?>
        <h5 class="mb-3">Next adventures</h5>
        <?php $votesNeeded = 5; ?>
        <?php
          $loggedInUser = currentUser();
          $canSeeVoteResults = $loggedInUser && (int) $memory['user_id'] === (int) $loggedInUser['id'];
          $voteCheck = db()->prepare('SELECT 1 FROM votes WHERE memory_id = ? AND user_id = ?');
          $voteCheck->execute([$memoryId, $loggedInUser['id'] ?? 0]);
          $hasVoted = (bool) $voteCheck->fetchColumn();
        ?>
        <form method="post" class="next-adventure-voting mb-4">
          <?php foreach ($memory['next_adventures'] as $adventureIndex => $adventure): ?>
            <label class="next-adventure-option">
              <input type="radio" name="vote_adventure" value="<?php echo $adventureIndex; ?>" <?php echo $hasVoted ? 'disabled' : ''; ?> required />
              <span><?php echo escapeOutput($adventure); ?></span>
              <?php if ($canSeeVoteResults): ?>
                <strong><?php echo escapeOutput($memory['votes'][$adventureIndex] ?? 0); ?> / <?php echo $votesNeeded; ?> votes</strong>
              <?php endif; ?>
            </label>
          <?php endforeach; ?>
          <button type="submit" class="btn btn-pill-gold" <?php echo $hasVoted ? 'disabled' : ''; ?>>
            <?php echo $hasVoted ? 'You already voted' : 'Vote for one adventure'; ?>
          </button>
        </form>
      <?php endif; ?>

      <?php $hasLiked = $_SESSION['liked_memories'][$memoryId] ?? false; ?>
      <div class="d-flex gap-4 align-items-center border-top border-bottom py-3 mb-4">
        <form method="post" class="memory-like-form">
          <input type="hidden" name="like_memory" value="1" />
          <button type="submit" class="memory-like-button" <?php echo $hasLiked ? 'disabled' : ''; ?>>
            &hearts; <?php echo $hasLiked ? 'Liked' : 'Like'; ?>
          </button>
        </form>
        <span><?php echo escapeOutput($memory['likes'] ?? 0); ?> people remember this too</span>
        <span>&#128172; <?php echo escapeOutput($memory['comments'] ?? 0); ?> comments</span>
      </div>

      <h5 class="mb-3">Comments</h5>
      <form method="post" class="comment-form mb-4">
        <textarea name="comment" class="form-control form-pill-input mb-2" rows="3" maxlength="500" placeholder="Add a comment" required></textarea>
        <button type="submit" class="btn btn-pill-gold">Post comment</button>
      </form>

      <?php if (!empty($memory['comments_list'])): ?>
        <?php foreach ($memory['comments_list'] as $comment): ?>
          <div class="comment-box mb-2">
            <div class="comment-avatar">ML</div>
            <div>
              <strong><?php echo escapeOutput($comment['author']); ?></strong>
              <p class="mb-0"><?php echo nl2br(escapeOutput($comment['text'])); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="comment-box">
          <div class="comment-avatar">ML</div>
          <div>
            <strong>No comments yet</strong>
            <p class="mb-0">Be the first to comment on this memory.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include __DIR__ . '/footer.php'; ?>

  <?php if (count($mediaFiles) > 1): ?>
    <script>
      const detailMediaItems = Array.from(document.querySelectorAll('.detail-media-item'));
      const detailMediaCounter = document.getElementById('detailMediaCounter');
      let detailMediaIndex = 0;

      function showDetailMedia(index) {
        detailMediaIndex = (index + detailMediaItems.length) % detailMediaItems.length;
        detailMediaItems.forEach((item, itemIndex) => {
          const isActive = itemIndex === detailMediaIndex;
          item.toggleAttribute('data-active', isActive);
          item.querySelectorAll('video, audio').forEach((media) => {
            if (!isActive) media.pause();
          });
        });
        detailMediaCounter.textContent = `${detailMediaIndex + 1} / ${detailMediaItems.length}`;
      }

      document.getElementById('previousDetailMedia').addEventListener('click', () => {
        showDetailMedia(detailMediaIndex - 1);
      });

      document.getElementById('nextDetailMedia').addEventListener('click', () => {
        showDetailMedia(detailMediaIndex + 1);
      });
    </script>
  <?php endif; ?>

  <script src="js/account-menu.js"></script>
</body>
</html>
