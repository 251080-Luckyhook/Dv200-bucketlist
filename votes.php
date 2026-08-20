<?php
require_once __DIR__ . '/auth.php';

function escapeOutput($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$memories = array_values(array_filter(getVisibleMemories(), function ($memory) {
  return (!isset($memory['visible']) || $memory['visible']) && !empty($memory['next_adventures']);
}));
$votesNeeded = 5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Votes — Bucket List Memories</title>

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
      <a class="nav-link" href="index.php">Feed</a>
      <a class="nav-link" href="add-memory.php">Add memory</a>
      <a class="nav-link active" href="votes.php">vote's</a>
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

  <div class="container">
  <div class="page-card">
    <h1 class="fw-bold">Vote's on next adventure</h1>
    <p class="text-muted mb-4">
      Next adventure is not guaranteed — they launch when the community votes them over the line.
      Every vote counts toward unlocking the feature for everyone.
    </p>

    <div class="row">
      <div class="col-lg-8">
        <?php if (empty($memories)): ?>
          <div class="vote-card">
            <h5>No next adventures yet</h5>
            <p class="mb-0">Add a memory with next adventures and the community can vote on them here.</p>
          </div>
        <?php else: ?>
          <?php foreach ($memories as $memory): ?>
            <?php foreach ($memory['next_adventures'] as $adventureIndex => $adventure): ?>
              <?php
                $loggedInUser = currentUser();
                $canSeeVoteResults = $loggedInUser && (int) $memory['user_id'] === (int) $loggedInUser['id'];
                $voteCount = $canSeeVoteResults ? ($memory['votes'][$adventureIndex] ?? 0) : 0;
                $voteCheck = db()->prepare('SELECT 1 FROM votes WHERE memory_id = ? AND user_id = ?');
                $voteCheck->execute([$memory['id'], $loggedInUser['id'] ?? 0]);
                $hasVoted = (bool) $voteCheck->fetchColumn();
                $votePercent = min(100, round(($voteCount / $votesNeeded) * 100));
              ?>
              <div class="vote-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <h5 class="mb-1"><?php echo escapeOutput($adventure); ?></h5>
                    <small>From <?php echo escapeOutput($memory['title']); ?></small>
                  </div>
                  <form method="post" action="memory.php?id=<?php echo urlencode($memory['id']); ?>">
                    <input type="hidden" name="vote_adventure" value="<?php echo $adventureIndex; ?>" />
                    <button type="submit" class="btn btn-vote" <?php echo $hasVoted ? 'disabled' : ''; ?>>
                      <?php echo $hasVoted ? 'Voted' : 'Vote &#8963;'; ?>
                    </button>
                  </form>
                </div>
                <?php if ($canSeeVoteResults): ?>
                  <div class="progress">
                    <div class="progress-bar" style="width:<?php echo $votePercent; ?>%"></div>
                  </div>
                  <div class="d-flex justify-content-between mt-2 small">
                    <span><?php echo escapeOutput($voteCount); ?> / <?php echo $votesNeeded; ?> votes</span>
                    <span><?php echo $votePercent >= 100 ? 'Complete' : escapeOutput($votePercent) . '% unlocked'; ?></span>
                  </div>
                <?php else: ?>
                  <div class="small text-muted mt-2">Vote to support this adventure.</div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="col-lg-4">
        <div class="prev-adventures-panel">
          <h4>Prev adventures</h4>
          <p class="subtitle">See some of your prev adventures</p>
          <ul>
            <?php foreach (getVisibleMemories() as $memory): ?>
              <?php if (!empty($memory['title'])): ?>
                <li><span><?php echo escapeOutput($memory['title']); ?></span><span><?php echo escapeOutput($memory['likes'] ?? 0); ?> likes</span></li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
  </div>

  <?php include __DIR__ . '/footer.php'; ?>

  <script src="js/account-menu.js"></script>
</body>
</html>