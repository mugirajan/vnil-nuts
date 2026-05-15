<?php
session_start(); // Always call this — not conditionally
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
echo $_SESSION['csrf_token'];
?>