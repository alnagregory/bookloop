/**
 * BOOKLOOP - Modern Interactive JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. Mobile Menu Toggle
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      navLinks.classList.toggle('active');
    });
  }

  // 2. User Dropdown Menu
  const userDropdownBtn = document.getElementById('userDropdownBtn');
  const userDropdownMenu = document.getElementById('userDropdownMenu');
  if (userDropdownBtn && userDropdownMenu) {
    userDropdownBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      userDropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', function (e) {
      if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
        userDropdownMenu.classList.remove('show');
      }
    });
  }

  // 3. Password Visibility Toggle
  const togglePassBtns = document.querySelectorAll('.toggle-password');
  togglePassBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input) {
        if (input.type === 'password') {
          input.type = 'text';
          this.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
          input.type = 'password';
          this.innerHTML = '<i class="fas fa-eye"></i>';
        }
      }
    });
  });

  // 4. Image Upload Preview
  const imageInput = document.getElementById('imageUploadInput');
  const imagePreview = document.getElementById('imageUploadPreview');
  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        // Validate size (5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert('Image file size must be less than 5MB.');
          this.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
          imagePreview.src = e.target.result;
          imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // 5. Auto dismiss flash alert after 5s
  const flashAlert = document.getElementById('flashAlert');
  if (flashAlert) {
    setTimeout(() => {
      flashAlert.style.transition = 'opacity 0.5s ease';
      flashAlert.style.opacity = '0';
      setTimeout(() => flashAlert.remove(), 500);
    }, 5000);
  }

  // 6. Chat auto-scroll to bottom
  const chatMessages = document.getElementById('chatMessages');
  if (chatMessages) {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // 7. Textarea Character Counters
  const trackedTextareas = document.querySelectorAll('[data-max-chars]');
  trackedTextareas.forEach(ta => {
    const max = parseInt(ta.getAttribute('data-max-chars'));
    const counterId = ta.getAttribute('data-counter-id');
    const counter = document.getElementById(counterId);
    if (counter) {
      ta.addEventListener('input', function () {
        const remaining = max - this.value.length;
        counter.textContent = remaining + ' characters left';
        if (remaining < 0) {
          counter.style.color = 'var(--danger)';
        } else {
          counter.style.color = 'var(--text-muted)';
        }
      });
    }
  });

  // 8. Wishlist Toggle Handlers
  const wishlistBtns = document.querySelectorAll('.wishlist-toggle-btn');
  wishlistBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const bookId = this.getAttribute('data-book-id');
      if (!bookId) return;

      fetch((window.BOOKLOOP_BASE_URL || '') + 'api-wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'book_id=' + encodeURIComponent(bookId)
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          if (data.action === 'added') {
            btn.classList.add('active');
            btn.innerHTML = '<i class="fas fa-heart"></i>';
            showToast('Added to Wishlist!', 'success');
          } else {
            btn.classList.remove('active');
            btn.innerHTML = '<i class="far fa-heart"></i>';
            showToast('Removed from Wishlist', 'info');
          }
          // Update navbar wishlist badge count if present
          const navWishlistBadge = document.getElementById('navWishlistBadge');
          if (navWishlistBadge && typeof data.total !== 'undefined') {
            navWishlistBadge.textContent = data.total;
            navWishlistBadge.style.display = data.total > 0 ? 'inline-block' : 'none';
          }
        } else if (data.status === 'unauthorized') {
          window.location.href = (window.BOOKLOOP_BASE_URL || '') + 'login.php';
        } else {
          showToast(data.message || 'Something went wrong', 'error');
        }
      })
      .catch(err => {
        console.error(err);
      });
    });
  });
});

// Modal Open/Close Global Functions
function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Global Toast Notification Helper
function showToast(message, type = 'info') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.bottom = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.gap = '10px';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `alert alert-${type}`;
  toast.style.margin = '0';
  toast.style.boxShadow = '0 10px 15px -3px rgba(0,0,0,0.1)';
  toast.style.animation = 'fadeIn 0.3s ease';
  toast.innerHTML = `<span>${message}</span>`;

  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.4s ease';
    setTimeout(() => toast.remove(), 400);
  }, 3500);
}
