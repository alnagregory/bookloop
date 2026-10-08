<?php
/**
 * BOOKLOOP - Administrator Login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in as admin, redirect
if (isAdmin()) {
    redirect('admin/dashboard.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expired. Please try again.";
    }

    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = "Please enter administrator email.";
    if (empty($password)) $errors[] = "Please enter administrator password.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "Invalid credentials.";
        } elseif ($user['role'] !== 'admin') {
            $errors[] = "Access denied. This portal is strictly for administrators. Please use the student login.";
        } elseif ($user['status'] !== 'active') {
            $errors[] = "This administrator account is disabled.";
        } elseif (!password_verify($password, $user['password'])) {
            $errors[] = "Incorrect administrator password.";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = 'admin';

            setFlash('success', 'Welcome to the BookLoop Management Portal, ' . htmlspecialchars($user['name']));
            redirect('admin/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | BookLoop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
</head>
<body style="background: #0f172a; display:flex; align-items:center; justify-content:center; min-height:100vh;">

<div class="auth-wrapper" style="background:white; max-width:440px; margin:20px; box-shadow:var(--shadow-xl);">
  <div class="auth-header">
    <div class="brand-icon" style="background:linear-gradient(135deg, #1e293b, #0f172a); margin:0 auto 12px auto;">
      <i class="fas fa-shield-alt"></i>
    </div>
    <h2 style="font-size:22px;">Administrator Portal</h2>
    <p style="font-size:13px; color:var(--text-muted);">Secure access for campus system operators</p>
  </div>

  <?php displayFlash(); ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i>
      <div>
        <?php foreach ($errors as $err): ?>
          <div><?= e($err) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <form action="<?= BASE_URL ?>admin/login.php" method="POST">
    <?= csrfField() ?>

    <div class="form-group">
      <label class="form-label required" for="email">Admin Email</label>
      <input type="email" id="email" name="email" class="form-control" placeholder="admin@bookloop.com" value="<?= e($email) ?>" required autofocus>
    </div>

    <div class="form-group">
      <label class="form-label required" for="password">Password</label>
      <div class="input-password-wrapper">
        <input type="password" id="password" name="password" class="form-control" placeholder="Enter admin password" required>
        <button type="button" class="toggle-password" data-target="password">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg" style="background:#0f172a; margin-top:10px;">
      <i class="fas fa-lock"></i> Authorize & Enter
    </button>
  </form>

  <div style="margin-top:20px; padding:12px; background:var(--bg-alt); border-radius:var(--radius-sm); font-size:12px; color:var(--text-muted);">
    <strong>Default Credentials:</strong><br>
    Email: <code>admin@bookloop.com</code><br>
    Password: <code>Admin@123</code>
  </div>

  <div class="auth-footer" style="padding-top:16px; margin-top:16px;">
    <a href="<?= BASE_URL ?>index.php">&larr; Return to Marketplace</a>
  </div>
</div>

<script src="<?= BASE_URL ?>js/script.js"></script>
</body>
</html>
