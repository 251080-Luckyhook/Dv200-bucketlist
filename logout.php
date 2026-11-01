<?php
require_once __DIR__ . '/auth.php';

// Forget the signed-in username and return to the feed.
unset($_SESSION['current_user']);
header('Location: index.php');
exit;
