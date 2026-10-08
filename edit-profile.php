<?php
/**
 * BOOKLOOP - Edit Student Profile
 */

$pageTitle = "Edit Profile";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();
$user = getAuthUser($pdo);

$errors = [];
$departments = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Department' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expired. Please try again.";
    }

    $action = $_POST['action'] ?? 'update_profile';

    // 1. Update Profile Info & Avatar
    if ($action === 'update_profile') {
        $name = clean($_POST['name'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        $college = clean($_POST['college'] ?? '');
        $department = clean($_POST['department'] ?? '');
        $semester = (int)($_POST['semester'] ?? 1);
        $student_id = clean($_POST['student_id'] ?? '');

        if (empty($name)) $errors[] = "Full name is required.";
        if (empty($phone) || !preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $phone))) {
            $errors[] = "Valid 10-digit phone number is required.";
        }
        if (empty($college)) $errors[] = "College name is required.";
        if (empty($department)) $errors[] = "Department is required.";
        if ($semester < 1 || $semester > 8) $errors[] = "Semester must be between 1 and 8.";
        if (empty($student_id)) $errors[] = "Student ID is required.";

        $profileImage = $user['profile_image'];

        // Handle Avatar File Upload
        if (!empty($_FILES['profile_image']['name'])) {
            $uploadResult = uploadImageFile($_FILES['profile_image'], UPLOAD_DIR_PROFILES, 'user_' . $userId . '_');
            if ($uploadResult['success']) {
                $profileImage = $uploadResult['filename'];
            } else {
                $errors[] = $uploadResult['error'];
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, phone = ?, college = ?, department = ?, semester = ?, student_id = ?, profile_image = ?
                WHERE user_id = ?
            ");
            if ($stmt->execute([$name, $phone, $college, $department, $semester, $student_id, $profileImage, $userId])) {
                $_SESSION['user_name'] = $name;
                setFlash('success', 'Profile updated successfully!');
                redirect('profile.php');
            } else {
                $errors[] = "Could not update profile in database.";
            }
        }
    }

    // 2. Change Password
    elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPass, $user['password'])) {
            $errors[] = "Current password does not match.";
        } elseif (strlen($newPass) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } elseif ($newPass !== $confirmPass) {
            $errors[] = "New password confirmation does not match.";
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            if ($stmt->execute([$hash, $userId])) {
                addNotification($pdo, $userId, "Your account password was updated.", "security", null);
                setFlash('success', 'Password updated successfully!');
                redirect('profile.php');
            } else {
                $errors[] = "Could not change password.";
            }
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
    <a href="<?= BASE_URL ?>profile.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Profile</a>
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

  <!-- Personal Information Form -->
  <div class="card" style="padding:30px; margin-bottom:30px;">
    <h3 style="font-size:18px; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
      Student Information & Photo
    </h3>

    <form action="<?= BASE_URL ?>edit-profile.php" method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="update_profile">

      <!-- Photo Upload & Preview -->
      <div style="display:flex; align-items:center; gap:24px; margin-bottom:24px;">
        <img id="imageUploadPreview" src="<?= BASE_URL ?>uploads/profiles/<?= e($user['profile_image']) ?>" alt="Avatar" 
             style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:2px solid var(--border-color);"
             onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
        
        <div>
          <label class="form-label" for="imageUploadInput">Upload New Profile Picture</label>
          <input type="file" id="imageUploadInput" name="profile_image" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="form-control" style="font-size:12px;">
          <span class="form-text">JPG, PNG, or WEBP up to 5MB.</span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="name">Full Name</label>
          <input type="text" id="name" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">College Email (Read-only)</label>
          <input type="email" id="email" class="form-control" value="<?= e($user['email']) ?>" disabled style="background:#f1f5f9;">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label required" for="student_id">Roll / Student ID</label>
          <input type="text" id="student_id" name="student_id" class="form-control" value="<?= e($user['student_id']) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label required" for="college">College / University Name</label>
        <input type="text" id="college" name="college" class="form-control" value="<?= e($user['college']) ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="department">Department</label>
          <select id="department" name="department" class="form-control" required>
            <?php foreach ($departments as $dept): ?>
              <option value="<?= e($dept) ?>" <?= ($user['department'] === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label required" for="semester">Semester</label>
          <select id="semester" name="semester" class="form-control" required>
            <?php for ($s = 1; $s <= 8; $s++): ?>
              <option value="<?= $s ?>" <?= ($user['semester'] == $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg">
        <i class="fas fa-save"></i> Save Profile Changes
      </button>
    </form>
  </div>

  <!-- Change Password Card -->
  <div class="card" style="padding:30px;">
    <h3 style="font-size:18px; margin-bottom:20px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">
      Update Password
    </h3>

    <form action="<?= BASE_URL ?>edit-profile.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="change_password">

      <div class="form-group">
        <label class="form-label required" for="current_password">Current Password</label>
        <div class="input-password-wrapper">
          <input type="password" id="current_password" name="current_password" class="form-control" required>
          <button type="button" class="toggle-password" data-target="current_password" aria-label="Show password">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="new_password">New Password</label>
          <div class="input-password-wrapper">
            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min. 6 chars" required>
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
      </div>

      <button type="submit" class="btn btn-outline-primary">
        <i class="fas fa-key"></i> Update Password
      </button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
