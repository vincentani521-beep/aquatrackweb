<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db instanceof PDO) {
    http_response_code(503);
    exit('The dashboard is temporarily unavailable because the database connection failed.');
}

try {
    $activeIncidents = (int)$db->query(
        "SELECT COUNT(*) FROM incidents WHERE status IN ('Open', 'In Progress')"
    )->fetchColumn();
    $onlineSensors = (int)$db->query(
        "SELECT COUNT(*) FROM sensors WHERE status = 'Online'"
    )->fetchColumn();
    $qualityAlerts = (int)$db->query(
        "SELECT COUNT(*) FROM water_quality WHERE status IN ('Warning', 'Unsafe')"
    )->fetchColumn();

    $latestQuality = $db->query(
        "SELECT status, tested_at FROM water_quality ORDER BY tested_at DESC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    $recentIncidents = $db->query(
        "SELECT i.id, i.title, i.incident_type, i.severity, i.status, i.reported_at, z.zone_name
         FROM incidents i
         LEFT JOIN distribution_zones z ON i.zone_id = z.id
         ORDER BY i.reported_at DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('Dashboard query error: ' . $exception->getMessage());
    http_response_code(503);
    exit('The dashboard could not load its summary data. Please try again later.');
}

$currentUser = auth_current_user();
$firstName = explode(' ', trim($currentUser['name'] ?? 'there'))[0];
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<section class="dashboard-overview">
    <header class="dashboard-welcome">
        <div class="dashboard-welcome-copy">
            <span class="dashboard-eyebrow">AQUATRACK OVERVIEW</span>
            <h1><?= htmlspecialchars($greeting . ', ' . $firstName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Here’s the latest activity across your water network.</p>
        </div>
        <div class="dashboard-water-mark" aria-hidden="true">
            <i class="fa-solid fa-droplet"></i>
        </div>
        <div class="dashboard-date">
            <i class="fa-regular fa-calendar" aria-hidden="true"></i>
            <?= htmlspecialchars(date('l, F j, Y'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </header>

    <section class="dashboard-metrics" aria-label="Network summary">
        <a class="dashboard-metric metric-incidents" href="incidents.php">
            <span class="dashboard-metric-icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
            <span class="dashboard-metric-label">Active incidents</span>
            <strong><?= number_format($activeIncidents) ?></strong>
            <span class="dashboard-metric-foot">Open or in progress <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
        <a class="dashboard-metric metric-sensors" href="sensors.php">
            <span class="dashboard-metric-icon"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i></span>
            <span class="dashboard-metric-label">Sensors online</span>
            <strong><?= number_format($onlineSensors) ?></strong>
            <span class="dashboard-metric-foot">Currently reporting <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
        <a class="dashboard-metric metric-quality" href="water-quality.php">
            <span class="dashboard-metric-icon"><i class="fa-solid fa-flask" aria-hidden="true"></i></span>
            <span class="dashboard-metric-label">Quality alerts</span>
            <strong><?= number_format($qualityAlerts) ?></strong>
            <span class="dashboard-metric-foot">Warnings or unsafe results <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
        </a>
    </section>

    <div class="dashboard-content-grid">
        <section class="dashboard-panel dashboard-recent-panel">
            <div class="dashboard-panel-heading">
                <div>
                    <span class="dashboard-panel-kicker">NETWORK ACTIVITY</span>
                    <h2>Recent incidents</h2>
                </div>
                <a class="dashboard-panel-link" href="incidents.php">View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>

            <?php if ($recentIncidents): ?>
                <div class="dashboard-recent-list">
                    <?php foreach ($recentIncidents as $incident): ?>
                        <?php
                            $statusClass = strtolower(str_replace(' ', '-', $incident['status'] ?? 'open'));
                            $severityClass = strtolower($incident['severity'] ?? 'medium');
                        ?>
                        <article class="dashboard-incident-row">
                            <span class="dashboard-incident-icon"><i class="fa-solid fa-droplet" aria-hidden="true"></i></span>
                            <div class="dashboard-incident-main">
                                <h3><?= htmlspecialchars($incident['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($incident['incident_type'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($incident['zone_name'] ?? 'Unassigned zone', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="dashboard-severity severity-<?= htmlspecialchars($severityClass, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($incident['severity'] ?? 'Medium', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="dashboard-status status-<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($incident['status'] ?? 'Open', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <time datetime="<?= htmlspecialchars(date('c', strtotime($incident['reported_at'])), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(date('M j', strtotime($incident['reported_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </time>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dashboard-empty-state">
                    <span><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                    <h3>You’re all caught up</h3>
                    <p>No incidents have been reported yet.</p>
                </div>
            <?php endif; ?>
        </section>

        <aside class="dashboard-side-panels">
            <section class="dashboard-panel dashboard-quality-panel">
                <span class="dashboard-panel-kicker">WATER QUALITY</span>
                <h2>Latest check</h2>
                <?php if ($latestQuality): ?>
                    <?php $qualityClass = strtolower($latestQuality['status'] ?? 'unknown'); ?>
                    <div class="dashboard-quality-result quality-<?= htmlspecialchars($qualityClass, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="dashboard-quality-icon">
                            <i class="fa-solid <?= $qualityClass === 'safe' ? 'fa-check' : 'fa-triangle-exclamation' ?>" aria-hidden="true"></i>
                        </span>
                        <div>
                            <strong><?= htmlspecialchars($latestQuality['status'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></strong>
                            <span>Most recent sample</span>
                        </div>
                    </div>
                    <p class="dashboard-quality-time">
                        <?= htmlspecialchars(date('M j, Y · g:i A', strtotime($latestQuality['tested_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php else: ?>
                    <div class="dashboard-quality-result quality-unknown">
                        <span class="dashboard-quality-icon"><i class="fa-solid fa-minus" aria-hidden="true"></i></span>
                        <div>
                            <strong>No readings yet</strong>
                            <span>Water quality data is not available</span>
                        </div>
                    </div>
                <?php endif; ?>
                <a class="dashboard-panel-link" href="water-quality.php">View water quality <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </section>

            <section class="dashboard-panel dashboard-shortcuts">
                <span class="dashboard-panel-kicker">QUICK ACCESS</span>
                <h2>Go to</h2>
                <a href="telemetry.php"><span><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span>Telemetry<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                <a href="work-orders.php"><span><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span>Work orders<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                <a href="distribution_zones.php"><span><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span>Distribution zones<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            </section>
        </aside>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
