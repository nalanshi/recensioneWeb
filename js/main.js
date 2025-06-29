document.addEventListener('DOMContentLoaded', function() {
  const hamburgerMenu = document.querySelector('.hamburger-menu');
  const navLinks = document.querySelector('.nav-links');

  if (hamburgerMenu) {
    hamburgerMenu.addEventListener('click', function() {
      this.classList.toggle('active');
      navLinks.classList.toggle('active');

      const isExpanded = this.classList.contains('active');
      this.setAttribute('aria-expanded', isExpanded);
      navLinks.setAttribute('aria-hidden', !isExpanded);
    });

    hamburgerMenu.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        hamburgerMenu.click();
      }
    });

    document.addEventListener('click', (e) => {
      if (!hamburgerMenu.contains(e.target) && !navLinks.contains(e.target)) {
        hamburgerMenu.classList.remove('active');
        navLinks.classList.remove('active');
        hamburgerMenu.setAttribute('aria-expanded', 'false');
        navLinks.setAttribute('aria-hidden', 'true');
      }
    });
  }
  const commentForm = document.getElementById('comment-form');
  if (commentForm) {
    commentForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(commentForm);
      try {
        const res = await fetch('../php/api.php?endpoint=comments', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          commentForm.reset();
          window.location.reload();
        } else {
          alert(data.message || 'Errore');
        }
      } catch {
        alert('Errore di rete');
      }
    });
  }

  const reviewsGrid = document.querySelector('.reviews-grid');
  if (reviewsGrid) {
    reviewsGrid.addEventListener('click', (e) => {
      const reviewCard = e.target.closest('.review-card-main');
      if (reviewCard && reviewCard.getAttribute('href')) {
        window.location.href = reviewCard.getAttribute('href');
      }
    });

    reviewsGrid.addEventListener('keydown', (e) => {
      if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('review-card-main')) {
        e.preventDefault();
        e.target.click();
      }
    });
  }

});
