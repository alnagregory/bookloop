<?php
/**
 * BOOKLOOP - About Us Page
 */

$pageTitle = "About BookLoop";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
  <div class="section-header" style="margin-bottom: 40px;">
    <span class="badge" style="background:#dbeafe; color:var(--primary); font-size:12px; margin-bottom:12px; padding:6px 14px;">
      About the Platform
    </span>
    <h2>BOOKLOOP – Online Book Exchange & Marketplace</h2>
    <p>“Buy. Sell. Exchange. Learn.”</p>
  </div>

  <div class="card" style="padding: 30px; margin-bottom: 30px;">
    <h3 style="font-size:22px; margin-bottom:14px; color:var(--primary);">
      <i class="fas fa-graduation-cap"></i> Empowering College Students
    </h3>
    <p style="font-size:15px; color:#334155; line-height:1.7; margin-bottom:16px;">
      Every semester, university students spend considerable sums purchasing brand-new academic textbooks that are only required for four to six months. Once exams conclude, those heavy reference volumes sit untouched on dormitory shelves while incoming junior students struggle to afford the exact same syllabus editions.
    </p>
    <p style="font-size:15px; color:#334155; line-height:1.7;">
      <strong>BookLoop</strong> solves this disconnect by establishing a direct, hyper-local campus peer-to-peer marketplace. Here, students can seamlessly list their previous semester textbooks, negotiate fair rates, arrange direct physical swaps, and coordinate safe meetings right in the campus library or cafeteria.
    </p>
  </div>

  <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin-bottom:40px;">
    <div class="card" style="padding:24px; text-align:center;">
      <div style="font-size:32px; color:var(--primary); margin-bottom:12px;"><i class="fas fa-piggy-bank"></i></div>
      <h4 style="font-size:17px; margin-bottom:8px;">Affordable Learning</h4>
      <p style="font-size:13px; color:var(--text-muted); line-height:1.5;">Students save up to 70% compared to bookstore prices by buying directly from seniors.</p>
    </div>

    <div class="card" style="padding:24px; text-align:center;">
      <div style="font-size:32px; color:var(--secondary); margin-bottom:12px;"><i class="fas fa-sync-alt"></i></div>
      <h4 style="font-size:17px; margin-bottom:8px;">Zero-Cash Swaps</h4>
      <p style="font-size:13px; color:var(--text-muted); line-height:1.5;">Exchange your completed course textbooks for the ones required in your upcoming semester.</p>
    </div>

    <div class="card" style="padding:24px; text-align:center;">
      <div style="font-size:32px; color:var(--success); margin-bottom:12px;"><i class="fas fa-shield-alt"></i></div>
      <h4 style="font-size:17px; margin-bottom:8px;">Campus Privacy</h4>
      <p style="font-size:13px; color:var(--text-muted); line-height:1.5;">Private internal messaging protects student phone numbers and college credentials.</p>
    </div>
  </div>

  <div class="card" style="padding:30px; background:linear-gradient(135deg, #1e3a8a, #2563eb); color:white;">
    <h3 style="color:white; font-size:20px; margin-bottom:10px;">Built as a Real-World Academic Solution</h3>
    <p style="font-size:14px; color:#bfdbfe; line-height:1.6; margin-bottom:20px;">
      BookLoop is constructed with native PHP, PDO prepared statements, and MySQL for bulletproof reliability, low server overhead, and clear architectural design suitable for college project demonstrations, DBMS labs, and viva examinations.
    </p>
    <a href="<?= BASE_URL ?>register.php" class="btn btn-secondary">
      <i class="fas fa-user-plus"></i> Join the Campus Network
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
