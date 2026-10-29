<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

include 'includes/header.php';
include 'includes/sidebar.php';

$reports = $db->query("
    SELECT r.*, z.zone_name 
    FROM consumption_reports r 
    JOIN distribution_zones z ON r.zone_id = z.id 
    ORDER BY r.generated_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fa-regular fa-file-lines"></i> Consumption Analytics & Non-Revenue Water (NRW)</h1>
</div>

<div class="card-table">
    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Zone</th>
                <th>Water Produced (m³)</th>
                <th>Water Consumed (m³)</th>
                <th>NRW Loss (%)</th>
                <th>Period</th>
                <th>Generated Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $report): ?>
            <tr>
                <td>#<?= $report['id'] ?></td>
                <td><strong><?= htmlspecialchars($report['zone_name']) ?></strong></td>
                <td><?= number_format($report['water_produced_m3'], 2) ?> m³</td>
                <td><?= number_format($report['water_consumed_m3'], 2) ?> m³</td>
                <td><strong style="color: #e62e2e;"><?= number_format($report['nrw_percentage'], 2) ?>%</strong></td>
                <td><?= htmlspecialchars($report['report_period']) ?></td>
                <td><?= $report['generated_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>