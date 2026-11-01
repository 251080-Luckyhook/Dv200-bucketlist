<?php
// Starts the session. PHP uses a cookie to remember which session belongs to the browser.
// The session stores the username, not the password.
session_start();
require_once __DIR__ . '/db.php';

// Finds the signed-in user's account, or returns nothing if no one is logged in.
function currentUser() {
  // The username saved in the session is used to find the account.
  $username = $_SESSION['current_user'] ?? null;
  return $username !== null ? userByUsername($username) : null;
}

// Gets the letters shown in the user's avatar.
function userInitials() {
  $user = currentUser();
  if (!$user) return '?';

  $parts = preg_split('/\s+/', trim($user['username']));
  $initials = strtoupper(substr($parts[0], 0, 1));
  if (count($parts) > 1) {
    $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
  }
  return substr($initials, 0, 2);
}

// Checks whether this user is an admin.
function isAdmin() {
  // The admin setting comes from the user's saved account.
  $user = currentUser();
  return $user && !empty($user['is_admin']);
}

// Sends signed-out visitors to the login page.
function requireLogin() {
  if (!currentUser()) {
    header('Location: login.php');
    exit;
  }
}

// Sends non-admin users back to the feed.
function requireAdmin() {
  if (!isAdmin()) {
    header('Location: index.php');
    exit;
  }
}
