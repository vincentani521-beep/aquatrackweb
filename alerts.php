<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

include 'includes/header.php';
include 'includes/sidebar.php';

$alerts = $db->query("
    SELECT a.*, s.sensor_name 
    FROM alerts a 
    JOIN sensors s ON a.sensor_id = s.id 
    ORDER BY a.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fa-solid fa-bell"></i> Automated System Alerts</h1>
</div>

<div class="card-table">
    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Target Sensor</th>
                <th>Alert Type</th>
                <th>Message</th>
                <th>Channel</th>
                <th>Status</th>
                <th>Logged At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alerts as $alert): ?>
            <tr>
                <td>#<?= $alert['id'] ?></td>
                <td><strong><?= htmlspecialchars($alert['sensor_name']) ?></strong></td>
                <td><?= htmlspecialchars($alert['alert_type']) ?></td>
                <td><?= htmlspecialchars($alert['message']) ?></td>
                <td><span class="badge-status"><?= htmlspecialchars($alert['notification_channel']) ?></span></td>
                <td><span class="badge-status"><?= htmlspecialchars($alert['sent_status']) ?></span></td>
                <td><?= $alert['created_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>