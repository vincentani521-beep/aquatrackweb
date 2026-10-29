<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

include 'includes/header.php';
include 'includes/sidebar.php';

$rows = $db->query("SELECT w.*, u.name as technician FROM work_orders w LEFT JOIN users u ON w.assigned_to = u.id ORDER BY w.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fa-solid fa-clipboard-list"></i> Work Order Dispatch</h1>
</div>

<div class="card-table" style="background:#fff; padding:20px; border-radius:8px;">
    <table style="width:100%; text-align:left; border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #eee;">
                <th>#ID</th>
                <th>Task Title</th>
                <th>Assigned Technician</th>
                <th>Priority</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:10px 0;">#<?= $row['id'] ?></td>
                <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                <td><?= htmlspecialchars($row['technician'] ?? 'Unassigned') ?></td>
                <td><?= htmlspecialchars($row['priority']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>