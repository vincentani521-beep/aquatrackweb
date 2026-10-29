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
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedEmail = $_POST['email'] ?? '';
    $email = is_string($submittedEmail) ? strtolower(trim($submittedEmail)) : '';
    $password = $_POST['password'] ?? '';

    if (!auth_validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password)) {
        $error = 'Invalid email or password.';
    } elseif (!$db instanceof PDO) {
        http_response_code(503);
        $error = 'Sign-in is unavailable. Check the database connection and authentication setup.';
    } else {
        try {
            $stmt = $db->prepare('SELECT id, full_name, email, password_hash FROM app_users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                unset($_SESSION['auth_csrf_token']);
                $_SESSION['auth_user'] = [
                    'id' => (int)$user['id'],
                    'name' => $user['full_name'],
                    'email' => $user['email']
                ];
                header('Location: incidents.php');
                exit;
            }

            $error = 'Invalid email or password.';
        } catch (PDOException $exception) {
            error_log('Login error: ' . $exception->getMessage());
            http_response_code(503);
            $error = $exception->getCode() === '42S02'
                ? 'The account table is missing. Import database/auth_schema.sql into aquatrack_db, then try again.'
                : 'Sign-in is unavailable. Check the database connection and authentication setup.';
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
    <title>Sign in - AquaTrack</title>
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
            <span class="auth-eyebrow">WATER OPERATIONS PLATFORM</span>
            <h1>Welcome back</h1>
            <p class="auth-subtitle">Sign in to your account to continue monitoring your water network.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="auth-message" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
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
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle="password">
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <button class="auth-submit" type="submit">
                <span>Sign in</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
        </form>

        <div class="auth-divider"><span>NEW TO AQUATRACK?</span></div>
        <a class="auth-secondary-action" href="register.php">Create an account <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        <p class="auth-security-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Your account is protected with secure sign-in.</p>
    </main>
    <script src="asset/js/auth.js"></script>
</body>
</html>
