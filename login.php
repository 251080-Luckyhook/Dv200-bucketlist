<?php
require_once __DIR__ . '/auth.php';

if (currentUser()) {
  header('Location: index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = strtolower(trim($_POST['username'] ?? ''));
  $password = $_POST['password'] ?? '';
  $user = userByUsername($username);

  if (!$user || !password_verify($password, $user['password_hash'])) {
    $formError = 'The username or password is incorrect.';
  } else {
    $_SESSION['current_user'] = $username;
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
  <title>Log In — Bucket List Memories</title>

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
      <a class="nav-link" href="passport.php">My passport</a>
    </div>
    <a href="login.php" class="avatar-circle text-decoration-none"><?php echo userInitials(); ?></a>
  </nav>

  <div class="hero-login">
    <h1>Some things <span class="highlight">deserve</span><br />to be remembered.</h1>
    <p class="mt-3" style="max-width:500px; opacity:0.85;">
      Stop planning what you'll do someday. Start keeping proof of what you already did.
    </p>
  </div>

  <div class="login-card">
    <div class="tabs">
      <a href="login.php" class="tab active">Log in</a>
      <a href="create-account.php" class="tab">Create account</a>
    </div>

    <?php if (!empty($formError)): ?>
      <div class="alert alert-warning"><?php echo htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Username</label>
      <input type="text" name="username" placeholder="Your username" required />

      <label>Password</label>
      <input type="password" name="password" placeholder="Your password" required />

      <button type="submit" class="btn btn-pill-gold w-100 mt-2">Log in &gt;</button>

      <p class="fine-print text-center mt-3">
        New here? <a href="create-account.php">Create an account</a> to start stamping memories.
      </p>
    </form>
  </div>

  <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
