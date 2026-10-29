<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
require_once 'classes/incident.php';

$database = new Database();
$db = $database->getConnection();
$incidentObj = new Incident($db);

$id = $_GET['id'] ?? null;
$message = '';

// Redirect if no valid ID provided
if (!$id) {
    header("Location: incidents.php");
    exit();
}

// Handle Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $updated = $incidentObj->updateIncident(
        $_POST['incident_id'],
        $_POST['reported_by'],
        $_POST['severity'],
        $_POST['status'],
        $_POST['description']
    );

    if ($updated) {
        header("Location: incidents.php?status=updated");
        exit();
    } else {
        $message = "Failed to update incident record.";
    }
}

// Fetch current incident details
$record = $incidentObj->getIncidentById($id);

if (!$record) {
    header("Location: incidents.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-pen-to-square" style="color: #1877f2;"></i> Edit Incident #<?= htmlspecialchars($record['incident_id']) ?></h1>
    <a href="incidents.php" class="btn-primary" style="background-color: #6c757d;"><i class="fa-solid fa-arrow-left"></i> Back to Incidents</a>
</div>

<?php if ($message): ?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="card-table" style="padding: 25px; max-width: 600px;">
    <form method="POST" action="update_incident.php?id=<?= $id ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="incident_id" value="<?= $record['incident_id'] ?>">

        <div class="form-group">
            <label>Reported By</label>
            <input type="text" name="reported_by" value="<?= htmlspecialchars($record['reported_by']) ?>" required>
        </div>

        <div class="form-group">
            <label>Severity Level</label>
            <select name="severity" required>
                <option value="Low" <?= $record['severity'] == 'Low' ? 'selected' : '' ?>>Low</option>
                <option value="Medium" <?= $record['severity'] == 'Medium' ? 'selected' : '' ?>>Medium</option>
                <option value="High" <?= $record['severity'] == 'High' ? 'selected' : '' ?>>High</option>
                <option value="Critical" <?= $record['severity'] == 'Critical' ? 'selected' : '' ?>>Critical</option>
            </select>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="Open" <?= ($record['status'] ?? '') == 'Open' ? 'selected' : '' ?>>Open</option>
                <option value="In Progress" <?= ($record['status'] ?? '') == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Resolved" <?= ($record['status'] ?? '') == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="Closed" <?= ($record['status'] ?? '') == 'Closed' ? 'selected' : '' ?>>Closed</option>
            </select>
        </div>

        <div class="form-group">
            <label>Description / Details</label>
            <textarea name="description" rows="4" required><?= htmlspecialchars($record['description'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>