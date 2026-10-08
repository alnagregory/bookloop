<?php
/**
 * BOOKLOOP - Student Registration
 */

$pageTitle = "Create Student Account";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$errors = [];
$name = '';
$email = '';
$phone = '';
$college = '';
$department = '';
$semester = '1';
$student_id = '';

// Fetch departments from database
$catStmt = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Department' AND status = 'active'");
$departments = $catStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid session token. Please try submitting again.";
    }

    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = clean($_POST['phone'] ?? '');
    $college = clean($_POST['college'] ?? '');
    $department = clean($_POST['department'] ?? '');
    $semester = (int)($_POST['semester'] ?? 1);
    $student_id = clean($_POST['student_id'] ?? '');

    // Server-side validation
    if (empty($name)) $errors[] = "Full name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid college email address is required.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $errors[] = "Please provide a valid 10-digit phone number.";
    }
    if (empty($college)) $errors[] = "College name is required.";
    if (empty($department)) $errors[] = "Academic department is required.";
    if ($semester < 1 || $semester > 8) $errors[] = "Please select a valid semester (1-8).";
    if (empty($student_id)) $errors[] = "Student ID or Roll Number is required.";

    // Check unique email
    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $errors[] = "An account with this email address already exists. Please log in.";
        }
    }

    // Insert user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("
            INSERT INTO users (name, email, password, phone, college, department, semester, student_id, profile_image, role, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'default_avatar.svg', 'student', 'active', NOW())
        ");

        if ($insertStmt->execute([$name, $email, $hashedPassword, $phone, $college, $department, $semester, $student_id])) {
            $newUserId = $pdo->lastInsertId();
            // Welcome notification
            addNotification($pdo, $newUserId, "Welcome to BookLoop! Explore textbooks or list your own to start saving.", "welcome", null);
            setFlash('success', 'Registration successful! You can now log in with your credentials.');
            redirect('login.php');
        } else {
            $errors[] = "A database error occurred during registration. Please try again.";
        }
    }
}
?>

<div class="auth-wrapper" style="max-width: 620px;">
  <div class="auth-header">
    <div class="brand-icon" style="margin: 0 auto 14px auto;">
      <i class="fas fa-user-plus"></i>
    </div>
    <h2>Join BookLoop</h2>
    <p>Create a verified student account to buy, sell, and swap textbooks on campus.</p>
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

  <form action="<?= BASE_URL ?>register.php" method="POST" novalidate>
    <?= csrfField() ?>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label required" for="name">Full Name</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Rahul Sharma" value="<?= e($name) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label required" for="email">College Email</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="name@college.edu" value="<?= e($email) ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label required" for="password">Password</label>
        <div class="input-password-wrapper">
          <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
          <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label required" for="confirm_password">Confirm Password</label>
        <div class="input-password-wrapper">
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
          <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label required" for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit mobile number" value="<?= e($phone) ?>" required>
        <span class="form-text">Kept private; only shared when you choose in chat.</span>
      </div>

      <div class="form-group">
        <label class="form-label required" for="student_id">Roll / Student ID</label>
        <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g. CS-2024-041" value="<?= e($student_id) ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label required" for="college">College / University Name</label>
      <input type="text" id="college" name="college" class="form-control" placeholder="e.g. National Institute of Technology" value="<?= e($college) ?>" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label required" for="department">Department</label>
        <select id="department" name="department" class="form-control" required>
          <option value="">Select Department</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept) ?>" <?= ($department === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label required" for="semester">Semester</label>
        <select id="semester" name="semester" class="form-control" required>
          <?php for ($s = 1; $s <= 8; $s++): ?>
            <option value="<?= $s ?>" <?= ($semester == $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
      <i class="fas fa-user-check"></i> Register Account
    </button>
  </form>

  <div class="auth-footer">
    Already registered on BookLoop? <a href="<?= BASE_URL ?>login.php"><strong>Log in here</strong></a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
