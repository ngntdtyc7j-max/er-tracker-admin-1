<?php
// admin/client.php
require_once __DIR__ . '/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid client ID");
}
$client_id = (int)$_GET['id'];

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update client core details
    if (isset($_POST['action']) && $_POST['action'] === 'update_client') {
        $status = $_POST['status'] === 'suspended' ? 'suspended' : 'active';
        $expiry_date = $_POST['expiry_date'] ?? date('Y-m-d');
        $notes = $_POST['notes'] ?? '';

        $stmt = $conn->prepare("UPDATE clients SET status = ?, expiry_date = ?, notes = ? WHERE id = ?");
        $stmt->bind_param('sssi', $status, $expiry_date, $notes, $client_id);
        $stmt->execute();

        $message = 'Client details updated.';
    }

    // Add authorised user
    if (isset($_POST['action']) && $_POST['action'] === 'add_auth_user') {
        $full_name = trim($_POST['full_name'] ?? '');
        if ($full_name !== '') {
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role_label = trim($_POST['role_label'] ?? '');

            $stmt = $conn->prepare("
                INSERT INTO client_authorised_users (client_id, full_name, email, phone, role_label)
                VALUES (?,?,?,?,?)
            ");
            $stmt->bind_param('issss', $client_id, $full_name, $email, $phone, $role_label);
            $stmt->execute();
            $message = 'Authorised user added.';
        }
    }

    // Toggle authorised user active/inactive
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_auth_user') {
        $auth_id = (int)($_POST['auth_id'] ?? 0);
        $is_active = (int)($_POST['is_active'] ?? 0);

        $stmt = $conn->prepare("UPDATE client_authorised_users SET is_active = ? WHERE id = ? AND client_id = ?");
        $stmt->bind_param('iii', $is_active, $auth_id, $client_id);
        $stmt->execute();
        $message = 'Authorised user updated.';
    }
}

// Fetch client
$stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->bind_param('i', $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

if (!$client) {
    die("Client not found.");
}

// Fetch authorised users
$stmt = $conn->prepare("
    SELECT *
    FROM client_authorised_users
    WHERE client_id = ?
    ORDER BY full_name ASC
");
$stmt->bind_param('i', $client_id);
$stmt->execute();
$authUsers = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Client — <?= e($client['name']) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-card">
  <div class="admin-header">
    <div>
      <div class="admin-title"><?= e($client['name']) ?></div>
      <div class="admin-sub">
        Code: <?= e($client['code']) ?> · Status: <?= e(ucfirst($client['status'])) ?>
      </div>
    </div>
    <div class="admin-links">
      <a href="index.php">← Back to clients</a>
    </div>
  </div>

  <?php if ($message): ?>
    <p class="alert-success"><?= e($message) ?></p>
  <?php endif; ?>

  <!-- Client core details -->
  <h2 class="section-title">Account</h2>
  <form method="post" class="mb-3">
    <input type="hidden" name="action" value="update_client">

    <div class="form-row">
      <label>Account Status</label>
      <select name="status">
        <option value="active" <?= $client['status']==='active' ? 'selected' : '' ?>>Active</option>
        <option value="suspended" <?= $client['status']==='suspended' ? 'selected' : '' ?>>Suspended</option>
      </select>
      <div class="small-note">Use “Suspended” to disable access for this client environment.</div>
    </div>

    <div class="form-row">
      <label>Expiry Date</label>
      <input type="date" name="expiry_date" value="<?= e($client['expiry_date']) ?>">
      <div class="small-note">Represents when their subscription / contract expires.</div>
    </div>

    <div class="form-row">
      <label>Notes</label>
      <textarea name="notes" rows="3"><?= e($client['notes']) ?></textarea>
    </div>

    <button type="submit" class="btn-primary mt-2">Save Client Changes</button>
  </form>

  <!-- Authorised users -->
  <h2 class="section-title">Authorised Users</h2>

  <?php if ($authUsers->num_rows > 0): ?>
    <table class="admin-table mb-2">
      <thead>
        <tr>
          <th>Name</th>
          <th>Details</th>
          <th class="text-right">Active</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($au = $authUsers->fetch_assoc()): ?>
          <tr>
            <td><?= e($au['full_name']) ?></td>
            <td>
              <?php if ($au['role_label']): ?>
                <strong><?= e($au['role_label']) ?></strong><br>
              <?php endif; ?>
              <?php if ($au['email']): ?>
                <?= e($au['email']) ?><br>
              <?php endif; ?>
              <?php if ($au['phone']): ?>
                <?= e($au['phone']) ?>
              <?php endif; ?>
            </td>
            <td class="text-right">
              <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="toggle_auth_user">
                <input type="hidden" name="auth_id" value="<?= (int)$au['id'] ?>">
                <input type="hidden" name="is_active" value="<?= $au['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="btn-primary" style="padding:4px 10px; font-size:12px;">
                  <?= $au['is_active'] ? 'Disable' : 'Enable' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="admin-sub">No authorised users yet.</p>
  <?php endif; ?>

  <!-- Add authorised user -->
  <h3 class="section-title">Add Authorised User</h3>
  <form method="post" class="mt-2">
    <input type="hidden" name="action" value="add_auth_user">

    <div class="form-row">
      <label>Full Name</label>
      <input type="text" name="full_name" required>
    </div>

    <div class="form-row">
      <label>Email</label>
      <input type="email" name="email">
    </div>

    <div class="form-row">
      <label>Phone</label>
      <input type="text" name="phone">
    </div>

    <div class="form-row">
      <label>Role / Relationship</label>
      <input type="text" name="role_label" placeholder="e.g. HR Manager, IT Lead">
    </div>

    <button type="submit" class="btn-primary mt-2">Add Authorised User</button>
  </form>
</div>

</body>
</html>
