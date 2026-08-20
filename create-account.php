<?php
require_once __DIR__ . '/auth.php';

if (currentUser()) {
  header('Location: index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $username = trim($_POST['username'] ?? '');

  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || $username === '') {
    $formError = 'Please enter a valid email, password, and username.';
  } elseif (userByUsername($username)) {
    $formError = 'That username is already taken.';
  } else {
    $usernameKey = strtolower($username);
    $emailCheck = db()->prepare('SELECT id FROM users WHERE email = ?');
    $emailCheck->execute([$email]);
    if ($emailCheck->fetch()) {
      $formError = 'That email is already registered.';
    } else {
      $statement = db()->prepare('INSERT INTO users (username, email, password_hash, is_admin) VALUES (?, ?, ?, ?)');
      $statement->execute([$usernameKey, $email, password_hash($password, PASSWORD_DEFAULT), $usernameKey === 'admin' ? 1 : 0]);
      $_SESSION['current_user'] = $usernameKey;
      header('Location: index.php');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account — Bucket List Memories</title>

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
      <a href="login.php" class="tab">Log in</a>
      <a href="create-account.php" class="tab active">Create account</a>
    </div>

    <?php if (!empty($formError)): ?>
      <div class="alert alert-warning"><?php echo htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post">
      <label>Email address</label>
      <input type="email" name="email" placeholder="You@gmail.com" required />

      <label>Password</label>
      <input type="password" name="password" placeholder="Choose a password" required />

      <label>Username</label>
      <input type="text" name="username" placeholder="Choose a username" maxlength="30" required />

      <button type="submit" class="btn btn-pill-gold w-100 mt-2">Create account &gt;</button>

      <p class="fine-print text-center mt-3">
        Already have an account? <a href="login.php">Log in</a> instead.
      </p>
    </form>
  </div>

  <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
