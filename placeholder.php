<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
$pageTitle = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'Module';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header">
    <h1><?= $pageTitle ?></h1>
</div>

<div class="card-table" style="padding: 40px; text-align: center;">
    <i class="fa-solid fa-screwdriver-wrench" style="font-size: 48px; color: #1877f2; margin-bottom: 15px;"></i>
    <h2><?= $pageTitle ?> Module Under Construction</h2>
    <p style="color: #666; margin-top: 8px;">This module is part of the AquaTrack system roadmap.</p>
</div>

<?php include 'includes/footer.php'; ?>