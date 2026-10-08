<?php
/**
 * BOOKLOOP - Browse Books Marketplace
 */

$pageTitle = "Browse Academic Textbooks";
require_once __DIR__ . '/includes/header.php';

$authUserId = getAuthUserId();

// Collect filters & search parameters
$search = clean($_GET['q'] ?? '');
$filterDept = clean($_GET['department'] ?? '');
$filterSubject = clean($_GET['subject'] ?? '');
$filterSemester = clean($_GET['semester'] ?? '');
$filterCondition = clean($_GET['condition'] ?? '');
$filterType = clean($_GET['listing_type'] ?? '');
$filterStatus = clean($_GET['status'] ?? 'Available');
$filterMinPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$filterMaxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$filterLocation = clean($_GET['location'] ?? '');
$sort = clean($_GET['sort'] ?? 'newest');

// Base query for approved books
$sql = "
    SELECT b.*, u.name as seller_name, u.college as seller_college 
    FROM books b 
    JOIN users u ON b.user_id = u.user_id 
    WHERE b.approval_status = 'Approved'
";
$params = [];

// Apply Search (Title, Author, ISBN, Subject)
if (!empty($search)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.subject LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Department filter
if (!empty($filterDept)) {
    $sql .= " AND b.department = ?";
    $params[] = $filterDept;
}

// Subject filter
if (!empty($filterSubject)) {
    $sql .= " AND b.subject = ?";
    $params[] = $filterSubject;
}

// Semester filter
if (!empty($filterSemester)) {
    $sql .= " AND b.semester = ?";
    $params[] = (int)$filterSemester;
}

// Condition filter
if (!empty($filterCondition)) {
    $sql .= " AND b.`condition` = ?";
    $params[] = $filterCondition;
}

// Listing Type filter
if (!empty($filterType)) {
    $sql .= " AND b.listing_type = ?";
    $params[] = $filterType;
}

// Status filter
if ($filterStatus !== 'all' && !empty($filterStatus)) {
    $sql .= " AND b.status = ?";
    $params[] = $filterStatus;
}

// Min Price
if ($filterMinPrice !== null) {
    $sql .= " AND b.price >= ?";
    $params[] = $filterMinPrice;
}

// Max Price
if ($filterMaxPrice !== null) {
    $sql .= " AND b.price <= ?";
    $params[] = $filterMaxPrice;
}

// Location
if (!empty($filterLocation)) {
    $sql .= " AND b.location LIKE ?";
    $params[] = "%{$filterLocation}%";
}

// Sorting logic
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY b.created_at ASC";
        break;
    case 'price_low':
        $sql .= " ORDER BY b.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY b.price DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY b.created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Fetch filter category options
$departments = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Department' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
$subjects = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Subject' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="browse-layout">
  <!-- Filters Sidebar -->
  <aside class="filter-sidebar">
    <div class="filter-header">
      <h3 style="font-size:16px; margin:0;"><i class="fas fa-sliders-h"></i> Filters</h3>
      <a href="<?= BASE_URL ?>books.php" style="font-size:12px; color:var(--danger);">Reset All</a>
    </div>

    <form action="<?= BASE_URL ?>books.php" method="GET" id="filterForm">
      <?php if (!empty($search)): ?>
        <input type="hidden" name="q" value="<?= e($search) ?>">
      <?php endif; ?>

      <!-- Department Filter -->
      <div class="filter-group">
        <label class="filter-title" for="filterDept">Department</label>
        <select name="department" id="filterDept" class="form-control" onchange="this.form.submit()">
          <option value="">All Departments</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?= e($dept) ?>" <?= ($filterDept === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Subject Filter -->
      <div class="filter-group">
        <label class="filter-title" for="filterSub">Subject</label>
        <select name="subject" id="filterSub" class="form-control" onchange="this.form.submit()">
          <option value="">All Subjects</option>
          <?php foreach ($subjects as $sub): ?>
            <option value="<?= e($sub) ?>" <?= ($filterSubject === $sub) ? 'selected' : '' ?>><?= e($sub) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Semester Filter -->
      <div class="filter-group">
        <label class="filter-title" for="filterSem">Semester</label>
        <select name="semester" id="filterSem" class="form-control" onchange="this.form.submit()">
          <option value="">All Semesters</option>
          <?php for ($s = 1; $s <= 8; $s++): ?>
            <option value="<?= $s ?>" <?= ($filterSemester == $s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <!-- Listing Type -->
      <div class="filter-group">
        <label class="filter-title" for="filterType">Listing Type</label>
        <select name="listing_type" id="filterType" class="form-control" onchange="this.form.submit()">
          <option value="">All Types</option>
          <option value="For Sale" <?= ($filterType === 'For Sale') ? 'selected' : '' ?>>For Sale</option>
          <option value="For Exchange" <?= ($filterType === 'For Exchange') ? 'selected' : '' ?>>For Exchange</option>
          <option value="Sale or Exchange" <?= ($filterType === 'Sale or Exchange') ? 'selected' : '' ?>>Sale or Exchange</option>
        </select>
      </div>

      <!-- Condition -->
      <div class="filter-group">
        <label class="filter-title" for="filterCond">Condition</label>
        <select name="condition" id="filterCond" class="form-control" onchange="this.form.submit()">
          <option value="">Any Condition</option>
          <option value="Like New" <?= ($filterCondition === 'Like New') ? 'selected' : '' ?>>Like New</option>
          <option value="Very Good" <?= ($filterCondition === 'Very Good') ? 'selected' : '' ?>>Very Good</option>
          <option value="Good" <?= ($filterCondition === 'Good') ? 'selected' : '' ?>>Good</option>
          <option value="Acceptable" <?= ($filterCondition === 'Acceptable') ? 'selected' : '' ?>>Acceptable</option>
        </select>
      </div>

      <!-- Status Availability -->
      <div class="filter-group">
        <label class="filter-title" for="filterStat">Availability</label>
        <select name="status" id="filterStat" class="form-control" onchange="this.form.submit()">
          <option value="Available" <?= ($filterStatus === 'Available') ? 'selected' : '' ?>>Available Only</option>
          <option value="all" <?= ($filterStatus === 'all') ? 'selected' : '' ?>>All (Including Sold/Exchanged)</option>
          <option value="Sold" <?= ($filterStatus === 'Sold') ? 'selected' : '' ?>>Sold</option>
          <option value="Exchanged" <?= ($filterStatus === 'Exchanged') ? 'selected' : '' ?>>Exchanged</option>
        </select>
      </div>

      <!-- Price Range -->
      <div class="filter-group">
        <label class="filter-title">Price Range (₹)</label>
        <div style="display:flex; gap:8px;">
          <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?= e($filterMinPrice ?? '') ?>" min="0">
          <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?= e($filterMaxPrice ?? '') ?>" min="0">
        </div>
      </div>

      <!-- Location Keyword -->
      <div class="filter-group">
        <label class="filter-title" for="filterLoc">Campus Spot</label>
        <input type="text" name="location" id="filterLoc" class="form-control" placeholder="e.g. Library, Hostel" value="<?= e($filterLocation) ?>">
      </div>

      <button type="submit" class="btn btn-primary btn-block">
        <i class="fas fa-filter"></i> Apply Filters
      </button>
    </form>
  </aside>

  <!-- Marketplace Main Content -->
  <div>
    <!-- Browse Toolbar -->
    <div class="browse-toolbar">
      <div>
        <span style="font-weight:600; color:var(--text-main);"><?= count($books) ?> Textbooks</span>
        <?php if (!empty($search)): ?>
          <span style="color:var(--text-muted); font-size:13px;"> matching "<strong><?= e($search) ?></strong>"</span>
        <?php endif; ?>
      </div>

      <div style="display:flex; align-items:center; gap:10px;">
        <label for="sortSelect" style="font-size:13px; color:var(--text-muted); font-weight:500;">Sort By:</label>
        <select id="sortSelect" class="form-control" style="width:auto; padding:6px 12px; font-size:13px;" onchange="
          let url = new URL(window.location.href);
          url.searchParams.set('sort', this.value);
          window.location.href = url.toString();
        ">
          <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Newest First</option>
          <option value="oldest" <?= ($sort === 'oldest') ? 'selected' : '' ?>>Oldest First</option>
          <option value="price_low" <?= ($sort === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_high" <?= ($sort === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
        </select>
      </div>
    </div>

    <!-- Active Filter Tags / Chips -->
    <?php if (!empty($filterDept) || !empty($filterSubject) || !empty($filterSemester) || !empty($filterCondition) || !empty($filterType) || !empty($search)): ?>
      <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px;">
        <?php if (!empty($search)): ?>
          <span class="badge" style="background:#e2e8f0; color:#334155; padding:6px 10px;">Query: <?= e($search) ?></span>
        <?php endif; ?>
        <?php if (!empty($filterDept)): ?>
          <span class="badge" style="background:#e0f2fe; color:#0369a1; padding:6px 10px;">Dept: <?= e($filterDept) ?></span>
        <?php endif; ?>
        <?php if (!empty($filterSubject)): ?>
          <span class="badge" style="background:#f0fdf4; color:#15803d; padding:6px 10px;">Subject: <?= e($filterSubject) ?></span>
        <?php endif; ?>
        <?php if (!empty($filterSemester)): ?>
          <span class="badge" style="background:#fef3c7; color:#b45309; padding:6px 10px;">Sem: <?= e($filterSemester) ?></span>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>books.php" style="font-size:12px; align-self:center; color:var(--danger); margin-left:4px;">Clear filters</a>
      </div>
    <?php endif; ?>

    <!-- Books Listing Grid -->
    <?php if (empty($books)): ?>
      <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-search-minus"></i></div>
        <h3 class="empty-state-title">No books found</h3>
        <p class="empty-state-desc">Try clearing your search query or adjusting your filters to find what you are looking for.</p>
        <a href="<?= BASE_URL ?>books.php" class="btn btn-outline">Clear All Filters</a>
      </div>
    <?php else: ?>
      <div class="book-grid">
        <?php foreach ($books as $b): 
          $inWishlist = $authUserId ? isBookInWishlist($pdo, $authUserId, $b['book_id']) : false;
          $isOwner = ($authUserId && $b['user_id'] == $authUserId);
          $isAvailable = ($b['status'] === 'Available');
        ?>
          <div class="book-card">
            <div class="book-card-img-wrapper">
              <div class="book-card-badges">
                <?= getListingTypeBadge($b['listing_type']) ?>
                <?= getStatusBadge($b['status']) ?>
              </div>

              <?php if (!$isOwner): ?>
                <button type="button" class="wishlist-toggle-btn <?= $inWishlist ? 'active' : '' ?>" data-book-id="<?= $b['book_id'] ?>" title="Save to Wishlist">
                  <i class="<?= $inWishlist ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
              <?php endif; ?>

              <img src="<?= BASE_URL ?>uploads/books/<?= e($b['image']) ?>" alt="<?= e($b['title']) ?>" class="book-card-img" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
            </div>

            <div class="book-card-body">
              <div class="book-card-meta">
                <span><i class="fas fa-graduation-cap"></i> <?= e($b['department']) ?></span>
                <span>&bull;</span>
                <span>Sem <?= e($b['semester']) ?></span>
              </div>

              <h3 class="book-card-title" title="<?= e($b['title']) ?>">
                <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>"><?= e($b['title']) ?></a>
              </h3>

              <div class="book-card-author">
                <i class="fas fa-user-edit" style="font-size:11px;"></i> <?= e($b['author']) ?>
              </div>

              <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
                <?= getConditionBadge($b['condition']) ?>
                <span style="font-size:11px; color:var(--text-muted);"><i class="fas fa-clock"></i> <?= timeAgo($b['created_at']) ?></span>
              </div>

              <div class="book-card-details-row">
                <div class="book-price-block">
                  <?php if ($b['listing_type'] === 'For Exchange'): ?>
                    <span class="book-price" style="color:var(--secondary); font-size:16px;">Exchange</span>
                  <?php else: ?>
                    <span class="book-price"><?= formatPrice($b['price']) ?></span>
                    <?php if ($b['original_price'] > $b['price']): ?>
                      <span class="book-original-price"><?= formatPrice($b['original_price']) ?></span>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>

                <div style="font-size:12px; color:var(--text-muted); text-align:right;">
                  <i class="fas fa-map-marker-alt"></i> <?= e(explode(',', $b['location'])[0]) ?>
                </div>
              </div>
            </div>

            <!-- Card Actions -->
            <div class="book-card-footer" style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
              <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline" style="grid-column: span 2;">
                <i class="fas fa-eye"></i> View Details
              </a>

              <?php if (!$isOwner && $isAvailable): ?>
                <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $b['user_id'] ?>&book_id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline-primary" title="Private Chat">
                  <i class="fas fa-comment"></i> Contact
                </a>
                
                <?php if ($b['listing_type'] === 'For Exchange'): ?>
                  <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>#exchange" class="btn btn-sm btn-secondary" title="Request Exchange">
                    <i class="fas fa-sync"></i> Exchange
                  </a>
                <?php else: ?>
                  <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>#buy" class="btn btn-sm btn-primary" title="Request to Buy">
                    <i class="fas fa-shopping-cart"></i> Buy
                  </a>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
