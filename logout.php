<?php
require_once __DIR__ . '/includes/auth.php';

// Already logged out (or session expired): just go to the login page.
if (!auth_current_user()) {
    header('Location: login.php');
    exit;
}

// Only a POST form with a valid CSRF token can log the user out.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Someone opened logout.php directly (link/URL): send them back instead of an error.
    header('Location: dashboard.php');
    exit;
}

if (!auth_validate_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request token. Go back, refresh the page, and try again.');
}

auth_logout();

header('Location: login.php?logged_out=1');
exit;