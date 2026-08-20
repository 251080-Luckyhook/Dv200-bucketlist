<?php
session_start();
require_once __DIR__ . '/db.php';

function currentUser() {
  $username = $_SESSION['current_user'] ?? null;
  return $username !== null ? userByUsername($username) : null;
}

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

function isAdmin() {
  $user = currentUser();
  return $user && !empty($user['is_admin']);
}

function requireLogin() {
  if (!currentUser()) {
    header('Location: login.php');
    exit;
  }
}

function requireAdmin() {
  if (!isAdmin()) {
    header('Location: index.php');
    exit;
  }
}
