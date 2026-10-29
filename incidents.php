<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();

if (!file_exists('config/database.php')) {
    die("Database config file missing: config/database.php");
}
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

function executeIncidentTransaction(PDO $db, string $query, array $params): bool {
    try {
        $db->beginTransaction();
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $db->commit();
        return true;
    } catch (PDOException $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Incident transaction error: " . $exception->getMessage());
        return false;
    }
}

// Handle Search Query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 1. CREATE Incident
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report_incident') {
    $title = trim($_POST['title']);
    $incident_type = trim($_POST['incident_type']);
    $zone_id = !empty($_POST['zone_id']) ? $_POST['zone_id'] : null;
    $severity = trim($_POST['severity']);

    if (!empty($title) && !empty($incident_type) && !empty($severity)) {
        $success = executeIncidentTransaction(
            $db,
            "INSERT INTO incidents (zone_id, title, incident_type, severity, status, reported_at) VALUES (:zone_id, :title, :incident_type, :severity, 'Open', NOW())",
            [':zone_id' => $zone_id, ':title' => $title, ':incident_type' => $incident_type, ':severity' => $severity]
        );
        header("Location: incidents.php?status=" . ($success ? 'created' : 'error'));
        exit;
    }
}

// 2. UPDATE Incident
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_incident') {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $incident_type = trim($_POST['incident_type']);
    $zone_id = !empty($_POST['zone_id']) ? $_POST['zone_id'] : null;
    $severity = trim($_POST['severity']);
    $status = trim($_POST['status']);

    if ($id && !empty($title)) {
        $success = executeIncidentTransaction(
            $db,
            "UPDATE incidents SET zone_id = :zone_id, title = :title, incident_type = :incident_type, severity = :severity, status = :status WHERE id = :id",
            [':zone_id' => $zone_id, ':title' => $title, ':incident_type' => $incident_type, ':severity' => $severity, ':status' => $status, ':id' => $id]
        );
        header("Location: incidents.php?status=" . ($success ? 'updated' : 'error'));
        exit;
    }
}

// 3. DELETE Incident
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $success = executeIncidentTransaction(
        $db,
        "DELETE FROM incidents WHERE id = :id",
        [':id' => $delete_id]
    );
    header("Location: incidents.php?status=" . ($success ? 'deleted' : 'error'));
    exit;
}

// Fetch Incidents with optional search filter
$query = "SELECT i.*, z.zone_name 
          FROM incidents i 
          LEFT JOIN distribution_zones z ON i.zone_id = z.id ";
if (!empty($search)) {
    $query .= " WHERE i.title LIKE :search OR i.incident_type LIKE :search OR z.zone_name LIKE :search";
}
$query .= " ORDER BY i.reported_at DESC";

