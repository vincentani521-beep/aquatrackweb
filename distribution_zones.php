<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

include 'includes/header.php';
include 'includes/sidebar.php';

$rows = $db->query("SELECT * FROM distribution_zones ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fa-solid fa-map-location-dot"></i> Distribution Zones & Nodes</h1>
</div>

<div class="card-table" style="background:#fff; padding:20px; border-radius:8px;">
    <table style="width:100%; text-align:left; border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #eee;">
                <th>#ID</th>
                <th>Zone Name</th>
                <th>Node Type</th>
                <th>Code</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:10px 0;">#<?= $row['id'] ?></td>
                <td><strong><?= htmlspecialchars($row['zone_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['node_type']) ?></td>
                <td><code><?= htmlspecialchars($row['code']) ?></code></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>