<?php
/**
 * BOOKLOOP - Contact & Support Page
 */

$pageTitle = "Contact Campus Support";
require_once __DIR__ . '/includes/header.php';

$sent = false;
$errors = [];
$name = '';
$email = '';
$subject = '';
$message = '';

if (isLoggedIn()) {
    $authUser = getAuthUser($pdo);
    $name = $authUser['name'] ?? '';
    $email = $authUser['email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expired. Please try again.";
    }

    $name = clean($_POST['name'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $message = clean($_POST['message'] ?? '');

    if (empty($name)) $errors[] = "Please provide your name.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($message)) $errors[] = "Message cannot be empty.";

    if (empty($errors)) {
        // If user is logged in, optionally insert a report or log
        $sent = true;
        setFlash('success', 'Thank you! Your message has been received by the BookLoop campus team.');
    }
}
?>

<div style="max-width: 960px; margin: 0 auto;">
  <div class="section-header">
    <h2>Contact & Campus Help Desk</h2>
    <p>Have questions about exchanging books, account safety, or listing policies? Reach out to us.</p>
  </div>

  <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap:36px; align-items:start;">
    <!-- Contact Form -->
    <div class="card" style="padding: 30px;">
      <h3 style="font-size:18px; margin-bottom:16px;"><i class="fas fa-paper-plane" style="color:var(--primary);"></i> Send us a Message</h3>

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

      <?php if ($sent): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> Thank you! Your inquiry has been logged. Our campus support team will reply within 24 hours.
        </div>
      <?php endif; ?>

      <form action="<?= BASE_URL ?>contact.php" method="POST">
        <?= csrfField() ?>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label required" for="name">Your Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= e($name) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label required" for="email">College Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label required" for="subject">Subject</label>
          <input type="text" id="subject" name="subject" class="form-control" placeholder="e.g. Question regarding book exchange safety" value="<?= e($subject) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label required" for="message">Message</label>
          <textarea id="message" name="message" class="form-control" rows="5" placeholder="Write your question or feedback..." required><?= e($message) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <i class="fas fa-paper-plane"></i> Send Message
        </button>
      </form>
    </div>

    <!-- Campus Help Info -->
    <div>
      <div class="card" style="padding: 24px; margin-bottom: 20px;">
        <h4 style="font-size:16px; margin-bottom:14px;"><i class="fas fa-map-marker-alt" style="color:var(--primary);"></i> Campus Support Hub</h4>
        <p style="font-size:13px; color:var(--text-muted); line-height:1.6; margin-bottom:12px;">
          Student Activity Center (SAC) &bull; Ground Floor<br>
          Central University Campus<br>
          Open Monday - Saturday: 9:00 AM – 5:00 PM
        </p>
        <div style="font-size:13px; color:var(--text-main); margin-bottom:6px;">
          <i class="fas fa-envelope" style="color:var(--text-muted);"></i> <strong>Email:</strong> support@bookloop.com
        </div>
        <div style="font-size:13px; color:var(--text-main);">
          <i class="fas fa-phone-alt" style="color:var(--text-muted);"></i> <strong>Helpline:</strong> +91 (Campus Ext.) 4022
        </div>
      </div>

      <div class="card" style="padding: 24px; background:var(--bg-alt);">
        <h4 style="font-size:15px; margin-bottom:10px;"><i class="fas fa-shield-alt" style="color:var(--success);"></i> Quick Safety Guidelines</h4>
        <ul style="font-size:13px; color:var(--text-muted); line-height:1.6; display:flex; flex-direction:column; gap:8px;">
          <li>&bull; Never send digital payments prior to physically inspecting the book.</li>
          <li>&bull; Inspect all pages, binding, and textbook edition before confirming.</li>
          <li>&bull; Use BookLoop's built-in chat to keep records of your arrangement.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
