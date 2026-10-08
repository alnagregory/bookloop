<?php
/**
 * BOOKLOOP - Password Recovery
 */

$pageTitle = "Reset Password";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$step = 1;
$email = clean($_GET['email'] ?? '');
$errors = [];
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch. Please try again.";
    }

    $action = $_POST['action'] ?? 'request';

    if ($action === 'verify_email') {
        $email = clean($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid registered email address.";
        } else {
            $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $step = 2;
            } else {
                $errors[] = "No student account found with this email.";
            }
        }
    } elseif ($action === 'reset_password') {
        $email = clean($_POST['email'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (strlen($newPass) < 6) {
            $errors[] = "Password must be at least 6 characters.";
            $step = 2;
        } elseif ($newPass !== $confirmPass) {
            $errors[] = "Passwords do not match.";
            $step = 2;
        } else {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $up = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $up->execute([$hash, $user['user_id']]);

                addNotification($pdo, $user['user_id'], "Your account password was successfully reset.", "security", null);
                setFlash('success', 'Password reset successfully! You can now login with your new password.');
                redirect('login.php');
            } else {
                $errors[] = "Account not found.";
            }
        }
    }
}
?>

<div class="auth-wrapper">
  <div class="auth-header">
    <div class="brand-icon" style="margin: 0 auto 14px auto;">
      <i class="fas fa-key"></i>
    </div>
    <h2>Reset Password</h2>
    <p>Recover access to your student BookLoop account.</p>
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

  <?php if ($step === 1): ?>
    <form action="<?= BASE_URL ?>forgot-password.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="verify_email">

      <div class="form-group">
        <label class="form-label required" for="email">Registered College Email</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="name@college.edu" value="<?= e($email) ?>" required autofocus>
        <span class="form-text">We'll verify your student record to help you reset your password.</span>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
        <i class="fas fa-arrow-right"></i> Verify Account
      </button>
    </form>
  <?php else: ?>
    <form action="<?= BASE_URL ?>forgot-password.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="email" value="<?= e($email) ?>">

      <div class="alert alert-info" style="margin-bottom:16px;">
        <i class="fas fa-info-circle"></i> Account verified for: <strong><?= e($email) ?></strong>
      </div>

      <div class="form-group">
        <label class="form-label required" for="new_password">New Password</label>
        <div class="input-password-wrapper">
          <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required autofocus>
          <button type="button" class="toggle-password" data-target="new_password" aria-label="Show password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label required" for="confirm_password">Confirm New Password</label>
        <div class="input-password-wrapper">
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
          <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
        <i class="fas fa-check"></i> Set New Password
      </button>
    </form>
  <?php endif; ?>

  <div class="auth-footer">
    Remembered your password? <a href="<?= BASE_URL ?>login.php"><strong>Sign in</strong></a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
