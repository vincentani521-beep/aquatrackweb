<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();

// Import database connection safely
if (!file_exists('config/database.php')) {
    die("Database config file not found: config/database.php");
}
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Direct query on audit_logs without fragile table joins
$query = "SELECT * FROM audit_logs";

if (!empty($search)) {
    $query .= " WHERE action LIKE :search 
               OR details LIKE :search 
               OR ip_address LIKE :search";
}

$query .= " ORDER BY id DESC LIMIT 100";

try {
    $stmt = $db->prepare($query);

    if (!empty($search)) {
        $stmt->execute([':search' => '%' . $search . '%']);
    } else {
        $stmt->execute();
    }

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query Error: " . $e->getMessage());
}

if (file_exists('includes/header.php')) include 'includes/header.php';
if (file_exists('includes/sidebar.php')) include 'includes/sidebar.php';
?>

<div class="content-wrapper" style="padding: 25px; background-color: #f4f6f9; min-height: 100vh;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin: 0; color: #333; font-weight: 600; font-size: 26px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-clipboard-list" style="color: #0d6efd;"></i> Audit Logs
        </h2>
        <div style="color: #6c757d; font-size: 14px;">
            <a href="index.php" style="color: #0d6efd; text-decoration: none;">Dashboard</a> / Audit Logs
        </div>
    </div>

    <!-- Data Container -->
    <div style="background: #ffffff; border-radius: 8px; border: 1px solid #e3e6f0; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div style="font-size: 14px; color: #6c757d;">
                Showing recent system activities and security events.
            </div>

            <!-- Search Form -->
            <form action="audit-logs.php" method="GET" style="display: flex;">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search logs..." style="padding: 9px 15px; border: 1px solid #ced4da; border-radius: 6px 0 0 6px; outline: none;">
                <button type="submit" style="background-color: #0d6efd; color: white; border: 1px solid #0d6efd; padding: 9px 18px; border-radius: 0 6px 6px 0; cursor: pointer;">
                    Search
                </button>
            </form>
        </div>

        <!-- Logs Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e3e6f0; background-color: #f8f9fc;">
                        <th style="padding: 12px 15px;">Log ID</th>
                        <th style="padding: 12px 15px;">User ID / Actor</th>
                        <th style="padding: 12px 15px;">Action</th>
                        <th style="padding: 12px 15px;">Details</th>
                        <th style="padding: 12px 15px;">IP Address</th>
                        <th style="padding: 12px 15px;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr style="border-bottom: 1px solid #e3e6f0;">
                                <td style="padding: 12px 15px;"><?= htmlspecialchars($log['id']) ?></td>
                                <td style="padding: 12px 15px; font-weight: 600;">
                                    <?= htmlspecialchars($log['user_id'] ?? $log['username'] ?? 'System') ?>
                                </td>
                                <td style="padding: 12px 15px;">
                                    <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; background: #e7f1ff; color: #0d6efd; font-weight: 600;">
                                        <?= htmlspecialchars($log['action'] ?? 'LOG') ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 15px; max-width: 300px; color: #495057;">
                                    <?= htmlspecialchars($log['details'] ?? 'N/A') ?>
                                </td>
                                <td style="padding: 12px 15px; font-family: monospace; color: #6c757d;">
                                    <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?>
                                </td>
                                <td style="padding: 12px 15px; color: #6c757d; font-size: 13px;">
                                    <?= htmlspecialchars($log['created_at'] ?? $log['timestamp'] ?? 'N/A') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 25px; color: #6c757d;">
                                No activity logs recorded yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (file_exists('includes/footer.php')) include 'includes/footer.php'; ?>