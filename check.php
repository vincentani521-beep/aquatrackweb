<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo 'PHP ' . PHP_VERSION . '<br>';
foreach (['includes/auth.php', 'includes/header.php', 'includes/sidebar.php', 'config/database.php', 'config/database.local.php', 'classes/incident.php'] as $f) {
    echo $f . ': ' . (file_exists(__DIR__ . '/' . $f) ? 'OK' : 'MISSING') . '<br>';
}

require __DIR__ . '/includes/auth.php';
echo 'auth.php OK<br>';

require __DIR__ . '/config/database.php';
$db = (new Database())->getConnection();
echo $db ? 'Database OK' : 'Database FAILED';