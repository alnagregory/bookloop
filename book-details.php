<?php
/**
 * BOOKLOOP - Book Details & Transaction Flow
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bookId <= 0) {
    setFlash('error', 'Book listing not found.');
    redirect('books.php');
}

// Fetch book details and seller information
$stmt = $pdo->prepare("
    SELECT b.*, u.name as seller_name, u.college as seller_college, u.department as seller_department, 
           u.profile_image as seller_avatar, u.status as seller_status, u.created_at as seller_joined
    FROM books b
    JOIN users u ON b.user_id = u.user_id
    WHERE b.book_id = ?
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    setFlash('error', 'The requested book listing does not exist.');
    redirect('books.php');
}

// If pending/rejected, only admin or the owner can view
$authUserId = getAuthUserId();
$isOwner = ($authUserId && $authUserId == $book['user_id']);
if ($book['approval_status'] !== 'Approved' && !$isOwner && !isAdmin()) {
    setFlash('warning', 'This listing is currently pending administrative review.');
    redirect('books.php');
}

// Seller rating stats
$sellerRating = getUserRatingStats($pdo, $book['user_id']);

// Check wishlist status
$inWishlist = $authUserId ? isBookInWishlist($pdo, $authUserId, $book['book_id']) : false;

// If logged in, fetch user's own available books for potential exchange
$myBooks = [];
if ($authUserId && !$isOwner) {
    $myBStmt = $pdo->prepare("
        SELECT book_id, title, price, `condition` 
        FROM books 
        WHERE user_id = ? AND status = 'Available' AND approval_status = 'Approved'
    ");
    $myBStmt->execute([$authUserId]);
    $myBooks = $myBStmt->fetchAll();
}

// Handle Form Submissions: Buy Request, Exchange Request, or Report Listing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to perform this action.');
        redirect('login.php');
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security session expired. Please try again.');
        redirect('book-details.php?id=' . $bookId);
    }

    $action = $_POST['action'] ?? '';

    // Action 1: Buy Request
    if ($action === 'buy_request') {
        if ($isOwner) {
            setFlash('error', 'You cannot send a purchase request for your own book.');
            redirect('book-details.php?id=' . $bookId);
        }
        if ($book['status'] !== 'Available') {
            setFlash('error', 'This book is no longer available.');
            redirect('book-details.php?id=' . $bookId);
        }

        // Check if there is already an active pending request from this buyer
        $checkReq = $pdo->prepare("SELECT request_id FROM requests WHERE book_id = ? AND buyer_id = ? AND status = 'Pending'");
        $checkReq->execute([$bookId, $authUserId]);
        if ($checkReq->fetch()) {
            setFlash('warning', 'You already have a pending request for this book.');
            redirect('book-details.php?id=' . $bookId);
        }

        $offeredPrice = (float)($_POST['offered_price'] ?? $book['price']);
        $meetingPlace = clean($_POST['meeting_place'] ?? $book['location']);
        $deliveryOption = clean($_POST['delivery_option'] ?? 'Campus Meet-up');
        $message = clean($_POST['message'] ?? '');

        $insReq = $pdo->prepare("
            INSERT INTO requests (book_id, buyer_id, seller_id, request_type, offered_price, meeting_place, delivery_option, contact_method, message, status, created_at)
            VALUES (?, ?, ?, 'buy', ?, ?, ?, 'Chat on BookLoop', ?, 'Pending', NOW())
        ");
        $insReq->execute([$bookId, $authUserId, $book['user_id'], $offeredPrice, $meetingPlace, $deliveryOption, $message]);
        $newReqId = $pdo->lastInsertId();

        // Also initiate an intro message in chat automatically so communication is unified!
        $msgText = "Hello! I sent a purchase request for \"" . $book['title'] . "\" offering " . formatPrice($offeredPrice) . ". Delivery Method: " . $deliveryOption . " (" . $meetingPlace . "). Note: " . $message;
        $insMsg = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, book_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $insMsg->execute([$authUserId, $book['user_id'], $bookId, $msgText]);

        // Notify seller
        $buyerName = $_SESSION['user_name'] ?? 'A student';
        addNotification($pdo, $book['user_id'], "{$buyerName} sent a purchase request for \"{$book['title']}\" (" . formatPrice($offeredPrice) . "). Delivery: {$deliveryOption}", 'request_buy', $newReqId);

        setFlash('success', 'Your purchase request was submitted! The seller has been notified.');
        redirect('my-requests.php');
    }

    // Action 2: Exchange Request
    elseif ($action === 'exchange_request') {
        if ($isOwner) {
            setFlash('error', 'You cannot send an exchange request for your own listing.');
            redirect('book-details.php?id=' . $bookId);
        }
        if ($book['status'] !== 'Available') {
            setFlash('error', 'This book is no longer available.');
            redirect('book-details.php?id=' . $bookId);
        }

        $offeredBookId = (int)($_POST['offered_book_id'] ?? 0);
        $meetingPlace = clean($_POST['meeting_place'] ?? $book['location']);
        $deliveryOption = clean($_POST['delivery_option'] ?? 'Campus Meet-up');
        $message = clean($_POST['message'] ?? '');

        if ($offeredBookId <= 0) {
            setFlash('error', 'Please select one of your approved books to offer in exchange.');
            redirect('book-details.php?id=' . $bookId);
        }

        // Verify offered book belongs to current user
        $offStmt = $pdo->prepare("SELECT title FROM books WHERE book_id = ? AND user_id = ?");
        $offStmt->execute([$offeredBookId, $authUserId]);
        $offeredBook = $offStmt->fetch();

        if (!$offeredBook) {
            setFlash('error', 'Invalid offered textbook.');
            redirect('book-details.php?id=' . $bookId);
        }

        $insReq = $pdo->prepare("
            INSERT INTO requests (book_id, buyer_id, seller_id, request_type, offered_book_id, meeting_place, delivery_option, contact_method, message, status, created_at)
            VALUES (?, ?, ?, 'exchange', ?, ?, ?, 'Chat on BookLoop', ?, 'Pending', NOW())
        ");
        $insReq->execute([$bookId, $authUserId, $book['user_id'], $offeredBookId, $meetingPlace, $deliveryOption, $message]);
        $newReqId = $pdo->lastInsertId();

        // Message
        $msgText = "Hello! I would like to exchange my book \"" . $offeredBook['title'] . "\" for your \"" . $book['title'] . "\". Delivery/Meet: " . $deliveryOption . " (" . $meetingPlace . "). Note: " . $message;
        $insMsg = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, book_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $insMsg->execute([$authUserId, $book['user_id'], $bookId, $msgText]);

        // Notify
        $buyerName = $_SESSION['user_name'] ?? 'A student';
        addNotification($pdo, $book['user_id'], "{$buyerName} sent an exchange request for \"{$book['title']}\" offering \"{$offeredBook['title']}\".", 'request_exchange', $newReqId);

        setFlash('success', 'Exchange request sent successfully! You can track it in Sent Requests.');
        redirect('my-requests.php');
    }

    // Action 3: Report Listing
    elseif ($action === 'report_listing') {
        $reason = clean($_POST['reason'] ?? '');
        $description = clean($_POST['description'] ?? '');

        if (empty($reason)) {
            setFlash('error', 'Please select a reason for the report.');
            redirect('book-details.php?id=' . $bookId);
        }

        $insRep = $pdo->prepare("
            INSERT INTO reports (reporter_id, book_id, reported_user_id, reason, description, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        $insRep->execute([$authUserId, $bookId, $book['user_id'], $reason, $description]);

        setFlash('success', 'Thank you. Your report has been submitted for administrative review.');
        redirect('book-details.php?id=' . $bookId);
    }
}

$pageTitle = $book['title'] . " - Book Details";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div style="font-size:13px; color:var(--text-muted); margin-bottom:20px;">
  <a href="<?= BASE_URL ?>index.php">Home</a> &gt; 
  <a href="<?= BASE_URL ?>books.php">Browse Books</a> &gt; 
  <a href="<?= BASE_URL ?>books.php?department=<?= urlencode($book['department']) ?>"><?= e($book['department']) ?></a> &gt; 
  <span><?= e($book['title']) ?></span>
</div>

<div class="book-details-grid">
  <!-- Left Column: Gallery & Badges -->
  <div class="book-details-gallery">
    <div class="book-large-image">
      <img src="<?= BASE_URL ?>uploads/books/<?= e($book['image']) ?>" alt="<?= e($book['title']) ?>" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-alt); padding:12px 16px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
      <div>
        <div style="font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Condition</div>
        <div style="margin-top:2px;"><?= getConditionBadge($book['condition']) ?></div>
      </div>
      <div>
        <div style="font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Listing Type</div>
        <div style="margin-top:2px;"><?= getListingTypeBadge($book['listing_type']) ?></div>
      </div>
      <div>
        <div style="font-size:11px; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Status</div>
        <div style="margin-top:2px;"><?= getStatusBadge($book['status']) ?></div>
      </div>
    </div>

    <!-- Administrative Approval Notice if Pending -->
    <?php if ($book['approval_status'] !== 'Approved'): ?>
      <div class="alert alert-warning" style="margin:0;">
        <i class="fas fa-clock"></i> Listing Status: <strong><?= e($book['approval_status']) ?></strong>.
      </div>
    <?php endif; ?>
  </div>

  <!-- Right Column: Specs & Actions -->
  <div>
    <h1 style="font-size:28px; margin-bottom:6px;"><?= e($book['title']) ?></h1>
    <div style="font-size:16px; color:var(--text-muted); margin-bottom:16px;">
      By <strong><?= e($book['author']) ?></strong>
    </div>

    <!-- Price Header -->
    <div style="display:flex; align-items:baseline; gap:12px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--border-color);">
      <?php if ($book['listing_type'] === 'For Exchange'): ?>
        <span style="font-size:32px; font-weight:800; color:var(--secondary);">Available for Exchange</span>
      <?php else: ?>
        <span style="font-size:32px; font-weight:800; color:var(--primary);"><?= formatPrice($book['price']) ?></span>
        <?php if ($book['original_price'] > $book['price']): ?>
          <span style="font-size:16px; color:var(--text-light); text-decoration:line-through;"><?= formatPrice($book['original_price']) ?></span>
          <span class="badge" style="background:#dcfce7; color:#15803d; font-size:12px;">Save <?= round((($book['original_price'] - $book['price']) / $book['original_price']) * 100) ?>%</span>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Book Academic Specifications -->
    <table class="book-spec-table">
      <tr>
        <td>Subject / Course</td>
        <td><?= e($book['subject']) ?></td>
      </tr>
      <tr>
        <td>Department</td>
        <td><?= e($book['department']) ?></td>
      </tr>
      <tr>
        <td>Semester</td>
        <td>Semester <?= e($book['semester']) ?></td>
      </tr>
      <?php if (!empty($book['edition'])): ?>
      <tr>
        <td>Edition</td>
        <td><?= e($book['edition']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($book['publisher'])): ?>
      <tr>
        <td>Publisher</td>
        <td><?= e($book['publisher']) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (!empty($book['isbn'])): ?>
      <tr>
        <td>ISBN</td>
        <td><?= e($book['isbn']) ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td>Campus Meeting Point</td>
        <td><i class="fas fa-map-marker-alt" style="color:var(--danger);"></i> <?= e($book['location']) ?></td>
      </tr>
      <tr>
        <td>Delivery Options</td>
        <td><i class="fas fa-truck" style="color:var(--primary);"></i> <?= e($book['delivery_type'] ?? 'Campus Handover & Hostel Delivery') ?></td>
      </tr>
      <tr>
        <td>Listed On</td>
        <td><?= date('d F Y', strtotime($book['created_at'])) ?> (<?= timeAgo($book['created_at']) ?>)</td>
      </tr>
    </table>

    <!-- Description -->
    <div style="margin:20px 0;">
      <h3 style="font-size:16px; margin-bottom:8px;">Seller Notes & Description</h3>
      <div style="background:var(--bg-alt); padding:16px; border-radius:var(--radius-sm); font-size:14px; line-height:1.6; color:#334155; white-space:pre-line;">
        <?= e($book['description']) ?>
      </div>
    </div>

    <!-- Seller Profile Box -->
    <div class="seller-profile-card">
      <img src="<?= BASE_URL ?>uploads/profiles/<?= e($book['seller_avatar']) ?>" alt="Seller" class="seller-avatar" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
      <div style="flex:1;">
        <div style="display:flex; align-items:center; gap:8px;">
          <h4 style="margin:0; font-size:16px;"><?= e($book['seller_name']) ?></h4>
          <span class="badge" style="background:#e0f2fe; color:#0369a1;"><i class="fas fa-check-circle"></i> Student</span>
        </div>
        <div style="font-size:13px; color:var(--text-muted); margin-top:2px;">
          <?= e($book['seller_college']) ?> &bull; <?= e($book['seller_department']) ?>
        </div>
        <div style="display:flex; align-items:center; gap:6px; font-size:13px; margin-top:4px;">
          <span style="color:#f59e0b;"><i class="fas fa-star"></i> <?= $sellerRating['avg'] > 0 ? $sellerRating['avg'] . ' / 5.0' : 'New Seller' ?></span>
          <span style="color:var(--text-muted); font-size:12px;">(<?= $sellerRating['count'] ?> reviews)</span>
        </div>
      </div>
      <div>
        <a href="<?= BASE_URL ?>profile.php?id=<?= $book['user_id'] ?>" class="btn btn-sm btn-outline">View Profile</a>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="book-actions-bar">
      <?php if ($isOwner): ?>
        <a href="<?= BASE_URL ?>edit-book.php?id=<?= $book['book_id'] ?>" class="btn btn-primary">
          <i class="fas fa-edit"></i> Edit My Listing
        </a>
        <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-outline">
          <i class="fas fa-list"></i> Manage in My Listings
        </a>
      <?php else: ?>
        <?php if ($book['status'] === 'Available'): ?>
          <!-- Contact Seller -->
          <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $book['user_id'] ?>&book_id=<?= $book['book_id'] ?>" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-comments"></i> Contact Seller
          </a>

          <!-- Request to Buy Button -->
          <?php if ($book['listing_type'] !== 'For Exchange'): ?>
            <button type="button" class="btn btn-primary btn-lg" onclick="openModal('buyModal')">
              <i class="fas fa-shopping-cart"></i> Request to Buy
            </button>
          <?php endif; ?>

          <!-- Request Exchange Button -->
          <?php if ($book['listing_type'] !== 'For Sale'): ?>
            <button type="button" class="btn btn-secondary btn-lg" onclick="openModal('exchangeModal')">
              <i class="fas fa-sync-alt"></i> Request Exchange
            </button>
          <?php endif; ?>

          <!-- Wishlist Toggle -->
          <button type="button" class="btn btn-outline wishlist-toggle-btn <?= $inWishlist ? 'active' : '' ?>" data-book-id="<?= $book['book_id'] ?>" style="position:static; width:auto; height:auto; padding:12px 18px; border-radius:var(--radius-sm);">
            <i class="<?= $inWishlist ? 'fas' : 'far' ?> fa-heart"></i> <?= $inWishlist ? 'Saved in Wishlist' : 'Add to Wishlist' ?>
          </button>

          <!-- Report Listing Button -->
          <button type="button" class="btn btn-outline" style="color:var(--danger); border-color:transparent;" onclick="openModal('reportModal')">
            <i class="fas fa-flag"></i> Report
          </button>
        <?php else: ?>
          <div class="alert alert-info" style="margin:0; width:100%;">
            <i class="fas fa-info-circle"></i> This textbook has been marked as <strong><?= e($book['status']) ?></strong> and is no longer accepting requests.
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ==========================================
     BUY REQUEST MODAL
     ========================================== -->
<div class="modal-overlay" id="buyModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-shopping-bag"></i> Send Purchase Request</h3>
      <button type="button" class="modal-close" onclick="closeModal('buyModal')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>book-details.php?id=<?= $bookId ?>" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="buy_request">

      <div class="modal-body">
        <div style="background:var(--bg-alt); padding:12px; border-radius:var(--radius-sm); margin-bottom:16px; font-size:13px;">
          Listing: <strong><?= e($book['title']) ?></strong> &bull; Seller: <?= e($book['seller_name']) ?>
        </div>

        <div class="form-group">
          <label class="form-label required" for="offered_price">Your Offer (₹)</label>
          <input type="number" id="offered_price" name="offered_price" class="form-control" value="<?= e($book['price']) ?>" min="1" step="1" required>
          <span class="form-text">Listing price is <?= formatPrice($book['price']) ?>. You can offer a counter-price.</span>
        </div>

        <div class="form-group">
          <label class="form-label required" for="meeting_place">Preferred Campus Meeting Location</label>
          <input type="text" id="meeting_place" name="meeting_place" class="form-control" value="<?= e($book['location']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label required" for="delivery_option">Preferred Delivery / Handover Option</label>
          <select id="delivery_option" name="delivery_option" class="form-control" required>
            <option value="Campus Meet-up (Library / Canteen / Gate)">🏫 Campus Meet-up (Self Pick-up at Library / Canteen / Campus Gate)</option>
            <option value="Hostel / Room Delivery">🚪 Hostel / Room Delivery (Deliver to Hostel Gate)</option>
            <option value="Department Drop-off">🏛️ Department Drop-off (Between classes / Labs)</option>
            <option value="Courier / Speed Post">📦 Courier / Speed Post (Off-Campus Delivery)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label required" for="buy_msg">Message to Seller</label>
          <textarea id="buy_msg" name="message" class="form-control" rows="3" placeholder="e.g. Hi! I need this book for my class this semester. Can we meet after 3 PM today?" required></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('buyModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Request</button>
      </div>
    </form>
  </div>
</div>

<!-- ==========================================
     EXCHANGE REQUEST MODAL
     ========================================== -->
<div class="modal-overlay" id="exchangeModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-sync-alt"></i> Propose Book Exchange</h3>
      <button type="button" class="modal-close" onclick="closeModal('exchangeModal')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>book-details.php?id=<?= $bookId ?>" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="exchange_request">

      <div class="modal-body">
        <div style="background:var(--bg-alt); padding:12px; border-radius:var(--radius-sm); margin-bottom:16px; font-size:13px;">
          Requested Book: <strong><?= e($book['title']) ?></strong>
        </div>

        <?php if (empty($myBooks)): ?>
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> You do not have any active approved listings to offer in exchange yet.
          </div>
          <p style="font-size:13px; color:var(--text-muted);">Please add a book listing first to participate in textbook swaps.</p>
          <div style="margin-top:10px;">
            <a href="<?= BASE_URL ?>add-book.php" class="btn btn-sm btn-primary">Add a Book Listing Now</a>
          </div>
        <?php else: ?>
          <div class="form-group">
            <label class="form-label required" for="offered_book_id">Select Your Book to Offer</label>
            <select name="offered_book_id" id="offered_book_id" class="form-control" required>
              <option value="">-- Choose from your listings --</option>
              <?php foreach ($myBooks as $mb): ?>
                <option value="<?= $mb['book_id'] ?>"><?= e($mb['title']) ?> (<?= e($mb['condition']) ?>, Valued at <?= formatPrice($mb['price']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label required" for="ex_meeting">Preferred Meeting Spot</label>
            <input type="text" id="ex_meeting" name="meeting_place" class="form-control" value="<?= e($book['location']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label required" for="ex_delivery_option">Preferred Delivery / Handover Option</label>
            <select id="ex_delivery_option" name="delivery_option" class="form-control" required>
              <option value="Campus Meet-up (Library / Canteen / Gate)">🏫 Campus Meet-up (Self Pick-up at Library / Canteen / Campus Gate)</option>
              <option value="Hostel / Room Delivery">🚪 Hostel / Room Delivery (Deliver to Hostel Gate)</option>
              <option value="Department Drop-off">🏛️ Department Drop-off (Between classes / Labs)</option>
              <option value="Courier / Speed Post">📦 Courier / Speed Post (Off-Campus Delivery)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label required" for="ex_msg">Message</label>
            <textarea id="ex_msg" name="message" class="form-control" rows="3" placeholder="Explain why this exchange is a fair trade and when you are free on campus." required></textarea>
          </div>
        <?php endif; ?>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('exchangeModal')">Cancel</button>
        <?php if (!empty($myBooks)): ?>
          <button type="submit" class="btn btn-secondary"><i class="fas fa-paper-plane"></i> Send Exchange Request</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- ==========================================
     REPORT LISTING MODAL
     ========================================== -->
<div class="modal-overlay" id="reportModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" style="color:var(--danger);"><i class="fas fa-flag"></i> Report Listing</h3>
      <button type="button" class="modal-close" onclick="closeModal('reportModal')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>book-details.php?id=<?= $bookId ?>" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="report_listing">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label required" for="rep_reason">Reason for Report</label>
          <select name="reason" id="rep_reason" class="form-control" required>
            <option value="">-- Select Reason --</option>
            <option value="Fake or Misleading Book">Fake or Misleading Book</option>
            <option value="Wrong Subject / Edition Information">Wrong Subject / Edition Information</option>
            <option value="Inappropriate Content or Image">Inappropriate Content or Image</option>
            <option value="Spam / Duplicate Listing">Spam / Duplicate Listing</option>
            <option value="Suspicious User or Scam Attempt">Suspicious User or Scam Attempt</option>
            <option value="Other Policy Violation">Other Policy Violation</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="rep_desc">Additional Details</label>
          <textarea id="rep_desc" name="description" class="form-control" rows="3" placeholder="Please provide details so campus administrators can investigate."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('reportModal')">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-flag"></i> Submit Report</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
