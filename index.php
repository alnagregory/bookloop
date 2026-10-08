<?php
/**
 * BOOKLOOP - Home Page
 */

$pageTitle = "Buy, Sell & Exchange Academic Books";
require_once __DIR__ . '/includes/header.php';

// 1. Fetch Dynamic Platform Statistics
$statUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$statBooks = $pdo->query("SELECT COUNT(*) FROM books WHERE approval_status = 'Approved'")->fetchColumn();
$statSold = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'Sold'")->fetchColumn();
$statExchanged = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'Exchanged'")->fetchColumn();

// 2. Fetch Active Categories
$catStmt = $pdo->query("SELECT * FROM categories WHERE category_type = 'Department' AND status = 'active' LIMIT 12");
$departments = $catStmt->fetchAll();

// 3. Fetch Featured / Recently Added Approved Books
$bookStmt = $pdo->query("
    SELECT b.*, u.name as seller_name, u.college as seller_college 
    FROM books b 
    JOIN users u ON b.user_id = u.user_id 
    WHERE b.approval_status = 'Approved' 
    ORDER BY b.created_at DESC 
    LIMIT 8
");
$featuredBooks = $bookStmt->fetchAll();

$authUserId = getAuthUserId();
?>

<!-- Hero Section -->
<section class="hero-section" style="margin: -30px -20px 40px -20px;">
  <div class="container">
    <div class="hero-grid">
      <div class="hero-content">
        <span class="badge" style="background: rgba(255,255,255,0.15); color: #93c5fd; margin-bottom: 14px; padding: 6px 12px; font-size: 12px;">
          <i class="fas fa-graduation-cap"></i> College Peer-to-Peer Marketplace
        </span>
        <h1>Find Your Next Textbook at a Better Price</h1>
        <p>Buy affordable used academic books, sell the books you no longer need, or exchange them with fellow students.</p>
        
        <div class="hero-actions">
          <a href="<?= BASE_URL ?>books.php" class="btn btn-lg" style="background: white; color: var(--primary);">
            <i class="fas fa-search"></i> Browse Books
          </a>
          <a href="<?= BASE_URL ?>add-book.php" class="btn btn-lg btn-outline" style="border-color: rgba(255,255,255,0.4); color: white;">
            <i class="fas fa-plus"></i> Sell a Book
          </a>
        </div>

        <div class="hero-stats">
          <div class="hero-stat-item">
            <h3><?= number_format($statBooks) ?>+</h3>
            <p>Books Listed</p>
          </div>
          <div class="hero-stat-item">
            <h3><?= number_format($statUsers) ?>+</h3>
            <p>Verified Students</p>
          </div>
          <div class="hero-stat-item">
            <h3>₹<?= number_format(($statSold + $statExchanged) * 350) ?>+</h3>
            <p>Money Saved</p>
          </div>
        </div>
      </div>

      <div class="hero-visual">
        <div class="hero-visual-card">
          <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
            <div style="width:40px; height:40px; border-radius:50%; background:#2563eb; display:flex; align-items:center; justify-content:center; font-size:18px;">
              <i class="fas fa-exchange-alt"></i>
            </div>
            <div>
              <h4 style="color:white; margin:0; font-size:16px;">Safe Student Exchange</h4>
              <p style="margin:0; font-size:12px; color:#bfdbfe;">Campus-verified textbook swaps</p>
            </div>
          </div>
          <p style="font-size:13px; color:#e2e8f0; margin-bottom:14px; line-height:1.5;">
            "Got my Semester 4 DBMS & OS textbooks from seniors on campus. Saved over ₹1,200 and made new branch friends!"
          </p>
          <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; color:#93c5fd; border-top:1px solid rgba(255,255,255,0.15); padding-top:10px;">
            <span><i class="fas fa-star" style="color:#f59e0b;"></i> 4.9 Student Rating</span>
            <span>Zero Commission</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Homepage Large Search Bar -->
<div class="container">
  <form action="<?= BASE_URL ?>books.php" method="GET" class="home-search-bar">
    <div class="search-input-group">
      <i class="fas fa-search" style="color:var(--text-muted); font-size:18px;"></i>
      <input type="text" name="q" placeholder="Search for books, subjects, authors or ISBN..." autofocus>
    </div>
    <select name="department" aria-label="Department Filter">
      <option value="">All Departments</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?= e($dept['category_name']) ?>"><?= e($dept['category_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fas fa-arrow-right"></i> Search
    </button>
  </form>
</div>

<!-- Book Categories Section -->
<section style="margin: 60px 0 40px 0;">
  <div class="section-header">
    <h2>Academic Departments</h2>
    <p>Browse textbooks by discipline and curriculum requirements.</p>
  </div>

  <div class="categories-grid">
    <?php
      $deptIcons = [
        'Computer Science' => 'fa-laptop-code',
        'Information Technology' => 'fa-network-wired',
        'Electronics' => 'fa-microchip',
        'Electrical' => 'fa-bolt',
        'Mechanical' => 'fa-cogs',
        'Civil' => 'fa-building',
        'Mathematics' => 'fa-calculator',
        'Physics' => 'fa-atom',
        'Chemistry' => 'fa-flask',
        'Management' => 'fa-chart-line',
        'General' => 'fa-book-open',
      ];
      foreach ($departments as $dept): 
        $catName = $dept['category_name'];
        $icon = $deptIcons[$catName] ?? 'fa-book';
    ?>
      <a href="<?= BASE_URL ?>books.php?department=<?= urlencode($catName) ?>" class="category-card">
        <div class="category-icon">
          <i class="fas <?= $icon ?>"></i>
        </div>
        <span class="category-name"><?= e($catName) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Featured / Recent Books Section -->
<section style="margin: 60px 0;">
  <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px;">
    <div>
      <h2 style="font-size:28px; margin-bottom:6px;">Featured Textbooks</h2>
      <p style="color:var(--text-muted);">Recently listed books available for sale or exchange on campus.</p>
    </div>
    <a href="<?= BASE_URL ?>books.php" class="btn btn-outline">
      View All Books <i class="fas fa-arrow-right"></i>
    </a>
  </div>

  <?php if (empty($featuredBooks)): ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
      <h3 class="empty-state-title">No books listed yet</h3>
      <p class="empty-state-desc">Be the first student to list a textbook for sale or exchange!</p>
      <a href="<?= BASE_URL ?>add-book.php" class="btn btn-primary">Add Book Listing</a>
    </div>
  <?php else: ?>
    <div class="book-grid">
      <?php foreach ($featuredBooks as $b): 
        $inWishlist = $authUserId ? isBookInWishlist($pdo, $authUserId, $b['book_id']) : false;
        $isOwner = ($authUserId && $b['user_id'] == $authUserId);
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

            <div style="margin-bottom:10px;">
              <?= getConditionBadge($b['condition']) ?>
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

          <div class="book-card-footer">
            <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline btn-block">
              <i class="fas fa-eye"></i> View Details
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- How It Works Section -->
<section id="how-it-works" class="how-it-works-section" style="margin: 60px -20px 40px -20px; padding: 70px 20px;">
  <div class="container">
    <div class="section-header">
      <h2>How BookLoop Works</h2>
      <p>Four easy steps to buy, sell, or exchange academic textbooks within your campus.</p>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-number">1</div>
        <div class="step-icon"><i class="fas fa-search"></i></div>
        <h3 class="step-title">Find a Book</h3>
        <p class="step-desc">Search by course, author, subject, or ISBN to find the exact syllabus textbook you need.</p>
      </div>

      <div class="step-card">
        <div class="step-number">2</div>
        <div class="step-icon"><i class="fas fa-comments"></i></div>
        <h3 class="step-title">Contact the Student</h3>
        <p class="step-desc">Chat privately on the platform with fellow student owners without sharing personal contact details.</p>
      </div>

      <div class="step-card">
        <div class="step-number">3</div>
        <div class="step-icon"><i class="fas fa-handshake"></i></div>
        <h3 class="step-title">Send a Request</h3>
        <p class="step-desc">Send a formal purchase request with your price offer or an exchange request offering a book in trade.</p>
      </div>

      <div class="step-card">
        <div class="step-number">4</div>
        <div class="step-icon"><i class="fas fa-check-circle"></i></div>
        <h3 class="step-title">Complete the Deal</h3>
        <p class="step-desc">Meet safely in a campus common area, inspect the textbook condition, and conclude the deal.</p>
      </div>
    </div>
  </div>
</section>

<!-- Live Platform Statistics -->
<section class="stats-section">
  <div class="section-header">
    <h2>BookLoop Campus Community</h2>
    <p>Real-time statistics from our student network.</p>
  </div>

  <div class="stats-grid">
    <div class="stat-box">
      <div class="stat-box-icon"><i class="fas fa-book"></i></div>
      <div class="stat-box-number"><?= number_format($statBooks) ?></div>
      <div class="stat-box-label">Total Textbooks Listed</div>
    </div>

    <div class="stat-box">
      <div class="stat-box-icon" style="color:var(--secondary);"><i class="fas fa-user-graduate"></i></div>
      <div class="stat-box-number"><?= number_format($statUsers) ?></div>
      <div class="stat-box-label">Registered Students</div>
    </div>

    <div class="stat-box">
      <div class="stat-box-icon" style="color:var(--success);"><i class="fas fa-shopping-bag"></i></div>
      <div class="stat-box-number"><?= number_format($statSold) ?></div>
      <div class="stat-box-label">Books Sold</div>
    </div>

    <div class="stat-box">
      <div class="stat-box-icon" style="color:#7c3aed;"><i class="fas fa-sync-alt"></i></div>
      <div class="stat-box-number"><?= number_format($statExchanged) ?></div>
      <div class="stat-box-label">Books Exchanged</div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
