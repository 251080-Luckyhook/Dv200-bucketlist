<?php
require_once __DIR__ . '/auth.php';

$memories = getVisibleMemories();

function escapeOutput($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Community Feed — Bucket List Memories</title>

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

  <div class="container-fluid">
    <div class="row">

<!-- Filters sidebar -->
      <div class="col-lg-3 filters-panel">
        <div class="filters-card">
        <h6>Country</h6>
        <div id="countryFilters"></div>

        <h6>Year</h6>
        <div>
          <span class="year-pill active" data-year="all" onclick="selectYear(this)">All</span>
          <span class="year-pill" data-year="2026" onclick="selectYear(this)">2026</span>
          <span class="year-pill" data-year="2025" onclick="selectYear(this)">2025</span>
          <span class="year-pill" data-year="2024" onclick="selectYear(this)">2024</span>
          <span class="year-pill" data-year="2023" onclick="selectYear(this)">2023</span>
          <span class="year-pill" data-year="2022" onclick="selectYear(this)">2022</span>
          <span class="year-pill" data-year="2021" onclick="selectYear(this)">2021</span>
          <span class="year-pill" data-year="2020" onclick="selectYear(this)">2020</span>
          <span class="year-pill" data-year="Earlier" onclick="selectYear(this)">Earlier</span>
        </div>

        <h6>Category</h6>
        <div>
          <span class="category-pill" data-category="Adventure" onclick="toggleCategory(this)">Adventure</span>
          <span class="category-pill" data-category="Food" onclick="toggleCategory(this)">Food</span>
          <span class="category-pill" data-category="Culture" onclick="toggleCategory(this)">Culture</span>
          <span class="category-pill" data-category="Nature" onclick="toggleCategory(this)">Nature</span>
          <span class="category-pill" data-category="Nightlife" onclick="toggleCategory(this)">Nightlife</span>
          <span class="category-pill" data-category="Solo" onclick="toggleCategory(this)">Solo</span>
          <span class="category-pill" data-category="History" onclick="toggleCategory(this)">History</span>
          <span class="category-pill" data-category="Sports" onclick="toggleCategory(this)">Sports</span>
          <span class="category-pill" data-category="Wellness" onclick="toggleCategory(this)">Wellness</span>
          <span class="category-pill" data-category="Road trip" onclick="toggleCategory(this)">Road trip</span>
          <span class="category-pill" data-category="Photography" onclick="toggleCategory(this)">Photography</span>
          <span class="category-pill" data-category="Volunteering" onclick="toggleCategory(this)">Volunteering</span>
          <span class="category-pill" data-category="Weird" onclick="toggleCategory(this)">Weird</span>
        </div>
        </div>
      </div>

      <!-- Feed -->
      <div class="col-lg-9 p-4">
        <h1 class="fw-bold">Community Feed</h1>
        <p class="text-muted mb-4" id="feedSummary">Showing <?php echo count($memories); ?> memories</p>

        <div class="row">
          <?php foreach ($memories as $memory): ?>
            <?php
              $cardImage = '';
              foreach ($memory['media'] ?? [] as $mediaPath) {
                $mediaExtension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                if (in_array($mediaExtension, ['jpg', 'jpeg', 'png'], true)) {
                  $cardImage = $mediaPath;
                  break;
                }
              }
              $coverClass = $memory['cover'] ?? 'cover-yellow';
              $country = $memory['country'] ?? '';
              $year = $memory['year'] ?? '';
              $category = $memory['category'] ?? '';
            ?>
            <div class="col-md-4 memory-card-col" data-country="<?php echo escapeOutput($country); ?>" data-year="<?php echo escapeOutput($year); ?>" data-category="<?php echo escapeOutput($category); ?>">
              <a href="memory.php?id=<?php echo urlencode($memory['id']); ?>" class="text-decoration-none text-dark">
                <div class="memory-card">
                  <div class="cover <?php echo escapeOutput($coverClass); ?><?php echo $cardImage !== '' ? ' has-media' : ''; ?>">
                    <?php if ($cardImage !== ''): ?>
                      <img class="card-cover-media" src="<?php echo escapeOutput($cardImage); ?>" alt="<?php echo escapeOutput($memory['title']); ?>" />
                    <?php endif; ?>
                    <div class="badge-stamp"><?php echo escapeOutput(strtoupper(substr($country, 0, 3))); ?><br /><?php echo escapeOutput($year); ?></div>
                    <div class="card-title-overlay"><?php echo escapeOutput($memory['title']); ?></div>
                  </div>
                  <div class="card-body-custom">
                    <div class="category"><?php echo escapeOutput($category); ?></div>
                    <div class="snippet"><?php echo escapeOutput(substr($memory['story'], 0, 80)); ?></div>
                    <div class="meta">
                      <span>&hearts; <?php echo escapeOutput($memory['likes'] ?? 0); ?></span>
                      <span>&#128172; <?php echo escapeOutput($memory['comments'] ?? 0); ?></span>
                      <span>@<?php echo escapeOutput(strtolower($memory['author'] ?? 'you')); ?></span>
                    </div>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    function toggleCountry(el) {
      el.classList.toggle('checked');
      applyFilters();
    }

    function buildCountryFilters() {
      const countryFilters = document.getElementById('countryFilters');
      const countryCounts = new Map();

      document.querySelectorAll('.memory-card-col[data-country]').forEach(card => {
        const country = card.dataset.country;
        countryCounts.set(country, (countryCounts.get(country) || 0) + 1);
      });

      Array.from(countryCounts.entries()).slice(0, 4).forEach(([country, count]) => {
        const filter = document.createElement('div');
        filter.className = 'filter-check';
        filter.dataset.country = country;
        filter.onclick = () => toggleCountry(filter);

        const label = document.createElement('span');
        const box = document.createElement('span');
        box.className = 'box';
        label.append(box, country);

        const resultCount = document.createElement('span');
        resultCount.textContent = count;
        filter.append(label, resultCount);
        countryFilters.appendChild(filter);
      });
    }

    function selectYear(el) {
      const wasActive = el.classList.contains('active');
      document.querySelectorAll('.year-pill').forEach(p => p.classList.remove('active'));
      if (!wasActive) el.classList.add('active');
      applyFilters();
    }

    function toggleCategory(el) {
      el.classList.toggle('active');
      applyFilters();
    }

    function applyFilters() {
      const selectedCountries = Array.from(document.querySelectorAll('.filter-check.checked'))
        .map(el => el.dataset.country);
      const selectedYearValue = document.querySelector('.year-pill.active')?.dataset.year || null;
      const selectedYear = selectedYearValue === 'all' ? null : selectedYearValue;
      const selectedCategories = Array.from(document.querySelectorAll('.category-pill.active'))
        .map(el => el.dataset.category);

      let visibleCount = 0;
      document.querySelectorAll('.memory-card-col').forEach(col => {
        const countryMatch = selectedCountries.length === 0 || selectedCountries.includes(col.dataset.country);
        const yearMatch = !selectedYear || col.dataset.year === selectedYear;
        const categoryMatch = selectedCategories.length === 0 || selectedCategories.includes(col.dataset.category);
        const show = countryMatch && yearMatch && categoryMatch;
        col.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });

      const parts = [];
      if (selectedCountries.length) parts.push(selectedCountries.join(', '));
      if (selectedYear) parts.push(selectedYear);
      if (selectedCategories.length) parts.push(selectedCategories.join(', '));

      const summary = document.getElementById('feedSummary');
      const noun = visibleCount === 1 ? 'memory' : 'memories';
      summary.textContent = parts.length
        ? `Filtered by ${parts.join(' · ')} — ${visibleCount} ${noun}`
        : `Showing ${visibleCount} ${noun}`;
    }

    document.addEventListener('DOMContentLoaded', () => {
      buildCountryFilters();
      applyFilters();
    });
  </script>

  <script src="js/account-menu.js"></script>
</body>
</html>