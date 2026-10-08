<?php
/**
 * BOOKLOOP - Common Footer Component
 */
?>
  </div><!-- /.container -->
</main><!-- /.main-content -->

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <!-- Col 1: Brand & Bio -->
      <div class="footer-brand">
        <h3 class="brand-name">Book<span>Loop</span></h3>
        <p>A smart, student-centric academic book exchange and marketplace. Save money on textbooks, pass knowledge forward, and connect safely with peers across campus.</p>
        <div style="display:flex; gap:12px; color:#cbd5e1; font-size:18px;">
          <a href="#" style="color:#94a3b8;"><i class="fab fa-facebook"></i></a>
          <a href="#" style="color:#94a3b8;"><i class="fab fa-instagram"></i></a>
          <a href="#" style="color:#94a3b8;"><i class="fab fa-linkedin"></i></a>
          <a href="#" style="color:#94a3b8;"><i class="fab fa-github"></i></a>
        </div>
      </div>

      <!-- Col 2: Quick Links -->
      <div class="footer-col">
        <h4>Explore Marketplace</h4>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>books.php"><i class="fas fa-chevron-right" style="font-size:10px;"></i> Browse All Books</a></li>
          <li><a href="<?= BASE_URL ?>add-book.php"><i class="fas fa-chevron-right" style="font-size:10px;"></i> Sell or Exchange a Book</a></li>
          <li><a href="<?= BASE_URL ?>index.php#how-it-works"><i class="fas fa-chevron-right" style="font-size:10px;"></i> How It Works</a></li>
          <li><a href="<?= BASE_URL ?>wishlist.php"><i class="fas fa-chevron-right" style="font-size:10px;"></i> Saved Wishlist</a></li>
          <li><a href="<?= BASE_URL ?>about.php"><i class="fas fa-chevron-right" style="font-size:10px;"></i> About BookLoop</a></li>
        </ul>
      </div>

      <!-- Col 3: Departments -->
      <div class="footer-col">
        <h4>Popular Branches</h4>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>books.php?department=Computer+Science"><i class="fas fa-chevron-right" style="font-size:10px;"></i> Computer Science (CSE)</a></li>
          <li><a href="<?= BASE_URL ?>books.php?department=Information+Technology"><i class="fas fa-chevron-right" style="font-size:10px;"></i> Information Technology (IT)</a></li>
          <li><a href="<?= BASE_URL ?>books.php?department=Electronics"><i class="fas fa-chevron-right" style="font-size:10px;"></i> Electronics & Comm. (ECE)</a></li>
          <li><a href="<?= BASE_URL ?>books.php?department=Mechanical"><i class="fas fa-chevron-right" style="font-size:10px;"></i> Mechanical Engineering</a></li>
          <li><a href="<?= BASE_URL ?>books.php?department=General"><i class="fas fa-chevron-right" style="font-size:10px;"></i> First Year & General Maths</a></li>
        </ul>
      </div>

      <!-- Col 4: Student Safety -->
      <div class="footer-col">
        <h4>Campus Safety</h4>
        <ul class="footer-links">
          <li><span style="font-size:13px; color:#94a3b8;"><i class="fas fa-shield-alt" style="color:var(--success);"></i> Always meet in open campus areas like the library or canteen.</span></li>
          <li style="margin-top:10px;"><span style="font-size:13px; color:#94a3b8;"><i class="fas fa-lock" style="color:var(--primary);"></i> Communicate via private chat; never share confidential credentials.</span></li>
          <li style="margin-top:10px;"><a href="<?= BASE_URL ?>contact.php"><i class="fas fa-headset" style="font-size:10px;"></i> Help & Support Desk</a></li>
        </ul>
      </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom">
      <div>
        &copy; <?= date('Y') ?> <strong>BookLoop</strong> – Online Book Exchange & Marketplace. Built for college students.
      </div>
      <div>
        “Buy. Sell. Exchange. Learn.” &bull; <a href="<?= BASE_URL ?>admin/login.php" style="color:#64748b; font-size:12px;">Admin Portal</a>
      </div>
    </div>
  </div>
</footer>

<!-- Global Toast Container -->
<div id="toastContainer"></div>

<!-- Custom Scripts -->
<script src="<?= BASE_URL ?>js/script.js"></script>
</body>
</html>
