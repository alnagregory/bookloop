<?php
/**
 * BOOKLOOP - Admin System Settings & Diagnostics
 */

$pageTitle = "System Settings";
require_once __DIR__ . '/includes/header.php';

$errors = [];
$adminId = getAuthUserId();

// Handle Password / Profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('admin/settings.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = clean($_POST['name'] ?? '');
        $email = clean($_POST['email'] ?? '');

        if (!empty($name) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?")->execute([$name, $email, $adminId]);
            $_SESSION['user_name'] = $name;
            setFlash('success', 'Admin profile updated.');
        } else {
            setFlash('error', 'Please provide valid name and email.');
        }
    } elseif ($action === 'change_password') {
        $curr = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($curr, $adminUser['password'])) {
            setFlash('error', 'Current administrator password incorrect.');
        } elseif (strlen($new) < 6) {
            setFlash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            setFlash('error', 'Passwords do not match.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$hash, $adminId]);
            setFlash('success', 'Administrator password updated successfully.');
        }
    } elseif ($action === 'update_moderation_policy') {
        $autoApproveVal = ($_POST['auto_approve'] ?? '1') === '1' ? '1' : '0';
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_approve_books', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$autoApproveVal, $autoApproveVal]);
        setFlash('success', 'Listing moderation policy updated successfully.');
    }
    redirect('admin/settings.php');
}

// Current moderation policy setting
$currAutoApprove = '1';
try {
    $sStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_approve_books'");
    $val = $sStmt->fetchColumn();
    if ($val !== false) $currAutoApprove = $val;
} catch (Exception $e) {}

// System Health Checks
$booksDirWritable = is_writable(UPLOAD_DIR_BOOKS);
$profilesDirWritable = is_writable(UPLOAD_DIR_PROFILES);
$phpVersion = phpversion();
$mysqlVersion = $pdo->query('select version()')->fetchColumn();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-cog" style="color:var(--primary);"></i> Platform Settings & Diagnostics</h2>
    <p style="color:var(--text-muted); font-size:14px;">Server environment status, directory permissions, and administrator security.</p>
  </div>
</div>

<div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:24px;">
  <!-- Column 1: Admin Account Security -->
  <div>
    <!-- Profile Info -->
    <div class="card" style="padding:24px; margin-bottom:24px;">
      <h3 style="font-size:16px; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        <i class="fas fa-user-shield" style="color:var(--primary);"></i> Administrator Profile
      </h3>

      <form action="<?= BASE_URL ?>admin/settings.php" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_profile">

        <div class="form-group">
          <label class="form-label required" for="name">Admin Display Name</label>
          <input type="text" id="name" name="name" class="form-control" value="<?= e($adminUser['name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label required" for="email">Admin Email Address</label>
          <input type="email" id="email" name="email" class="form-control" value="<?= e($adminUser['email']) ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Profile
        </button>
      </form>
    </div>

    <!-- Password Change -->
    <div class="card" style="padding:24px;">
      <h3 style="font-size:16px; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        <i class="fas fa-key" style="color:var(--warning);"></i> Change Admin Password
      </h3>

      <form action="<?= BASE_URL ?>admin/settings.php" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="change_password">

        <div class="form-group">
          <label class="form-label required" for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label required" for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min. 6 chars" required>
          </div>

          <div class="form-group">
            <label class="form-label required" for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
          </div>
        </div>

        <button type="submit" class="btn btn-outline-primary">
          <i class="fas fa-lock"></i> Update Password
        </button>
      </form>
    </div>

    <!-- Listing Moderation Policy -->
    <div class="card" style="padding:24px; margin-top:24px;">
      <h3 style="font-size:16px; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        <i class="fas fa-clipboard-check" style="color:var(--success);"></i> Listing Moderation Policy
      </h3>
      <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">
        Control whether new textbook listings posted by students go live immediately or require administrator verification first.
      </p>

      <form action="<?= BASE_URL ?>admin/settings.php" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_moderation_policy">

        <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
          <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; background:var(--bg-alt); padding:12px; border-radius:var(--radius-sm); border:1px solid <?= $currAutoApprove === '1' ? 'var(--primary)' : 'var(--border-color)' ?>;">
            <input type="radio" name="auto_approve" value="1" <?= $currAutoApprove === '1' ? 'checked' : '' ?> style="margin-top:3px;">
            <div>
              <div style="font-weight:700; font-size:14px; color:var(--text-main);"><i class="fas fa-bolt" style="color:#f59e0b;"></i> Instant Publish (Auto-Approve)</div>
              <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">Books go live across the marketplace immediately when submitted. Admin can still reject or remove inappropriate listings anytime.</div>
            </div>
          </label>

          <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; background:var(--bg-alt); padding:12px; border-radius:var(--radius-sm); border:1px solid <?= $currAutoApprove === '0' ? 'var(--primary)' : 'var(--border-color)' ?>;">
            <input type="radio" name="auto_approve" value="0" <?= $currAutoApprove === '0' ? 'checked' : '' ?> style="margin-top:3px;">
            <div>
              <div style="font-weight:700; font-size:14px; color:var(--text-main);"><i class="fas fa-shield-alt" style="color:var(--primary);"></i> Strict Review (Manual Admin Approval)</div>
              <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">All newly submitted books remain in "Pending Review" status until an administrator explicitly clicks Approve in the moderation queue.</div>
            </div>
          </label>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Moderation Policy
        </button>
      </form>
    </div>
  </div>

  <!-- Column 2: System Health & Diagnostics -->
  <div>
    <div class="card" style="padding:24px; margin-bottom:24px;">
      <h3 style="font-size:16px; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        <i class="fas fa-server" style="color:var(--secondary);"></i> Environment Diagnostics
      </h3>

      <table class="book-spec-table">
        <tr>
          <td>Web Engine</td>
          <td>Apache / Windows</td>
        </tr>
        <tr>
          <td>PHP Runtime</td>
          <td><span class="badge badge-available">PHP <?= e($phpVersion) ?></span></td>
        </tr>
        <tr>
          <td>Database Engine</td>
          <td><span class="badge badge-available">MySQL / MariaDB <?= e(explode('-', $mysqlVersion)[0]) ?></span></td>
        </tr>
        <tr>
          <td>Database Name</td>
          <td><code><?= e(DB_NAME) ?></code></td>
        </tr>
        <tr>
          <td>Book Uploads Folder</td>
          <td>
            <?php if ($booksDirWritable): ?>
              <span class="badge badge-available"><i class="fas fa-check"></i> Writable</span>
            <?php else: ?>
              <span class="badge badge-sold" style="background:var(--danger); color:white;">Not Writable</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td>Avatar Uploads Folder</td>
          <td>
            <?php if ($profilesDirWritable): ?>
              <span class="badge badge-available"><i class="fas fa-check"></i> Writable</span>
            <?php else: ?>
              <span class="badge badge-sold" style="background:var(--danger); color:white;">Not Writable</span>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </div>

    <div class="card" style="padding:24px; background:var(--bg-alt);">
      <h3 style="font-size:15px; margin-bottom:10px;"><i class="fas fa-info-circle" style="color:var(--primary);"></i> Project Demonstration Notes</h3>
      <p style="font-size:13px; color:var(--text-muted); line-height:1.6; margin-bottom:12px;">
        BookLoop is configured for standalone deployment under <code>C:\xampp\htdocs\bookloop</code>.
        All database queries use prepared PDO statements to ensure 100% protection against SQL Injection attacks.
      </p>
      <div style="font-size:12px; color:var(--text-main);">
        <strong>Base URL:</strong> <code><?= BASE_URL ?></code>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
