<?php
require_once __DIR__ . '/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $deleteId = $_POST['delete_id'] ?? '';
  $approveId = $_POST['approve_id'] ?? '';
  $rejectId = $_POST['reject_id'] ?? '';

  if ($approveId !== '') {
    db()->prepare("UPDATE memories SET status = 'approved' WHERE id = ?")->execute([$approveId]);
    header('Location: admin.php');
    exit;
  }

  if ($rejectId !== '') {
    db()->prepare("UPDATE memories SET status = 'rejected' WHERE id = ?")->execute([$rejectId]);
    header('Location: admin.php');
    exit;
  }

  $memory = getMemoryForAdmin($deleteId);
  if ($memory) {

    foreach ($memory['media'] as $mediaPath) {
      $fullPath = __DIR__ . '/' . ltrim($mediaPath, '/');
      if (is_file($fullPath)) unlink($fullPath);
    }
    db()->prepare('DELETE FROM memories WHERE id = ?')->execute([$deleteId]);
  }

  header('Location: admin.php');
  exit;
}

function escapeOutput($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — Bucket List Memories</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="css/style.css" rel="stylesheet" />
</head>
<body>
  <nav class="site-navbar d-flex align-items-center justify-content-between">
    <a class="brand" href="index.php"><span class="star">&#9733;</span> Bucket List Memories</a>
    <div class="d-none d-md-flex">
      <a class="nav-link" href="index.php">Feed</a>
      <a class="nav-link" href="add-memory.php">Add memory</a>
      <a class="nav-link active" href="admin.php">Admin</a>
    </div>
    <div class="account-menu">
      <button type="button" class="avatar-circle" aria-label="Open account menu"><?php echo userInitials(); ?></button>
      <div class="account-popup">
        <span>Signed in as <?php echo htmlspecialchars(currentUser()['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        <form method="post" action="logout.php"><button type="submit" class="btn btn-pill-outline">Sign out</button></form>
      </div>
    </div>
  </nav>

  <div class="container">
    <div class="page-card">
      <h1 class="fw-bold">Admin moderation</h1>
      <p class="text-muted mb-4">Remove community posts that should no longer appear in the feed.</p>

      <?php $memories = getAllMemories(); ?>
      <?php if (empty($memories)): ?>
        <p>No submitted memories yet.</p>
      <?php else: ?>
        <?php foreach ($memories as $memory): ?>
          <div class="admin-memory-row">
            <a class="admin-memory-link" href="memory.php?id=<?php echo urlencode($memory['id']); ?>">
              <strong><?php echo escapeOutput($memory['title']); ?></strong>
              <div class="text-muted"><?php echo escapeOutput($memory['country']); ?> · <?php echo escapeOutput($memory['category']); ?> · by <?php echo escapeOutput($memory['author'] ?? 'Unknown'); ?> · <?php echo escapeOutput($memory['status']); ?></div>
            </a>
            <div class="d-flex gap-2">
              <?php if ($memory['status'] === 'pending'): ?>
                <form method="post"><input type="hidden" name="approve_id" value="<?php echo escapeOutput($memory['id']); ?>" /><button type="submit" class="btn btn-pill-gold">Approve</button></form>
                <form method="post"><input type="hidden" name="reject_id" value="<?php echo escapeOutput($memory['id']); ?>" /><button type="submit" class="btn btn-pill-outline">Reject</button></form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Remove this memory?');">
                <input type="hidden" name="delete_id" value="<?php echo escapeOutput($memory['id']); ?>" />
                <button type="submit" class="btn btn-pill-outline">Remove</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php include __DIR__ . '/footer.php'; ?>

  <script src="js/account-menu.js"></script>
</body>
</html>
