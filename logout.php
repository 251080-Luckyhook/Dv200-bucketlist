<?php
require_once __DIR__ . '/auth.php';

unset($_SESSION['current_user']);
header('Location: index.php');
exit;
