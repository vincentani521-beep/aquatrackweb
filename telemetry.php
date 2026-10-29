<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

include 'includes/header.php';
include 'includes/sidebar.php';

$rows = $db->query("
    SELECT t.*, s.sensor_name 
    FROM telemetry t 
    JOIN sensors s ON t.sensor_id = s.id 
    ORDER BY t.logged_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fa-solid fa-chart-line"></i> Real-Time Telemetry</h1>
</div>

<div class="card-table" style="background:#fff; padding:20px; border-radius:8px;">
    <table style="width:100%; text-align:left; border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #eee;">
                <th>#ID</th>
                <th>Sensor Name</th>
                <th>Metric</th>
                <th>Value</th>
                <th>Logged At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:10px 0;">#<?= $row['id'] ?></td>
                <td><strong><?= htmlspecialchars($row['sensor_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['reading_type']) ?></td>
                <td><strong><?= $row['value'] ?> <?= $row['unit'] ?></strong></td>
                <td><?= $row['logged_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>