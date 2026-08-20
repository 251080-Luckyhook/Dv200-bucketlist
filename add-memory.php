<?php
require_once __DIR__ . '/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $year = trim($_POST['year'] ?? 'Earlier');
  $category = trim($_POST['category'] ?? 'Adventure');
  $story = trim($_POST['story'] ?? '');

  if ($title === '' || $country === '' || $story === '') {
    $formError = 'Please complete the title, country, and story before stamping your memory.';
  } else {
    $memoryId = bin2hex(random_bytes(8));
    $media = [];
    $uploadDirectory = __DIR__ . '/uploads';
    $allowedMediaTypes = [
      'audio/mpeg' => 'mp3',
      'video/mp4' => 'mp4',
      'image/jpeg' => 'jpg',
      'image/png' => 'png'
    ];

    if (!is_dir($uploadDirectory)) {
      mkdir($uploadDirectory, 0755, true);
    }

    if (!empty($_FILES['media']['name'][0])) {
      $fileInfo = new finfo(FILEINFO_MIME_TYPE);
      foreach ($_FILES['media']['tmp_name'] as $index => $temporaryFile) {
        if ($_FILES['media']['error'][$index] !== UPLOAD_ERR_OK) continue;

        $mediaType = $fileInfo->file($temporaryFile);
        if (!isset($allowedMediaTypes[$mediaType])) continue;

        $fileName = $memoryId . '-' . count($media) . '.' . $allowedMediaTypes[$mediaType];
        if (move_uploaded_file($temporaryFile, $uploadDirectory . '/' . $fileName)) {
          $media[] = 'uploads/' . $fileName;
        }
      }
    }

    $nextAdventures = array_values(array_filter(array_map('trim', $_POST['next_adventures'] ?? [])));
    $coverVariants = ['cover-yellow', 'cover-yellow-light', 'cover-green', 'cover-green-light'];
    $pdo = db();
    $pdo->beginTransaction();
    $memoryStatement = $pdo->prepare('INSERT INTO memories (user_id, title, country, year_completed, category, story, visible, status, cover) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $memoryStatement->execute([currentUser()['id'], $title, $country, $year, $category, $story, isset($_POST['visible']) ? 1 : 0, 'pending', $coverVariants[random_int(0, count($coverVariants) - 1)]]);
    $databaseMemoryId = $pdo->lastInsertId();

    $mediaStatement = $pdo->prepare('INSERT INTO memory_media (memory_id, path, media_type) VALUES (?, ?, ?)');
    foreach ($media as $mediaPath) {
      $mediaStatement->execute([$databaseMemoryId, $mediaPath, pathinfo($mediaPath, PATHINFO_EXTENSION)]);
    }

    $tagStatement = $pdo->prepare('INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $memoryTagStatement = $pdo->prepare('INSERT IGNORE INTO memory_tags (memory_id, tag_id) VALUES (?, LAST_INSERT_ID())');
    foreach (array_values(array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')))) as $tag) {
      $tagStatement->execute([$tag]);
      $memoryTagStatement->execute([$databaseMemoryId]);
    }

    $adventureStatement = $pdo->prepare('INSERT INTO next_adventures (memory_id, title) VALUES (?, ?)');
    foreach ($nextAdventures as $adventure) $adventureStatement->execute([$databaseMemoryId, $adventure]);
    $pdo->commit();

    header('Location: index.php');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Stamp a New Memory — Bucket List Memories</title>

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
      <a class="nav-link active" href="add-memory.php">Add memory</a>
      <a class="nav-link" href="votes.php">vote's</a>
      <a class="nav-link" href="passport.php">My passport</a>
    </div>
    <?php if (isAdmin()): ?><a class="nav-link" href="admin.php">Admin</a><?php endif; ?>
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
    <h1 class="fw-bold">Stamp a new memory</h1>
    <p class="text-muted mb-4">
      This is something you've already done — not something you're planning.
      Tell us where, when, and what happened.
    </p>

    <?php if (!empty($formError)): ?>
      <div class="alert alert-warning"><?php echo htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="row mb-4">
        <div class="col-md-6 mb-3">
          <label class="form-label-strong">Title</label>
          <input type="text" name="title" class="form-control form-pill-input" placeholder="Title of adventure" required />
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-strong">Country</label>
          <input type="text" name="country" class="form-control form-pill-input" list="countryOptions" placeholder="Type to search countries" autocomplete="off" required />
          <datalist id="countryOptions"></datalist>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-6 mb-3">
          <label class="form-label-strong">Year completed</label>
          <select name="year" class="form-select form-pill-input">
            <option>earlier</option> 
            <option>2020</option>
            <option>2021</option>
            <option>2022</option>
            <option>2023</option>
            <option>2024</option>
            <option>2025</option>
            <option>2026</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label-strong">Category</label>
          <select name="category" class="form-select form-pill-input">
            <option>Adventure</option>
            <option>Food</option>
            <option>Culture</option>
            <option>Nature</option>
            <option>Nightlife</option>
            <option>Solo</option>
            <option>History</option>
            <option>Sports</option>
            <option>Wellness</option>
            <option>Road trip</option>
            <option>Photography</option>
            <option>Volunteering</option>
          </select>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label-strong">Tags</label>
        <div id="tagContainer" class="d-flex flex-wrap gap-2 align-items-center">
          <button type="button" id="addTagButton" class="tag-pill tag-add-btn">Add tag +</button>
        </div>
        <input id="tagsInput" type="hidden" name="tags" />
      </div>

      <div id="tagModal" class="tag-modal hidden" aria-hidden="true">
        <div class="tag-modal-content">
          <h5 class="mb-3">Add a tag</h5>
          <input id="tagInput" type="text" class="form-control form-pill-input" maxlength="20" placeholder="Type a tag" />
          <div class="d-flex gap-2 mt-3">
            <button type="button" id="saveTagButton" class="btn btn-pill-gold">Save tag</button>
            <button type="button" id="closeTagModal" class="btn btn-pill-outline">Cancel</button>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label-strong">Photo and video</label>
        <div class="row">
          <div class="col-md-4 mb-2">
            <div id="mediaPreview" class="dropzone media-preview" aria-live="polite">
              <span id="mediaEmptyMessage">No preview yet</span>
              <div id="mediaPreviewContent"></div>
              <div id="mediaControls" class="media-controls" hidden>
                <button id="previousMedia" type="button" class="btn btn-pill-outline" aria-label="Previous file">&#8592;</button>
                <span id="mediaCounter">1 / 1</span>
                <button id="nextMedia" type="button" class="btn btn-pill-outline" aria-label="Next file">&#8594;</button>
                <button id="removeMedia" type="button" class="btn btn-pill-outline">Remove</button>
              </div>
            </div>
          </div>
          <div class="col-md-8 mb-2">
            <div id="mediaDropzone" class="dropzone media-dropzone" role="button" tabindex="0">
              <input id="mediaInput" type="file" name="media[]" accept=".mp3,.mp4,.jpeg,.jpg,.png,audio/mpeg,video/mp4,image/jpeg,image/png" multiple hidden />
              <span>Click or drag images and videos here</span>
              <small>Multiple files allowed</small>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label-strong">Your story</label>
        <textarea name="story" class="form-control form-pill-input" rows="3" placeholder="Add your story" required></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label-strong">Your next adventure</label>
        <input type="text" name="next_adventures[]" class="form-control form-pill-input mb-3" placeholder="Add adventure" />
        <input type="text" name="next_adventures[]" class="form-control form-pill-input mb-3" placeholder="Add adventure" />
        <input type="text" name="next_adventures[]" class="form-control form-pill-input" placeholder="Add adventure" />
      </div>

      <div class="form-check form-switch mb-4">
        <input class="form-check-input" type="checkbox" id="visibleSwitch" name="visible" checked />
        <label class="form-check-label" for="visibleSwitch">
          Visible to the community (uncheck to keep this memory private)
        </label>
      </div>

      <button type="submit" class="btn btn-pill-gold">Stamp this memory &#10022;</button>
    </form>
  </div>
  </div>

  <?php include __DIR__ . '/footer.php'; ?>

  <script>
    const countries = [
      'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan',
      'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi',
      'Cabo Verde', 'Cambodia', 'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', 'Croatia', 'Cuba', 'Cyprus', 'Czechia',
      'Democratic Republic of the Congo', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic',
      'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia',
      'Fiji', 'Finland', 'France',
      'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana',
      'Haiti', 'Honduras', 'Hungary',
      'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Israel', 'Italy', 'Ivory Coast',
      'Jamaica', 'Japan', 'Jordan',
      'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan',
      'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg',
      'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar',
      'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia', 'Norway',
      'Oman',
      'Pakistan', 'Palau', 'Palestine', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal',
      'Qatar',
      'Romania', 'Russia', 'Rwanda',
      'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'Sao Tome and Principe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Sweden', 'Switzerland', 'Syria',
      'Taiwan', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Turkey', 'Turkmenistan', 'Tuvalu',
      'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan',
      'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam',
      'Yemen',
      'Zambia', 'Zimbabwe'
    ];

    const countryOptions = document.getElementById('countryOptions');
    countries.forEach((country) => {
      const option = document.createElement('option');
      option.value = country;
      countryOptions.appendChild(option);
    });

    const MAX_TAGS = 5;
    const tagContainer = document.getElementById('tagContainer');
    const addTagButton = document.getElementById('addTagButton');
    const tagModal = document.getElementById('tagModal');
    const tagInput = document.getElementById('tagInput');
    const saveTagButton = document.getElementById('saveTagButton');
    const closeTagModal = document.getElementById('closeTagModal');
    const tagsInput = document.getElementById('tagsInput');

    function updateTagButton() {
      const count = tagContainer.querySelectorAll('.tag-item').length;
      addTagButton.disabled = count >= MAX_TAGS;
      addTagButton.textContent = count >= MAX_TAGS ? 'Tag limit reached' : 'Add tag +';
      tagsInput.value = Array.from(tagContainer.querySelectorAll('.tag-label'))
        .map((tag) => tag.textContent)
        .join(',');
    }

    function openTagModal() {
      if (tagContainer.querySelectorAll('.tag-item').length >= MAX_TAGS) {
        alert('You can add up to 5 tags only.');
        return;
      }

      tagModal.classList.remove('hidden');
      tagModal.setAttribute('aria-hidden', 'false');
      tagInput.value = '';
      setTimeout(() => tagInput.focus(), 50);
    }

    function closeTagModalDialog() {
      tagModal.classList.add('hidden');
      tagModal.setAttribute('aria-hidden', 'true');
      tagInput.value = '';
    }

    function addTag(tagText) {
      const trimmed = tagText.trim();
      if (!trimmed) return;

      const tags = tagContainer.querySelectorAll('.tag-item');
      if (tags.length >= MAX_TAGS) {
        alert('You can add up to 5 tags only.');
        return;
      }

      const duplicate = Array.from(tags).some((tag) => tag.querySelector('.tag-label').textContent.toLowerCase() === trimmed.toLowerCase());
      if (duplicate) {
        alert('That tag already exists.');
        return;
      }

      const tag = document.createElement('span');
      tag.className = 'tag-pill tag-item';

      const label = document.createElement('span');
      label.className = 'tag-label';
      label.textContent = trimmed;

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'tag-remove';
      removeBtn.setAttribute('aria-label', `Remove ${trimmed} tag`);
      removeBtn.textContent = '×';

      tag.appendChild(label);
      tag.appendChild(removeBtn);
      tagContainer.insertBefore(tag, addTagButton);
      updateTagButton();
    }

    tagContainer.addEventListener('click', (event) => {
      const removeBtn = event.target.closest('.tag-remove');
      if (!removeBtn) return;

      const tag = removeBtn.closest('.tag-item');
      if (tag) {
        tag.remove();
        updateTagButton();
      }
    });

    addTagButton.addEventListener('click', openTagModal);
    closeTagModal.addEventListener('click', closeTagModalDialog);
    saveTagButton.addEventListener('click', () => {
      addTag(tagInput.value);
      closeTagModalDialog();
    });

    tagInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        addTag(tagInput.value);
        closeTagModalDialog();
      }
      if (event.key === 'Escape') {
        closeTagModalDialog();
      }
    });

    tagModal.addEventListener('click', (event) => {
      if (event.target === tagModal) {
        closeTagModalDialog();
      }
    });

    const mediaDropzone = document.getElementById('mediaDropzone');
    const mediaInput = document.getElementById('mediaInput');
    const mediaPreview = document.getElementById('mediaPreview');
    const mediaEmptyMessage = document.getElementById('mediaEmptyMessage');
    const mediaPreviewContent = document.getElementById('mediaPreviewContent');
    const mediaControls = document.getElementById('mediaControls');
    const previousMedia = document.getElementById('previousMedia');
    const nextMedia = document.getElementById('nextMedia');
    const removeMedia = document.getElementById('removeMedia');
    const mediaCounter = document.getElementById('mediaCounter');
    const allowedMediaTypes = new Set(['audio/mpeg', 'video/mp4', 'image/jpeg', 'image/png']);
    const allowedMediaExtensions = new Set(['mp3', 'mp4', 'jpeg', 'jpg', 'png']);
    let uploadedMedia = [];
    let currentMediaIndex = 0;
    let currentPreviewUrl = '';

    function isAllowedMedia(file) {
      const extension = file.name.split('.').pop().toLowerCase();
      return allowedMediaTypes.has(file.type) || allowedMediaExtensions.has(extension);
    }

    function showMediaPreview() {
      const file = uploadedMedia[currentMediaIndex];
      if (!file) {
        mediaPreviewContent.replaceChildren();
        mediaEmptyMessage.hidden = false;
        mediaControls.hidden = true;
        return;
      }

      if (currentPreviewUrl) URL.revokeObjectURL(currentPreviewUrl);
      mediaPreviewContent.replaceChildren();
      mediaEmptyMessage.hidden = true;
      mediaControls.hidden = false;
      currentPreviewUrl = URL.createObjectURL(file);
      const extension = file.name.split('.').pop().toLowerCase();
      const previewElement = extension === 'mp3'
        ? document.createElement('audio')
        : extension === 'mp4'
          ? document.createElement('video')
          : document.createElement('img');

      previewElement.src = currentPreviewUrl;
      previewElement.className = 'media-preview-content';
      previewElement.alt = file.name;
      if (previewElement.tagName === 'AUDIO' || previewElement.tagName === 'VIDEO') {
        previewElement.controls = true;
        if (previewElement.tagName === 'VIDEO') previewElement.muted = true;
      }
      mediaPreviewContent.appendChild(previewElement);
      mediaCounter.textContent = `${currentMediaIndex + 1} / ${uploadedMedia.length}`;
      previousMedia.disabled = uploadedMedia.length < 2;
      nextMedia.disabled = uploadedMedia.length < 2;
    }

    function handleMediaFiles(files) {
      const selectedFiles = Array.from(files);
      const validFiles = selectedFiles.filter(isAllowedMedia);
      const rejectedFiles = selectedFiles.filter((file) => !isAllowedMedia(file));

      if (rejectedFiles.length) {
        alert(`Only MP3, MP4, JPEG, and PNG files are allowed. Rejected: ${rejectedFiles.map((file) => file.name).join(', ')}`);
      }
      if (!validFiles.length) return;

      uploadedMedia.push(...validFiles);
      currentMediaIndex = uploadedMedia.length - validFiles.length;
      updateMediaInput();
      showMediaPreview();

      const fileLabel = mediaDropzone.querySelector('span');
      fileLabel.textContent = `${uploadedMedia.length} file${uploadedMedia.length === 1 ? '' : 's'} selected`;
    }

    function updateMediaInput() {
      const fileList = new DataTransfer();
      uploadedMedia.forEach((file) => fileList.items.add(file));
      mediaInput.files = fileList.files;
    }

    mediaDropzone.addEventListener('click', () => mediaInput.click());
    mediaInput.addEventListener('change', () => handleMediaFiles(mediaInput.files));

    mediaDropzone.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        mediaInput.click();
      }
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
      mediaDropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        mediaDropzone.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
      mediaDropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        mediaDropzone.classList.remove('dragover');
      });
    });

    mediaDropzone.addEventListener('drop', (event) => {
      handleMediaFiles(event.dataTransfer.files);
    });

    previousMedia.addEventListener('click', () => {
      currentMediaIndex = (currentMediaIndex - 1 + uploadedMedia.length) % uploadedMedia.length;
      showMediaPreview();
    });

    nextMedia.addEventListener('click', () => {
      currentMediaIndex = (currentMediaIndex + 1) % uploadedMedia.length;
      showMediaPreview();
    });

    removeMedia.addEventListener('click', () => {
      uploadedMedia.splice(currentMediaIndex, 1);
      currentMediaIndex = Math.min(currentMediaIndex, uploadedMedia.length - 1);
      updateMediaInput();
      showMediaPreview();
      const fileLabel = mediaDropzone.querySelector('span');
      fileLabel.textContent = uploadedMedia.length
        ? `${uploadedMedia.length} file${uploadedMedia.length === 1 ? '' : 's'} selected`
        : 'Click or drag images and videos here';
    });

    updateTagButton();
  </script>

  <script src="js/account-menu.js"></script>
</body>
</html>