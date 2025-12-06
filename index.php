<?php
// admin/index.php
require_once __DIR__ . '/config.php';

$message = '';
$error   = '';

// Handle new client creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_client') {
    $name   = trim($_POST['name'] ?? '');
    $code   = trim($_POST['code'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'suspended' ? 'suspended' : 'active';
    $expiry = $_POST['expiry_date'] ?? '';

    $notes  = $_POST['notes'] ?? '';

    if ($name === '' || $code === '' || $expiry === '') {
        $error = 'Please enter a name, code and expiry date.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO clients (name, code, status, expiry_date, notes)
            VALUES (?,?,?,?,?)
        ");
        $stmt->bind_param('sssss', $name, $code, $status, $expiry, $notes);

        if ($stmt->execute()) {
            $message = 'Client created successfully.';
        } else {
            $error = 'Failed to create client. Code might already exist.';
        }
    }
}

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
      <div class="admin-sub">Manage ER Tracker environments, license status and authorised contacts.</div>
    </div>
    <div class="admin-links">
      <a href="settings_users.php">Settings (Users)</a>
    </div>
  </div>

  <?php if ($message): ?>
    <p class="alert-success"><?= e($message) ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="alert-error"><?= e($error) ?></p>
  <?php endif; ?>

  <!-- Existing clients -->
  <h2 class="section-title">Existing Clients</h2>

  <?php if ($clients->num_rows === 0): ?>
    <p class="admin-sub">No clients found yet.</p>
  <?php else: ?>
    <table class="admin-table mb-3">
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

  <!-- Create new client -->
  <h2 class="section-title">Create New Client</h2>

  <form method="post" class="mt-2">
    <input type="hidden" name="action" value="create_client">

    <div class="form-row">
      <label>Client Name</label>
      <input type="text" name="name" required>
    </div>

    <div class="form-row">
      <label>Client Code</label>
      <input type="text" name="code" required placeholder="e.g. ECOTRICITY">
      <div class="small-note">Unique identifier used across systems.</div>
    </div>

    <div class="form-row">
      <label>Initial Status</label>
      <select name="status">
        <option value="active" selected>Active</option>
        <option value="suspended">Suspended</option>
      </select>
    </div>

    <div class="form-row">
      <label>Expiry Date</label>
      <input type="date" name="expiry_date" required>
    </div>

    <div class="form-row">
      <label>Notes</label>
      <textarea name="notes" rows="3" placeholder="Internal notes about this client (optional)."></textarea>
    </div>

    <button type="submit" class="btn-primary mt-2">Create Client</button>
  </form>
</div>

</body>
</html>
