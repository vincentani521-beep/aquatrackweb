<?php
require_once __DIR__ . '/includes/auth.php';

if (auth_current_user()) {
    header('Location: incidents.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
$database = new Database();
$db = $database->getConnection();
$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedName = $_POST['name'] ?? '';
    $submittedEmail = $_POST['email'] ?? '';
    $name = is_string($submittedName) ? trim($submittedName) : '';
    $email = is_string($submittedEmail) ? strtolower(trim($submittedEmail)) : '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (!auth_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($name === '' || strlen($name) > 100) {
        $error = 'Enter a name between 1 and 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Enter a valid email address.';
    } elseif (!is_string($password) || strlen($password) < 8 || strlen($password) > 4096) {
        $error = 'Choose a password between 8 and 4096 characters.';
    } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
        $error = 'The password confirmation does not match.';
    } elseif (!$db instanceof PDO) {
        http_response_code(503);
        $error = 'Account creation is unavailable. Check the database connection and authentication setup.';
    } else {
        try {
            $stmt = $db->prepare('INSERT INTO app_users (full_name, email, password_hash) VALUES (:name, :email, :password_hash)');
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => password_hash($password, PASSWORD_DEFAULT)
            ]);

            session_regenerate_id(true);
            unset($_SESSION['auth_csrf_token']);
            $_SESSION['auth_user'] = [
                'id' => (int)$db->lastInsertId(),
                'name' => $name,
                'email' => $email
            ];
            header('Location: incidents.php');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'An account with that email already exists.';
            } elseif ($exception->getCode() === '42S02') {
                error_log('Registration setup error: ' . $exception->getMessage());
                http_response_code(503);
                $error = 'The account table is missing. Import database/auth_schema.sql into aquatrack_db, then try again.';
            } else {
                error_log('Registration error: ' . $exception->getMessage());
                http_response_code(503);
                $error = 'Account creation is unavailable. Check the database connection and authentication setup.';
            }
        }
    }
}

$csrfToken = auth_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account - AquaTrack</title>
    <link rel="stylesheet" href="asset/css/style.css?v=20261027-auth-ui">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="auth-brand" href="login.php">
            <span class="auth-brand-mark" aria-hidden="true"><i class="fa-solid fa-droplet"></i></span>
            <span>AquaTrack</span>
        </a>
        <div class="auth-heading">
            <span class="auth-eyebrow">GET STARTED</span>
            <h1>Create your account</h1>
            <p class="auth-subtitle">Set up your secure access to the AquaTrack water operations platform.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-message" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="auth-field">
                <label for="name">Name</label>
                <div class="auth-input-wrap">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    <input id="name" name="name" type="text" autocomplete="name" maxlength="100" placeholder="Your full name" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="auth-field">
                <label for="email">Email</label>
                <div class="auth-input-wrap">
                    <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="254" placeholder="you@example.com" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="auth-field">
                <label for="password">Password</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required>
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle="password">
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <small>Use at least 8 characters.</small>
            </div>
            <div class="auth-field">
                <label for="password_confirmation">Confirm password</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter your password" minlength="8" required>
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle="password_confirmation">
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <button class="auth-submit" type="submit">
                <span>Create account</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
        </form>

        <div class="auth-divider"><span>ALREADY HAVE AN ACCOUNT?</span></div>
        <a class="auth-secondary-action" href="login.php">Sign in <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        <p class="auth-security-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Your password is stored securely.</p>
    </main>
    <script src="asset/js/auth.js"></script>
</body>
</html>
