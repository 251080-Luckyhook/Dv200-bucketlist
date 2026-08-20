<?php
require_once __DIR__ . '/auth.php';

$passportMemories = array_values(array_filter(getVisibleMemories(), function ($memory) {
  if (isset($memory['visible']) && !$memory['visible']) return false;
  if (!currentUser()) return true;
  return ($memory['author'] ?? '') === currentUser()['username'];
}));
$passportPages = array_chunk($passportMemories, 3);
$passportStampPages = [
  [
    'left' => [],
    'right' => $passportPages[0] ?? []
  ],
  [
    'left' => $passportPages[1] ?? [],
    'right' => $passportPages[2] ?? []
  ],
  [
    'left' => $passportPages[3] ?? [],
    'right' => []
  ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Passport — Bucket List Memories</title>

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
      <a class="nav-link" href="votes.php">vote's</a>
      <a class="nav-link active" href="passport.php">My passport</a>
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

  <div class="passport-stage">
    <div class="passport-layout">
      <a class="passport-add-card passport-add-left" href="add-memory.php">Add</a>

      <div class="passport-book" id="passportBook">

      Closed cover
      <div class="passport-cover" id="passportCover">
        <img src="img/passport-closed.png" alt="Passport cover" draggable="false" />
        <div class="passport-name"><?php echo htmlspecialchars(currentUser()['username'] ?? 'Marco Le Roux', ENT_QUOTES, 'UTF-8'); ?></div>
      </div>

      Open pages
      <div class="passport-pages" id="passportPages">
        <div class="page-spread">
          <img src="img/passport-open.png" alt="Passport pages" id="pageImg" draggable="false" />
          <div class="passport-stamps" id="passportStamps" aria-live="polite">
            <div class="passport-stamp-page">
              <div id="passportStampLeft"></div>
            </div>
            <div class="passport-stamp-page">
              <div id="passportStampRight"></div>
            </div>
          </div>
        </div>
        <div class="page-zone page-zone-left" id="zoneLeft" title="Previous page"></div>
        <div class="page-zone page-zone-right" id="zoneRight" title="Next page"></div>
      </div>

      </div>

      <a class="passport-add-card passport-add-right" href="add-memory.php">Add</a>
    </div>
    <p class="passport-hint" id="passportHint">Click the passport to open it</p>
  </div>

  <script>
    // stamps spreading logic
    const spreads = [
      'img/spread-cover-1.png',
      'img/spread-2-3.png',
      'img/spread-6-cover.png'
    ];
    let spreadIndex = 0;

    const book = document.getElementById('passportBook');
    const cover = document.getElementById('passportCover');
    const hint = document.getElementById('passportHint');
    const zoneLeft = document.getElementById('zoneLeft');
    const zoneRight = document.getElementById('zoneRight');
    const pageImg = document.getElementById('pageImg');
    const stampLeft = document.getElementById('passportStampLeft');
    const stampRight = document.getElementById('passportStampRight');
    const passportStampPages = <?php echo json_encode($passportStampPages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function createStamp(memory) {
      const stamp = document.createElement('a');
      stamp.className = 'passport-stamp';
      stamp.href = `memory.php?id=${encodeURIComponent(memory.id)}`;
      stamp.title = memory.title;

      const country = document.createElement('strong');
      country.textContent = memory.country;
      const title = document.createElement('small');
      title.textContent = memory.title;
      stamp.append(country, title);
      return stamp;
    }

    function renderStamps(index) {
      stampLeft.replaceChildren();
      stampRight.replaceChildren();
      const spreadStamps = passportStampPages[index] || { left: [], right: [] };
      spreadStamps.left.forEach((memory) => stampLeft.appendChild(createStamp(memory)));
      spreadStamps.right.forEach((memory) => stampRight.appendChild(createStamp(memory)));
    }

    function isOpen() {
      return book.classList.contains('open');
    }

    function openPassport() {
      if (book.classList.contains('open')) return;
      spreadIndex = 0;
      pageImg.src = spreads[spreadIndex];
      renderStamps(spreadIndex);
      book.classList.add('open');
      updateHint();
    }

    function closePassport() {
      book.classList.remove('open');
      spreadIndex = 0;
      pageImg.src = spreads[spreadIndex];
      renderStamps(spreadIndex);
      updateHint();
    }

    function goToSpread(index) {
      pageImg.classList.add('flipping');
      window.setTimeout(() => {
        spreadIndex = index;
        pageImg.src = spreads[spreadIndex];
        renderStamps(spreadIndex);
        pageImg.classList.remove('flipping');
        updateHint();
      }, 260);
    }

    function updateHint() {
      if (!isOpen()) {
        hint.textContent = 'Click the passport to open it';
      } else if (spreadIndex === spreads.length - 1) {
        hint.textContent = 'Last page · click left to go back';
      } else {
        hint.textContent = 'Click right to turn the page · click left to go back';
      }
    }

    function nudgePage() {
      pageImg.classList.remove('nudge');
      void pageImg.offsetWidth; // restart animation
      pageImg.classList.add('nudge');
    }

    cover.addEventListener('click', openPassport);

    zoneLeft.addEventListener('click', () => {
      if (spreadIndex === 0) {
        closePassport();
      } else {
        goToSpread(spreadIndex - 1);
      }
    });

    zoneRight.addEventListener('click', () => {
      if (spreadIndex < spreads.length - 1) {
        goToSpread(spreadIndex + 1);
      } else {
        nudgePage();
      }
    });

    renderStamps(0);
  </script>

  <?php include __DIR__ . '/footer.php'; ?>

  <script src="js/account-menu.js"></script>
</body>
</html>