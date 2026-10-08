<?php
/**
 * BOOKLOOP - User Login Page
 */

$pageTitle = "Login to Your Account";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid session token. Please try again.";
    }

    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) $errors[] = "Please enter your email address.";
    if (empty($password)) $errors[] = "Please enter your password.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "No account found matching this email address.";
        } else {
            // Check status
            if ($user['status'] === 'suspended') {
                $errors[] = "Your account has been suspended by an administrator. Please reach out to support.";
            } elseif ($user['status'] === 'inactive') {
                $errors[] = "Your account is currently inactive. Please contact administration.";
            } elseif (!password_verify($password, $user['password'])) {
                $errors[] = "Incorrect password. Please verify and try again.";
            } else {
                // Successful login!
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                // Redirect logic
                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    $redirectUrl = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirectUrl);
                }
            }
        }
    }
}
?>

<div class="auth-wrapper">
  <div class="auth-header">
    <div class="brand-icon" style="margin: 0 auto 14px auto;">
      <i class="fas fa-sign-in-alt"></i>
    </div>
    <h2>Welcome to BookLoop</h2>
    <p>Sign in to your college student or administrator account.</p>
  </div>

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

  <form action="<?= BASE_URL ?>login.php" method="POST" novalidate>
    <?= csrfField() ?>

    <div class="form-group">
      <label class="form-label required" for="email">Email Address</label>
      <input type="email" id="email" name="email" class="form-control" placeholder="name@college.edu" value="<?= e($email) ?>" required autofocus>
    </div>

    <div class="form-group">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
        <label class="form-label required" for="password" style="margin:0;">Password</label>
        <a href="<?= BASE_URL ?>forgot-password.php" style="font-size:12px;">Forgot Password?</a>
      </div>
      <div class="input-password-wrapper">
        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your account password" required>
        <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
      <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
  </form>

  <!-- Demo Accounts Helper for Quick Viva Evaluation -->
  <div style="margin-top:24px; padding:14px; background:var(--bg-alt); border:1px dashed var(--border-color); border-radius:var(--radius-md); font-size:12px; color:var(--text-muted);">
    <strong style="color:var(--text-main); display:block; margin-bottom:6px;"><i class="fas fa-key"></i> Quick Demo Logins:</strong>
    <div style="display:flex; flex-direction:column; gap:4px;">
      <div><strong>Student:</strong> <code>rahul@college.edu</code> / <code>Student@123</code></div>
      <div><strong>Student 2:</strong> <code>priya@college.edu</code> / <code>Student@123</code></div>
      <div><strong>Admin:</strong> <code>admin@bookloop.com</code> / <code>Admin@123</code></div>
    </div>
  </div>

  <div class="auth-footer">
    New student on campus? <a href="<?= BASE_URL ?>register.php"><strong>Create an Account</strong></a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
