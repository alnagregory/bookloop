<?php
/**
 * BOOKLOOP - Edit Book Listing
 */

$pageTitle = "Edit Textbook Listing";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();
$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bookId <= 0) {
    setFlash('error', 'Invalid book listing ID.');
    redirect('my-listings.php');
}

// Fetch book and verify ownership
$stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    setFlash('error', 'Book not found.');
    redirect('my-listings.php');
}

if ($book['user_id'] != $userId && !isAdmin()) {
    setFlash('error', 'You are not authorized to edit this listing.');
    redirect('my-listings.php');
}

$errors = [];
$departments = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Department' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Subject' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expired. Please try again.";
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
    $status = clean($_POST['status'] ?? 'Available');
    $description = clean($_POST['description'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $delivery_type = clean($_POST['delivery_type'] ?? 'Campus Handover & Hostel Delivery');

    // Validation
    if (empty($title)) $errors[] = "Book title is required.";
    if (empty($author)) $errors[] = "Author name is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($department)) $errors[] = "Department is required.";
    if ($semester < 1 || $semester > 8) $errors[] = "Valid semester (1-8) is required.";
    if ($listing_type !== 'For Exchange' && $price <= 0) {
        $errors[] = "Valid price greater than 0 is required for sale listings.";
    }
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($location)) $errors[] = "Campus meeting location is required.";

    $imageFileName = $book['image'];
    if (!empty($_FILES['book_image']['name'])) {
        $uploadResult = uploadImageFile($_FILES['book_image'], UPLOAD_DIR_BOOKS, 'book_' . $userId . '_');
        if ($uploadResult['success']) {
            $imageFileName = $uploadResult['filename'];
        } else {
            $errors[] = $uploadResult['error'];
        }
    }

    if (empty($errors)) {
        $upStmt = $pdo->prepare("
            UPDATE books SET
                title = ?, author = ?, isbn = ?, subject = ?, department = ?, semester = ?,
                edition = ?, publisher = ?, original_price = ?, price = ?, `condition` = ?,
                listing_type = ?, status = ?, description = ?, image = ?, location = ?,
                delivery_type = ?, updated_at = NOW()
            WHERE book_id = ?
        ");

        if ($upStmt->execute([
            $title, $author, $isbn, $subject, $department, $semester,
            $edition, $publisher, $original_price, $price, $condition,
            $listing_type, $status, $description, $imageFileName, $location,
            $delivery_type, $bookId
        ])) {
            // If marked Sold or Exchanged, close other pending requests
            if ($status === 'Sold' || $status === 'Exchanged') {
                $closeReq = $pdo->prepare("UPDATE requests SET status = 'Rejected' WHERE book_id = ? AND status = 'Pending'");
                $closeReq->execute([$bookId]);
            }

            setFlash('success', 'Listing updated successfully!');
            redirect('my-listings.php');
        } else {
            $errors[] = "Database error while updating the listing.";
        }
    }
}
?>

<div style="max-width: 860px; margin: 0 auto;">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
    <h2><i class="fas fa-edit" style="color:var(--primary);"></i> Edit Textbook Listing</h2>
    <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to My Listings</a>
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
    <form action="<?= BASE_URL ?>edit-book.php?id=<?= $bookId ?>" method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>

      <div class="form-row">
        <div class="form-group" style="flex:2;">
          <label class="form-label required" for="title">Textbook Title</label>
          <input type="text" id="title" name="title" class="form-control" value="<?= e($book['title']) ?>" required>
        </div>

        <div class="form-group" style="flex:1.5;">
          <label class="form-label required" for="author">Author(s)</label>
          <input type="text" id="author" name="author" class="form-control" value="<?= e($book['author']) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="department">Department</label>
          <select id="department" name="department" class="form-control" required>
            <?php foreach ($departments as $dept): ?>
              <option value="<?= e($dept) ?>" <?= ($book['department'] === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label required" for="subject">Subject</label>
          <input list="sub_list" id="subject" name="subject" class="form-control" value="<?= e($book['subject']) ?>" required>
          <datalist id="sub_list">
            <?php foreach ($subjects as $sub): ?>
              <option value="<?= e($sub) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>

        <div class="form-group">
          <label class="form-label required" for="semester">Semester</label>
          <select id="semester" name="semester" class="form-control" required>
            <?php for ($s = 1; $s <= 8; $s++): ?>
              <option value="<?= $s ?>" <?= ($book['semester'] == $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="edition">Edition</label>
          <input type="text" id="edition" name="edition" class="form-control" value="<?= e($book['edition']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="publisher">Publisher</label>
          <input type="text" id="publisher" name="publisher" class="form-control" value="<?= e($book['publisher']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="isbn">ISBN</label>
          <input type="text" id="isbn" name="isbn" class="form-control" value="<?= e($book['isbn']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="listing_type">Listing Type</label>
          <select id="listing_type" name="listing_type" class="form-control" required>
            <option value="For Sale" <?= ($book['listing_type'] === 'For Sale') ? 'selected' : '' ?>>For Sale</option>
            <option value="For Exchange" <?= ($book['listing_type'] === 'For Exchange') ? 'selected' : '' ?>>For Exchange</option>
            <option value="Sale or Exchange" <?= ($book['listing_type'] === 'Sale or Exchange') ? 'selected' : '' ?>>Sale or Exchange</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label required" for="condition">Condition</label>
          <select id="condition" name="condition" class="form-control" required>
            <option value="Like New" <?= ($book['condition'] === 'Like New') ? 'selected' : '' ?>>Like New</option>
            <option value="Very Good" <?= ($book['condition'] === 'Very Good') ? 'selected' : '' ?>>Very Good</option>
            <option value="Good" <?= ($book['condition'] === 'Good') ? 'selected' : '' ?>>Good</option>
            <option value="Acceptable" <?= ($book['condition'] === 'Acceptable') ? 'selected' : '' ?>>Acceptable</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label required" for="status">Listing Availability Status</label>
          <select id="status" name="status" class="form-control" required>
            <option value="Available" <?= ($book['status'] === 'Available') ? 'selected' : '' ?>>Available</option>
            <option value="Pending" <?= ($book['status'] === 'Pending') ? 'selected' : '' ?>>Pending Deal</option>
            <option value="Sold" <?= ($book['status'] === 'Sold') ? 'selected' : '' ?>>Mark as Sold</option>
            <option value="Exchanged" <?= ($book['status'] === 'Exchanged') ? 'selected' : '' ?>>Mark as Exchanged</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="original_price">Original Price (₹)</label>
          <input type="number" id="original_price" name="original_price" class="form-control" value="<?= e($book['original_price']) ?>" min="0">
        </div>

        <div class="form-group">
          <label class="form-label required" for="price">Selling Price (₹)</label>
          <input type="number" id="price" name="price" class="form-control" value="<?= e($book['price']) ?>" min="0" required>
        </div>
      </div>

      <!-- Image replacement -->
      <div style="display:flex; gap:20px; align-items:start; margin:20px 0;">
        <img id="imageUploadPreview" src="<?= BASE_URL ?>uploads/books/<?= e($book['image']) ?>" alt="Cover" 
             style="width:110px; height:150px; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--border-color);"
             onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
        
        <div style="flex:1;">
          <label class="form-label" for="imageUploadInput">Replace Cover Image</label>
          <input type="file" id="imageUploadInput" name="book_image" accept="image/jpeg,image/png,image/webp,image/svg+xml" class="form-control">
          <span class="form-text">Leave blank to keep current cover image.</span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="location">Campus Meeting Point / Area</label>
          <input type="text" id="location" name="location" class="form-control" value="<?= e($book['location']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label required" for="delivery_type">Delivery & Handover Options</label>
          <select id="delivery_type" name="delivery_type" class="form-control" required>
            <?php
              $currentDel = $book['delivery_type'] ?? 'Campus Handover & Hostel Delivery';
              $delOptions = [
                'Campus Handover & Hostel Delivery' => 'Campus Handover & Hostel Delivery (Flexible)',
                'Campus Meet-up Only (Library / Canteen)' => 'Campus Meet-up Only (Library / Canteen)',
                'Hostel Gate Drop-off' => 'Hostel Gate Drop-off',
                'Department Lab / Foyer Handover' => 'Department Lab / Foyer Handover',
                'Campus Meet-up + Courier / Post Available' => 'Campus Meet-up + Courier / Post Available'
              ];
              foreach ($delOptions as $val => $label):
            ?>
              <option value="<?= e($val) ?>" <?= ($currentDel === $val) ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label required" for="description">Description</label>
        <textarea id="description" name="description" class="form-control" rows="4" required><?= e($book['description']) ?></textarea>
      </div>

      <div style="display:flex; gap:12px; margin-top:24px;">
        <button type="submit" class="btn btn-primary btn-lg" style="flex:1;">
          <i class="fas fa-save"></i> Save Changes
        </button>
        <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-outline btn-lg">
          Cancel
        </a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
