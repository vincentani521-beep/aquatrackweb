<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
require_once 'classes/incident.php';

$database = new Database();
$db = $database->getConnection();
$incidentObj = new Incident($db);

// Process form submission if POST request is sent to index.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reported_by = $_POST['reported_by'] ?? '';
    $severity    = $_POST['severity'] ?? 'Low';
    $status      = $_POST['status'] ?? 'Open';
    $description = $_POST['description'] ?? '';

    if ($incidentObj->addIncident($reported_by, $severity, $status, $description)) {
        header("Location: incidents.php?status=success");
        exit();
    }
}

// Redirect to the main Incident Management Dashboard
header("Location: incidents.php");
exit();
?>