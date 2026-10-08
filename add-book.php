<?php
/**
 * BOOKLOOP - Add New Book Listing
 */

$pageTitle = "List a Textbook";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();
$errors = [];

// Fetch category dropdown options
$departments = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Department' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Subject' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

// Pre-fill user college as default location hint
$user = getAuthUser($pdo);
$defaultLocation = $user['college'] . " - Campus Library / Common Room";

$title = '';
$author = '';
$isbn = '';
$subject = '';
$department = $user['department'] ?? '';
$semester = $user['semester'] ?? 1;
$edition = '';
$publisher = '';
$original_price = '';
$price = '';
$condition = 'Good';
$listing_type = 'For Sale';
$description = '';
$location = $defaultLocation;
$preferred_contact = 'Chat on BookLoop';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expired. Please try submitting again.";
    }

    $title = clean($_POST['title'] ?? '');
    $author = clean($_POST['author'] ?? '');
    $isbn = clean($_POST['isbn'] ?? '');
    $subject = clean($_POST['subject'] ?? '');
    $department = clean($_POST['department'] ?? '');
    $semester = (int)($_POST['semester'] ?? 1);
    $edition = clean($_POST['edition'] ?? '');
    $publisher = clean($_POST['publisher'] ?? '');
    $original_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : 0.00;
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : 0.00;
    $condition = clean($_POST['condition'] ?? 'Good');
    $listing_type = clean($_POST['listing_type'] ?? 'For Sale');
    $description = clean($_POST['description'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $delivery_type = clean($_POST['delivery_type'] ?? 'Campus Handover & Hostel Delivery');
    $preferred_contact = clean($_POST['preferred_contact'] ?? 'Chat on BookLoop');

    // Validation
    if (empty($title)) $errors[] = "Book title is required.";
    if (empty($author)) $errors[] = "Author name is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($department)) $errors[] = "Department is required.";
    if ($semester < 1 || $semester > 8) $errors[] = "Valid semester (1-8) is required.";
    if ($listing_type !== 'For Exchange' && $price <= 0) {
        $errors[] = "Please specify a valid selling price greater than 0.";
    }
    if (empty($description)) $errors[] = "Please add a brief description of the book condition.";
    if (empty($location)) $errors[] = "Preferred campus meeting location is required.";

    // Handle Image Upload
    $imageFileName = 'default_book.svg';
    if (!empty($_FILES['book_image']['name'])) {
        $uploadResult = uploadImageFile($_FILES['book_image'], UPLOAD_DIR_BOOKS, 'book_' . $userId . '_');
        if ($uploadResult['success']) {
            $imageFileName = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }

    if (empty($errors)) {
        // Check system policy: if auto-approve is enabled or user is admin, approve immediately
        $autoApprove = true;
        try {
            $sStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_approve_books'");
            $val = $sStmt->fetchColumn();
            if ($val !== false) {
                $autoApprove = ($val === '1' || $val === 'true');
            }
        } catch (Exception $e) {}

        $approvalStatus = (isAdmin() || $autoApprove) ? 'Approved' : 'Pending';

        $stmt = $pdo->prepare("
            INSERT INTO books (
                user_id, title, author, isbn, subject, department, semester, edition, publisher,
                original_price, price, `condition`, listing_type, description, image, location,
                delivery_type, preferred_contact, status, approval_status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, 'Available', ?, NOW()
            )
        ");

        $executed = $stmt->execute([
            $userId, $title, $author, $isbn, $subject, $department, $semester, $edition, $publisher,
            $original_price, $price, $condition, $listing_type, $description, $imageFileName, $location,
            $delivery_type, $preferred_contact, $approvalStatus
        ]);

        if ($executed) {
            $newBookId = $pdo->lastInsertId();
            if ($approvalStatus === 'Approved') {
                setFlash('success', 'Your book listing has been published successfully!');
            } else {
                setFlash('info', 'Your book listing was submitted for administrative approval and will appear publicly once verified.');
            }
            redirect('my-listings.php');
        } else {
            $errors[] = "Database error while creating the listing.";
        }
    }
}
?>

<div style="max-width: 860px; margin: 0 auto;">
  <div class="section-header" style="text-align:left; margin-bottom:24px;">
    <h2><i class="fas fa-plus-circle" style="color:var(--primary);"></i> List a Textbook for Sale or Exchange</h2>
    <p>Help other students save money by selling or swapping your used course books.</p>
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

  <div class="card" style="padding: 30px;">
    <form action="<?= BASE_URL ?>add-book.php" method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>

      <!-- 1. Book Primary Details -->
      <h3 style="font-size:16px; margin-bottom:16px; color:var(--primary); border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        1. Book Overview & Syllabus Info
      </h3>

      <div class="form-row">
        <div class="form-group" style="flex:2;">
          <label class="form-label required" for="title">Textbook Title</label>
          <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Operating System Concepts" value="<?= e($title) ?>" required>
        </div>

        <div class="form-group" style="flex:1.5;">
          <label class="form-label required" for="author">Author(s)</label>
          <input type="text" id="author" name="author" class="form-control" placeholder="e.g. Silberschatz & Galvin" value="<?= e($author) ?>" required>
        </div>
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
          <label class="form-label required" for="subject">Subject</label>
          <input list="subject_suggestions" id="subject" name="subject" class="form-control" placeholder="Select or type subject" value="<?= e($subject) ?>" required>
          <datalist id="subject_suggestions">
            <?php foreach ($subjects as $sub): ?>
              <option value="<?= e($sub) ?>"></option>
            <?php endforeach; ?>
          </datalist>
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

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="edition">Edition (Optional)</label>
          <input type="text" id="edition" name="edition" class="form-control" placeholder="e.g. 10th Edition" value="<?= e($edition) ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="publisher">Publisher (Optional)</label>
          <input type="text" id="publisher" name="publisher" class="form-control" placeholder="e.g. Wiley, Pearson, McGraw-Hill" value="<?= e($publisher) ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="isbn">ISBN (Optional)</label>
          <input type="text" id="isbn" name="isbn" class="form-control" placeholder="10 or 13 digit ISBN" value="<?= e($isbn) ?>">
        </div>
      </div>

      <!-- 2. Pricing & Listing Intent -->
      <h3 style="font-size:16px; margin:24px 0 16px 0; color:var(--primary); border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        2. Pricing & Listing Mode
      </h3>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="listing_type">Listing Type</label>
          <select id="listing_type" name="listing_type" class="form-control" required>
            <option value="For Sale" <?= ($listing_type === 'For Sale') ? 'selected' : '' ?>>For Sale (Cash deal)</option>
            <option value="For Exchange" <?= ($listing_type === 'For Exchange') ? 'selected' : '' ?>>For Exchange (Textbook swap)</option>
            <option value="Sale or Exchange" <?= ($listing_type === 'Sale or Exchange') ? 'selected' : '' ?>>Sale or Exchange (Open to both)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label required" for="condition">Book Condition</label>
          <select id="condition" name="condition" class="form-control" required>
            <option value="Like New" <?= ($condition === 'Like New') ? 'selected' : '' ?>>Like New (Pristine, no marks)</option>
            <option value="Very Good" <?= ($condition === 'Very Good') ? 'selected' : '' ?>>Very Good (Light use, clean pages)</option>
            <option value="Good" <?= ($condition === 'Good') ? 'selected' : '' ?>>Good (Moderate use, minor highlighting)</option>
            <option value="Acceptable" <?= ($condition === 'Acceptable') ? 'selected' : '' ?>>Acceptable (Heavy use, readable)</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="original_price">Original Book Price (₹)</label>
          <input type="number" id="original_price" name="original_price" class="form-control" placeholder="MRP when new" value="<?= e($original_price) ?>" min="0" step="1">
          <span class="form-text">Used to highlight student discount.</span>
        </div>

        <div class="form-group">
          <label class="form-label required" for="price">Your Selling Price (₹)</label>
          <input type="number" id="price" name="price" class="form-control" placeholder="Amount in ₹ (or 0 for swap)" value="<?= e($price) ?>" min="0" step="1">
          <span class="form-text">Set 0 if listing purely for exchange.</span>
        </div>
      </div>

      <!-- 3. Cover Image & Preview -->
      <h3 style="font-size:16px; margin:24px 0 16px 0; color:var(--primary); border-bottom:1px solid var(--border-color); padding-bottom:8px;">
        3. Textbook Image & Campus Meeting Spot
      </h3>

      <div style="display:flex; gap:20px; align-items:start; margin-bottom:20px;">
        <div style="width:130px; height:170px; border:2px dashed var(--border-color); border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f8fafc; flex-shrink:0;">
          <img id="imageUploadPreview" src="<?= BASE_URL ?>uploads/books/default_book.svg" alt="Preview" style="width:100%; height:100%; object-fit:cover;">
        </div>

        <div style="flex:1;">
          <label class="form-label" for="imageUploadInput">Upload Textbook Photo</label>
          <input type="file" id="imageUploadInput" name="book_image" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="form-control">
          <span class="form-text">Supported formats: JPG, JPEG, PNG, WEBP, SVG (Max 5MB). Live preview shown on left.</span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="location">Campus Meeting Spot / Area</label>
          <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Central Library Lawn, Canteen Block" value="<?= e($location) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label required" for="delivery_type">Delivery & Handover Options</label>
          <select id="delivery_type" name="delivery_type" class="form-control" required>
            <option value="Campus Handover & Hostel Delivery">Campus Handover & Hostel Delivery (Flexible)</option>
            <option value="Campus Meet-up Only (Library / Canteen)">Campus Meet-up Only (Library / Canteen)</option>
            <option value="Hostel Gate Drop-off">Hostel Gate Drop-off</option>
            <option value="Department Lab / Foyer Handover">Department Lab / Foyer Handover</option>
            <option value="Campus Meet-up + Courier / Post Available">Campus Meet-up + Courier / Post Available</option>
          </select>
          <span class="form-text">Choose how you can deliver or hand over the book to the buyer.</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label required" for="description">Textbook Description & Notes</label>
        <textarea id="description" name="description" class="form-control" rows="4" data-max-chars="800" data-counter-id="descCounter" placeholder="Describe highlights, notes, completeness of pages, or books you want in exchange..." required><?= e($description) ?></textarea>
        <div style="display:flex; justify-content:space-between; margin-top:4px;">
          <span class="form-text">Help buyers understand book condition.</span>
          <span id="descCounter" style="font-size:12px; color:var(--text-muted);">800 characters left</span>
        </div>
      </div>

      <div style="display:flex; gap:12px; margin-top:24px;">
        <button type="submit" class="btn btn-primary btn-lg" style="flex:1;">
          <i class="fas fa-check"></i> Submit Listing
        </button>
        <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-outline btn-lg">
          Cancel
        </a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
