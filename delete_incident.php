<?php
require_once __DIR__ . '/includes/auth.php';
require_authenticated_user();
require_once 'config/database.php';
require_once 'classes/incident.php';

$database = new Database();
$db = $database->getConnection();
$incident = new Incident($db);

$id = $_GET['id'] ?? null;
if ($id) {
    $incident->deleteIncident($id);
}

header("Location: incidents.php");
exit();
?>