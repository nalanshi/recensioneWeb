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

  const userMenus = document.querySelectorAll('.user-menu');
  if (userMenus.length) {
    userMenus.forEach((menu) => {
      const panel = menu.querySelector('.user-menu-panel');
      if (!panel) return;

      menu.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = panel.classList.toggle('open');
        menu.setAttribute('aria-expanded', isOpen);
        panel.setAttribute('aria-hidden', !isOpen);
      });

      menu.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          menu.click();
        }
      });
    });

    document.addEventListener('click', () => {
      userMenus.forEach((menu) => {
        const panel = menu.querySelector('.user-menu-panel');
        if (panel && panel.classList.contains('open')) {
          panel.classList.remove('open');
          menu.setAttribute('aria-expanded', 'false');
          panel.setAttribute('aria-hidden', 'true');
        }
      });
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

    const ratingFilter = document.getElementById('rating-filter');
    const searchFilter = document.getElementById('search-filter');
    const sortFilter = document.getElementById('sort-filter');
    const pagination = document.getElementById('reviewsPagination');
    const noReviews = document.getElementById('no-reviews');
    const reviewsPerPage = 20;
    let reviewsPage = 1;
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = parseInt(urlParams.get('page'), 10);
    if (!isNaN(initialPage) && initialPage > 1) {
      reviewsPage = initialPage;
    }
    let currentReviews = [];

    function escapeHtml(text) {
      const p = document.createElement('p');
      p.textContent = text;
      return p.innerHTML;
    }

    async function loadReviews(page = 1) {
      reviewsPage = page;
      history.replaceState(null, '', `?page=${page}`);
      if (reviewsGrid) {
        reviewsGrid.innerHTML = `\n        <div class="list-loading" role="status" aria-live="polite">\n          <span class="spinner" aria-hidden="true"></span> Caricamento in corso...\n        </div>`;
      }
      if (pagination) pagination.innerHTML = '';
      const params = new URLSearchParams({ page, limit: reviewsPerPage });
      try {
        const res = await fetch(`../php/api.php?endpoint=public_reviews&${params}`);
        const data = await res.json();
        if (data.success) {
          currentReviews = data.data.reviews;
          displayReviews();
          updatePagination(data.data);
        } else {
          reviewsGrid.textContent = 'Errore nel caricamento delle recensioni';
        }
      } catch {
        reviewsGrid.textContent = 'Errore di rete';
      }
    }

    function filterReviews(reviews) {
      const rating = ratingFilter ? ratingFilter.value : '';
      const search = searchFilter ? searchFilter.value.toLowerCase() : '';
      return reviews.filter(r => {
        if (rating && parseFloat(r.average_rating) < parseFloat(rating)) return false;
        if (search && !r.product_name.toLowerCase().includes(search)) return false;
        return true;
      });
    }

    function displayReviews() {
      let reviews = filterReviews(currentReviews);
      if (sortFilter && sortFilter.value === 'rating') {
        reviews.sort((a, b) => parseFloat(b.average_rating) - parseFloat(a.average_rating));
      }
      if (reviews.length === 0) {
        reviewsGrid.innerHTML = '';
        if (noReviews) noReviews.style.display = 'block';
        reviewsGrid.style.display = 'none';
        return;
      }
      const html = reviews.map(r => {
        const img = r.product_image ? `<img src='../${escapeHtml(r.product_image)}' alt='${escapeHtml(r.product_name)}' class='review-image'>` : '';
        return `<a href='recensione.php?id=${r.id}' class='review-card-main' data-rating='${r.average_rating}' data-product="${escapeHtml(r.product_name)}">` +
               `<div class='review-content'>` +
               `<div class='review-header'><h3 class='review-title'>${escapeHtml(r.title)}</h3>` +
               `<div class='review-rating' aria-label='Valutazione ${r.average_rating} su 5'>${escapeHtml(r.average_rating)}/5</div></div>` +
               `${img}` +
               `<div class='review-meta'><span class='review-author'>${escapeHtml(r.username)}</span><span>•</span><span class='review-date'>${escapeHtml(r.formatted_date)}</span></div>` +
               `<p class='review-excerpt'>${escapeHtml(r.content_preview)}</p>` +
               `</div></a>`;
      }).join('');
      reviewsGrid.innerHTML = html;
      if (noReviews) noReviews.style.display = 'none';
      reviewsGrid.style.display = 'grid';
    }

    function updatePagination(data) {
      if (!pagination) return;
      const totalPages = data.total_pages;
      const current = data.page;
      if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
      }
      let html = '';
      if (current > 1) {
        html += `<button class="pagination-btn" data-page="${current - 1}"><i aria-hidden="true" class="fas fa-chevron-left"></i></button>`;
      }
      const startPage = Math.max(1, current - 2);
      const endPage = Math.min(totalPages, current + 2);
      if (startPage > 1) {
        html += `<button class="pagination-btn" data-page="1">1</button>`;
        if (startPage > 2) html += `<span class="pagination-dots">...</span>`;
      }
      for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-btn ${i === current ? 'active' : ''}" data-page="${i}">${i}</button>`;
      }
      if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span class="pagination-dots">...</span>`;
        html += `<button class="pagination-btn" data-page="${totalPages}">${totalPages}</button>`;
      }
      if (current < totalPages) {
        html += `<button class="pagination-btn" data-page="${current + 1}"><i aria-hidden="true" class="fas fa-chevron-right"></i></button>`;
      }
      pagination.innerHTML = html;
      pagination.querySelectorAll('button[data-page]').forEach(btn => {
        btn.addEventListener('click', () => loadReviews(parseInt(btn.dataset.page, 10)));
      });
    }

    if (ratingFilter) ratingFilter.addEventListener('change', displayReviews);
    if (searchFilter) searchFilter.addEventListener('input', displayReviews);
    if (sortFilter) sortFilter.addEventListener('change', displayReviews);

    loadReviews(reviewsPage);
  }

  // Disable navigation link for the current page
  let currentPage = window.location.pathname.split('/').pop();
  if (!currentPage) {
    currentPage = 'index.php';
  }
  const navAnchors = document.querySelectorAll('.nav-links a, .logo-link, .user-menu-panel a');
  navAnchors.forEach((link) => {
    const linkPage = new URL(link.href).pathname.split('/').pop();
    if (linkPage === currentPage) {
      link.setAttribute('aria-current', 'page');
      link.removeAttribute('href');
      link.style.pointerEvents = 'none';
      link.style.cursor = 'default';
    }
  });
});
