<?php
// admin/index.php
require_once __DIR__ . '/config.php';

// Fetch clients
$clients = $conn->query("
    SELECT id, name, code, status, expiry_date
    FROM clients
    ORDER BY name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ER Tracker Admin — Clients</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-card">
  <div class="admin-header">
    <div>
      <div class="admin-title">Clients</div>
      <div class="admin-sub">Manage ER Tracker environments and account status.</div>
    </div>
    <div class="admin-links">
      <a href="settings_users.php">Settings (Users)</a>
    </div>
  </div>

  <?php if ($clients->num_rows === 0): ?>
    <p class="admin-sub">No clients found yet. Add them directly in the database or via future UI.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Code</th>
          <th>Status</th>
          <th>Expiry</th>
          <th class="text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($c = $clients->fetch_assoc()): ?>
          <tr>
            <td><?= e($c['name']) ?></td>
            <td><?= e($c['code']) ?></td>
            <td><?= e(ucfirst($c['status'])) ?></td>
            <td><?= e($c['expiry_date']) ?></td>
            <td class="text-right">
              <a href="client.php?id=<?= (int)$c['id'] ?>" class="btn-primary" style="padding:6px 10px; font-size:13px;">View</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

</body>
</html>
