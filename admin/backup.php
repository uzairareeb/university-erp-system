<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') die("Access Denied");

// NOTE: the original backup.php ran `mysqldump` via exec() — that cannot
// run on Vercel serverless. Backups are done from TiDB Cloud instead.
// This page now only lists the backup log.
$logs = $conn->query("SELECT * FROM backup_log ORDER BY id DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Backup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light">
<div class="container mt-4">
  <a href="admin_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
  <div class="alert alert-info">Backups run from TiDB Cloud (Export), not from this server — <code>mysqldump</code> is disabled on Vercel.</div>
  <div class="card"><div class="card-header">Backup log</div><div class="card-body">
  <?php if ($logs && $logs->num_rows > 0) { while ($r = $logs->fetch_assoc()) { echo htmlspecialchars($r['file_name']) . ' — ' . htmlspecialchars($r['created_at']) . "<br>"; } } else { echo "<span class='text-muted'>No backups logged yet.</span>"; } ?>
  </div></div>
</div></body></html>