$stmt = $db->prepare($query);
if (!empty($search)) {
    $stmt->execute([':search' => '%' . $search . '%']);
} else {
    $stmt->execute();
}
$incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Zones for Dropdowns
$zones = $db->query("SELECT id, zone_name FROM distribution_zones ORDER BY zone_name ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="content-wrapper incidents-page-wrapper">
    
    <!-- Top Breadcrumb Header -->
    <div class="incidents-page-heading">
        <h2>
            <i class="fa-droplet fa-solid"></i> Incidents
        </h2>
        <div class="incidents-breadcrumb">
            <a href="index.php"><i class="fa-home fa-solid"></i> Dashboard</a> / Incidents
        </div>
    </div>

    <?php
        $statusMessages = [
            'created' => 'New incident reported successfully!',
            'updated' => 'Incident updated successfully!',
            'deleted' => 'Incident deleted successfully!',
            'error' => 'The incident could not be saved. Please try again.'
        ];
        $status = $_GET['status'] ?? '';
    ?>
    <?php if (isset($statusMessages[$status])): ?>
        <div class="incident-flash <?= $status === 'error' ? 'incident-flash-error' : 'incident-flash-success' ?>">
            <?= htmlspecialchars($statusMessages[$status], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <div class="incidents-card">
        
        <!-- Controls Bar: Action Button + Search Form -->
        <div class="incidents-toolbar">
            <button class="incident-report-button" onclick="openModal('createModal')">
                <i class="fa-plus fa-solid"></i> Report New Incident
            </button>

            <form class="incident-search" action="incidents.php" method="GET">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search incidents...">
                <button type="submit">
                    <i class="fa-magnifying-glass fa-solid"></i> Search
                </button>
            </form>
        </div>

        <!-- Incidents Table -->
        <div class="incident-table-wrap">
            <table class="incident-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Incident Title</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Reported Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($incidents)): ?>
                        <?php foreach ($incidents as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td class="incident-title-cell"><?= htmlspecialchars($row['title'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['incident_type'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['zone_name'] ?? 'Unassigned') ?></td>
                                <td>
                                    <?php 
                                        $sev = strtolower($row['severity'] ?? 'medium');
                                        $sevBg = ($sev === 'high' || $sev === 'critical') ? '#f8d7da' : (($sev === 'medium') ? '#fff3cd' : '#d1e7dd');
                                        $sevColor = ($sev === 'high' || $sev === 'critical') ? '#842029' : (($sev === 'medium') ? '#664d03' : '#0f5132');
                                    ?>
                                    <span style="padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background-color: <?= $sevBg ?>; color: <?= $sevColor ?>; display: inline-block;">
                                        <?= htmlspecialchars(ucfirst($row['severity'] ?? 'Medium')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $status = strtolower($row['status'] ?? 'open');
                                        if ($status === 'resolved' || $status === 'closed') {
                                            $stBg = '#e2e8f0'; $stColor = '#475569'; $dotColor = '#64748b';
                                        } elseif ($status === 'in progress') {
                                            $stBg = '#dbeafe'; $stColor = '#1e40af'; $dotColor = '#2563eb';
                                        } else { // Open / Pending
                                            $stBg = '#fee2e2'; $stColor = '#991b1b'; $dotColor = '#dc2626';
                                        }
                                    ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; background-color: <?= $stBg ?>; color: <?= $stColor ?>; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; line-height: 1; white-space: nowrap;">
                                        <span style="display: inline-block; width: 6px; height: 6px; background-color: <?= $dotColor ?>; border-radius: 50%; flex-shrink: 0;"></span>
                                        <?= htmlspecialchars(ucfirst($row['status'] ?? 'Open')) ?>
                                    </span>
                                </td>
                                <td class="incident-date-cell">
                                    <?= !empty($row['reported_at']) ? date('Y-m-d H:i', strtotime($row['reported_at'])) : 'N/A' ?>
                                </td>
                                <td class="incident-actions-cell">
                                    <div class="incident-actions">
                                        <button class="incident-action incident-action-view" onclick='viewIncident(<?= json_encode($row) ?>)' title="View Details">
                                            <i class="fa-eye fa-solid"></i> View
                                        </button>
                                        <button class="incident-action incident-action-edit" onclick='editIncident(<?= json_encode($row) ?>)' title="Edit Incident">
                                            <i class="fa-pen-to-square fa-solid"></i> Edit
                                        </button>
                                        <a class="incident-action incident-action-delete" href="incidents.php?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this incident record?');" title="Delete Record">
                                            <i class="fa-trash fa-solid"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="incident-empty" colspan="8">No incident records matching criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Entry Count Footer -->
        <div class="incident-pagination">
            <div>Showing 1 to <?= count($incidents) ?> of <?= count($incidents) ?> entries</div>
            <div class="incident-page-controls">
                <button disabled>«</button>
                <button class="current-page">1</button>
                <button disabled>»</button>
            </div>
        </div>

    </div>
</div>

<!-- Modal Overlay Wrapper -->
<style>
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
</style>

<!-- 1. CREATE MODAL -->
<div id="createModal" class="modal-overlay">
    <div style="background: #fff; border-radius: 8px; width: 90%; max-width: 500px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333; font-size: 18px;"><i class="fa-triangle-exclamation fa-solid" style="color: #0d6efd;"></i> Report New Incident</h3>
            <span onclick="closeModal('createModal')" style="font-size: 22px; cursor: pointer; color: #aaa;">&times;</span>
        </div>
        <form action="incidents.php" method="POST">
            <input type="hidden" name="action" value="report_incident">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Incident Title</label>
                <input type="text" name="title" required placeholder="e.g. Pipe Leak near Sector A" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Incident Type</label>
                <select name="incident_type" required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
                    <option value="Leak">Leak</option>
                    <option value="Pressure Loss">Pressure Loss</option>
                    <option value="Pipe Burst">Pipe Burst</option>
                    <option value="Contamination">Contamination</option>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Distribution Zone</label>
                <select name="zone_id" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
                    <option value="">-- Select Zone --</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['zone_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Severity Level</label>
                <select name="severity" required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('createModal')" style="padding: 9px 18px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; font-size: 14px;">Cancel</button>
                <button type="submit" style="padding: 9px 20px; background: #0d6efd; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 14px;">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. EDIT MODAL -->
<div id="editModal" class="modal-overlay">
    <div style="background: #fff; border-radius: 8px; width: 90%; max-width: 500px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333; font-size: 18px;"><i class="fa-pen-to-square fa-solid" style="color: #ffc107;"></i> Edit Incident #<span id="edit_id_label"></span></h3>
            <span onclick="closeModal('editModal')" style="font-size: 22px; cursor: pointer; color: #aaa;">&times;</span>
        </div>
        <form action="incidents.php" method="POST">
            <input type="hidden" name="action" value="update_incident">
            <input type="hidden" name="id" id="edit_id">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Incident Title</label>
                <input type="text" name="title" id="edit_title" required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Incident Type</label>
                <select name="incident_type" id="edit_incident_type" required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
                    <option value="Leak">Leak</option>
                    <option value="Pressure Loss">Pressure Loss</option>
                    <option value="Pipe Burst">Pipe Burst</option>
                    <option value="Contamination">Contamination</option>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Distribution Zone</label>
                <select name="zone_id" id="edit_zone_id" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
                    <option value="">-- None --</option>
                    <?php foreach ($zones as $zone): ?>
                        <option value="<?= $zone['id'] ?>"><?= htmlspecialchars($zone['zone_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Severity Level</label>
                <select name="severity" id="edit_severity" required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 13px;">Status</label>
                <select name="status" id="edit_status" required style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 5px; box-sizing: border-box; font-size: 14px;">
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('editModal')" style="padding: 9px 18px; border: 1px solid #ccc; background: #fff; border-radius: 5px; cursor: pointer; font-size: 14px;">Cancel</button>
                <button type="submit" style="padding: 9px 20px; background: #ffc107; color: #212529; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 14px;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. VIEW MODAL -->
<div id="viewModal" class="modal-overlay">
    <div style="background: #fff; border-radius: 8px; width: 90%; max-width: 500px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #333; font-size: 18px;"><i class="fa-eye fa-solid" style="color: #0d6efd;"></i> Incident Details #<span id="view_id"></span></h3>
            <span onclick="closeModal('viewModal')" style="font-size: 22px; cursor: pointer; color: #aaa;">&times;</span>
        </div>
        <div style="line-height: 2; font-size: 14px; color: #333;">
            <p style="margin: 0;"><strong>Title:</strong> <span id="view_title"></span></p>
            <p style="margin: 0;"><strong>Incident Type:</strong> <span id="view_type"></span></p>
            <p style="margin: 0;"><strong>Zone:</strong> <span id="view_zone"></span></p>
            <p style="margin: 0;"><strong>Severity:</strong> <span id="view_severity"></span></p>
            <p style="margin: 0;"><strong>Status:</strong> <span id="view_status"></span></p>
            <p style="margin: 0;"><strong>Reported Date:</strong> <span id="view_date"></span></p>
        </div>
        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
            <button type="button" onclick="closeModal('viewModal')" style="padding: 8px 18px; background: #6c757d; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">Close</button>
        </div>
    </div>
</div>

<script>
function openModal(id) { 
    document.getElementById(id).style.display = 'flex'; 
}

function closeModal(id) { 
    document.getElementById(id).style.display = 'none'; 
}

function editIncident(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_id_label').innerText = data.id;
    document.getElementById('edit_title').value = data.title || '';
    document.getElementById('edit_incident_type').value = data.incident_type || 'Leak';
    document.getElementById('edit_zone_id').value = data.zone_id || '';
    document.getElementById('edit_severity').value = data.severity || 'Medium';
    document.getElementById('edit_status').value = data.status || 'Open';
    openModal('editModal');
}

function viewIncident(data) {
    document.getElementById('view_id').innerText = data.id;
    document.getElementById('view_title').innerText = data.title || 'N/A';
    document.getElementById('view_type').innerText = data.incident_type || 'N/A';
    document.getElementById('view_zone').innerText = data.zone_name || 'Unassigned';
    document.getElementById('view_severity').innerText = data.severity || 'Medium';
    document.getElementById('view_status').innerText = data.status || 'Open';
    document.getElementById('view_date').innerText = data.reported_at || 'N/A';
    openModal('viewModal');
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
};
</script>

<?php include 'includes/footer.php'; ?>