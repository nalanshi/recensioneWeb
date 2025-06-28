document.addEventListener('DOMContentLoaded', async () => {
  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const section = link.dataset.section;
      document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
      const target = document.getElementById(`${section}-section`);
      if (target) target.classList.add('active');
      document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
      link.closest('.nav-item').classList.add('active');
    });
  });
  const form = document.getElementById('create-review-form');
  const list = document.getElementById('reviews-list');
  const searchInput = document.getElementById('reviewSearch');
  const pagination = document.getElementById('reviewsPagination');
  const reviewsPerPage = 10;
  let currentPage = 1;
  const submitBtn = form.querySelector('button[type="submit"]');

  const deleteModal = document.getElementById('deleteReviewModal');
  const deleteModalClose = document.getElementById('deleteReviewModalClose');
  const cancelDeleteBtn = document.getElementById('cancelReviewDeleteBtn');
  const confirmDeleteBtn = document.getElementById('confirmReviewDeleteBtn');
  const deleteConfirmation = document.getElementById('deleteReviewConfirmation');
  let reviewIdToDelete = null;

  function debounce(fn, delay) {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => fn.apply(null, args), delay);
    };
  }

  function escapeHtml(text) {
    const p = document.createElement('p');
    p.textContent = text;
    return p.innerHTML;
  }
  // Aggiunge le recensioni e le rende espandibili
  async function loadReviews(page = 1) {
    const search = searchInput ? searchInput.value : '';
    currentPage = page;
    const params = new URLSearchParams({
      all: 1,
      page: page,
      limit: reviewsPerPage,
      search: search
    });
    try {
      const res = await fetch(`api.php?endpoint=reviews&${params}`);
      const data = await res.json();
      if (data.success) {
        list.innerHTML = data.data.reviews.map(r => `
          <div class="review-item" data-id="${r.id}" data-product="${escapeHtml(r.product_name)}" data-image="${escapeHtml(r.product_image || '')}" data-rating="${r.rating}" data-content="${escapeHtml(r.content)}" data-title="${escapeHtml(r.title)}">
            <div class="review-summary">
              <h3>${escapeHtml(r.title)}</h3>
              <p class="review-author">${escapeHtml(r.username || '')}</p>
            </div>
            <div class="review-details">  
              ${r.product_image ? `<img src="../${escapeHtml(r.product_image)}" alt="${escapeHtml(r.product_name.slice(0, 99))}" class="review-image">` : ''}
              <p><strong>Prodotto:</strong> ${escapeHtml(r.product_name)}</p>
              <p><strong>Valutazione:</strong> ${r.rating}</p>
              <p>${escapeHtml(r.content)}</p>
              <div class="review-actions">
                <button class="delete-btn" data-id="${r.id}">Elimina</button>
              </div>
            </div>
          </div>
        `).join('');

        document.querySelectorAll('.review-details').forEach(d => d.classList.add('hidden'));
        document.querySelectorAll('.review-summary').forEach(sum => {
          sum.addEventListener('click', () => {
            const item = sum.closest('.review-item');
            const details = item.querySelector('.review-details');
            item.classList.toggle('expanded');
            details.classList.toggle('hidden');
          });
        });
        updatePagination(data.data);
      } else {
        list.textContent = 'Errore nel caricamento delle recensioni';
      }
    } catch (e) {
      list.textContent = 'Errore di rete';
    }
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
      html += `<button class="pagination-btn" data-page="${current - 1}">` +
              `<i aria-hidden="true" class="fas fa-chevron-left"></i>` +
              `</button>`;
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
      html += `<button class="pagination-btn" data-page="${current + 1}">` +
              `<i aria-hidden="true" class="fas fa-chevron-right"></i>` +
              `</button>`;
    }

    pagination.innerHTML = html;

    pagination.querySelectorAll('button[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        const page = parseInt(btn.dataset.page, 10);
        if (!isNaN(page)) loadReviews(page);
      });
    });
  }

  async function createReview(formData) {
    const res = await fetch('api.php?endpoint=reviews', {
      method: 'POST',
      body: formData
    });
    return res.json();
  }


  async function deleteReview(id) {
    const res = await fetch(`api.php?endpoint=delete&id=${id}`, {
      method: 'DELETE',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({})
    });
    return res.json();
  }

  function openModal() {
    if (deleteModal) {
      deleteModal.classList.add('active');
      document.body.style.overflow = 'hidden';
      deleteConfirmation.value = '';
      confirmDeleteBtn.disabled = true;
      setTimeout(() => deleteConfirmation.focus(), 100);
    }
  }

  function closeModal() {
    if (deleteModal) {
      deleteModal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('title', form.title.value);
    formData.append('product_name', form.product.value);
    formData.append('rating', form.rating.value);
    formData.append('content', form.content.value);
    if (form.image.files[0]) {
      formData.append('product_image', form.image.files[0]);
    }
    let result = await createReview(formData);
    if (result.success) {
      form.reset();
      submitBtn.textContent = 'Pubblica';
      loadReviews(currentPage);
    } else {
      alert(result.message || 'Errore');
    }
  });

  list.addEventListener('click', async e => {
    const delBtn = e.target.closest('.delete-btn');
    if (delBtn) {
      reviewIdToDelete = delBtn.dataset.id;
      openModal();
    }
  });

  if (deleteModalClose) {
    deleteModalClose.addEventListener('click', closeModal);
  }
  if (cancelDeleteBtn) {
    cancelDeleteBtn.addEventListener('click', closeModal);
  }
  if (deleteConfirmation) {
    deleteConfirmation.addEventListener('input', e => {
      const text = e.target.value.trim().toUpperCase();
      confirmDeleteBtn.disabled = text !== 'ELIMINA';
    });
  }
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', async () => {
      if (reviewIdToDelete && deleteConfirmation.value.trim().toUpperCase() === 'ELIMINA') {
        try {
          const r = await deleteReview(reviewIdToDelete);
            if (r.success) {
              loadReviews(currentPage);
          } else {
            alert(r.message || 'Errore');
          }
        } catch {
          alert('Errore di rete');
        } finally {
          closeModal();
        }
      }
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', debounce(() => loadReviews(1), 500));
  }

  loadReviews();
});
