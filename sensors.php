<?php
// Enable error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();

// Verify file existence before importing
if (!file_exists('config/database.php')) {
    die("Database configuration file missing: config/database.php");
}
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch sensors query
$query = "SELECT * FROM sensors";
if (!empty($search)) {
    $query .= " WHERE sensor_name LIKE :search OR type LIKE :search OR location LIKE :search";
}
$query .= " ORDER BY id DESC";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->execute([':search' => '%' . $search . '%']);
} else {
    $stmt->execute();
}
$sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (file_exists('includes/header.php')) include 'includes/header.php';
if (file_exists('includes/sidebar.php')) include 'includes/sidebar.php';
?>

<div class="content-wrapper" style="padding: 25px; background-color: #f4f6f9; min-height: 100vh;">
    <!-- Top Breadcrumb Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; color: #333; font-weight: 600; font-size: 26px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-droplet fa-solid" style="color: #0d6efd;"></i> Sensors
        </h2>
        <div style="color: #6c757d; font-size: 14px;">
            <a href="index.php" style="color: #0d6efd; text-decoration: none;">Dashboard</a> / Sensors
        </div>
    </div>

    <!-- Main Container -->
    <div style="background: #ffffff; border-radius: 8px; border: 1px solid #e3e6f0; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <button style="background-color: #0d6efd; color: #ffffff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                + Add New Sensor
            </button>

            <form action="sensors.php" method="GET" style="display: flex;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search sensors..." style="padding: 9px 15px; border: 1px solid #ced4da; border-radius: 6px 0 0 6px; outline: none;">
                <button type="submit" style="background-color: #0d6efd; color: white; border: 1px solid #0d6efd; padding: 9px 18px; border-radius: 0 6px 6px 0; cursor: pointer;">
                    Search
                </button>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e3e6f0; background-color: #f8f9fc;">
                        <th style="padding: 12px 15px;">ID</th>
                        <th style="padding: 12px 15px;">Sensor Name</th>
                        <th style="padding: 12px 15px;">Type</th>
                        <th style="padding: 12px 15px;">Location</th>
                        <th style="padding: 12px 15px;">Status</th>
                        <th style="padding: 12px 15px;">Installation Date</th>
                        <th style="padding: 12px 15px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sensors)): ?>
                        <?php foreach ($sensors as $row): ?>
                            <tr style="border-bottom: 1px solid #e3e6f0;">
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($row['id']) ?></td>
                                <td style="padding: 12px 15px; font-weight: 600;"><?= htmlspecialchars($row['sensor_name'] ?? $row['name'] ?? 'N/A') ?></td>
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($row['type'] ?? 'N/A') ?></td>
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($row['location'] ?? 'N/A') ?></td>
                                <td style="padding: 12px 15px;">
                                    <span style="padding: 4px 10px; border-radius: 12px; font-size: 12px; background: #d1e7dd; color: #0f5132;">
                                        ● <?= htmlspecialchars($row['status'] ?? 'Active') ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 15px; color: #6c757d;"><?= htmlspecialchars($row['installation_date'] ?? $row['created_at'] ?? 'N/A') ?></td>
                                <td style="padding: 12px 15px; text-align: center;">
                                    <button style="background: #0d6efd; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">View</button>
                                    <button style="background: #ffc107; color: #212529; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Edit</button>
                                    <button style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">No sensors found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (file_exists('includes/footer.php')) include 'includes/footer.php'; ?>