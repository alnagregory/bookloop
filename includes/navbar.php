<?php
/**
 * BOOKLOOP - Navigation Bar Component
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
  <div class="container">
    <nav class="navbar">
      <!-- Brand Logo -->
      <a href="<?= BASE_URL ?>index.php" class="nav-brand">
        <div class="brand-icon">
          <i class="fas fa-book-open"></i>
        </div>
        <div class="brand-text">
          <span class="brand-name">Book<span>Loop</span></span>
          <span class="brand-tagline">Buy. Sell. Exchange.</span>
        </div>
      </a>

      <!-- Mobile Hamburger Toggle -->
      <button class="nav-toggle" id="navToggle" aria-label="Toggle Navigation">
        <i class="fas fa-bars"></i>
      </button>

      <!-- Navigation Links -->
      <div class="nav-links" id="navLinks">
        <a href="<?= BASE_URL ?>index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
          <i class="fas fa-home"></i> Home
        </a>
        <a href="<?= BASE_URL ?>books.php" class="nav-link <?= $currentPage === 'books.php' ? 'active' : '' ?>">
          <i class="fas fa-book"></i> Browse Books
        </a>

        <?php if (isLoggedIn()): ?>
          <a href="<?= BASE_URL ?>add-book.php" class="nav-link <?= $currentPage === 'add-book.php' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle"></i> Sell/Exchange
          </a>

          <a href="<?= BASE_URL ?>wishlist.php" class="nav-link nav-badge-link <?= $currentPage === 'wishlist.php' ? 'active' : '' ?>">
            <i class="fas fa-heart"></i> Wishlist
            <span class="nav-badge" id="navWishlistBadge" style="<?= ($wishlistCount > 0) ? '' : 'display:none;' ?>"><?= $wishlistCount ?></span>
          </a>

          <a href="<?= BASE_URL ?>messages.php" class="nav-link nav-badge-link <?= in_array($currentPage, ['messages.php', 'conversation.php']) ? 'active' : '' ?>">
            <i class="fas fa-comments"></i> Messages
            <?php if ($unreadMsgCount > 0): ?>
              <span class="nav-badge"><?= $unreadMsgCount ?></span>
            <?php endif; ?>
          </a>

          <a href="<?= BASE_URL ?>notifications.php" class="nav-link nav-badge-link <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> Alerts
            <?php if ($unreadNotifCount > 0): ?>
              <span class="nav-badge"><?= $unreadNotifCount ?></span>
            <?php endif; ?>
          </a>

          <a href="<?= BASE_URL ?>dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Dashboard
          </a>

        <?php else: ?>
          <a href="<?= BASE_URL ?>index.php#how-it-works" class="nav-link">
            <i class="fas fa-lightbulb"></i> How It Works
          </a>
          <a href="<?= BASE_URL ?>about.php" class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>">
            <i class="fas fa-info-circle"></i> About
          </a>
          <a href="<?= BASE_URL ?>contact.php" class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Contact
          </a>
        <?php endif; ?>

        <!-- Auth Actions / User Dropdown -->
        <div class="nav-auth">
          <?php if (isLoggedIn()): ?>
            <?php if (isAdmin()): ?>
              <a href="<?= BASE_URL ?>admin/dashboard.php" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-shield-alt"></i> Admin Panel
              </a>
            <?php endif; ?>

            <div class="nav-user-dropdown">
              <button class="user-btn" id="userDropdownBtn">
                <?php
                  $avatar = !empty($user['profile_image']) ? $user['profile_image'] : 'default_avatar.svg';
                ?>
                <img src="<?= BASE_URL ?>uploads/profiles/<?= e($avatar) ?>" alt="Avatar" class="user-avatar-mini" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
                <span><?= e(explode(' ', $user['name'] ?? 'Account')[0]) ?></span>
                <i class="fas fa-chevron-down" style="font-size:11px; color:var(--text-muted);"></i>
              </button>

              <div class="dropdown-menu" id="userDropdownMenu">
                <div class="dropdown-header">
                  <div class="dropdown-name"><?= e($user['name'] ?? 'Student') ?></div>
                  <div class="dropdown-email"><?= e($user['email'] ?? '') ?></div>
                </div>
                <a href="<?= BASE_URL ?>profile.php" class="dropdown-item">
                  <i class="fas fa-user-circle"></i> My Profile
                </a>
                <a href="<?= BASE_URL ?>my-listings.php" class="dropdown-item">
                  <i class="fas fa-book"></i> My Listings
                </a>
                <a href="<?= BASE_URL ?>my-requests.php" class="dropdown-item">
                  <i class="fas fa-paper-plane"></i> Sent Requests
                </a>
                <a href="<?= BASE_URL ?>received-requests.php" class="dropdown-item">
                  <i class="fas fa-inbox"></i> Received Requests
                </a>
                <a href="<?= BASE_URL ?>my-reviews.php" class="dropdown-item">
                  <i class="fas fa-star"></i> My Reviews
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= BASE_URL ?>logout.php" class="dropdown-item" style="color:var(--danger);">
                  <i class="fas fa-sign-out-alt"></i> Logout
                </a>
              </div>
            </div>

          <?php else: ?>
            <a href="<?= BASE_URL ?>login.php" class="btn btn-sm btn-outline">
              <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="<?= BASE_URL ?>register.php" class="btn btn-sm btn-primary">
              <i class="fas fa-user-plus"></i> Register
            </a>
          <?php endif; ?>
        </div>
      </div>
    </nav>
  </div>
</header>
