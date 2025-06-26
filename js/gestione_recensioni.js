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
  const submitBtn = form.querySelector('button[type="submit"]');

  const deleteModal = document.getElementById('deleteReviewModal');
  const deleteModalClose = document.getElementById('deleteReviewModalClose');
  const cancelDeleteBtn = document.getElementById('cancelReviewDeleteBtn');
  const confirmDeleteBtn = document.getElementById('confirmReviewDeleteBtn');
  const deleteConfirmation = document.getElementById('deleteReviewConfirmation');
  let reviewIdToDelete = null;

  function escapeHtml(text) {
    const p = document.createElement('p');
    p.textContent = text;
    return p.innerHTML;
  }

  async function loadReviews() {
    try {
      const res = await fetch('api.php?endpoint=reviews&limit=20&all=1');
      const data = await res.json();
      if (data.success) {
        list.innerHTML = data.data.reviews.map(r => `
          <div class="review-item" data-id="${r.id}" data-product="${escapeHtml(r.product_name)}" data-image="${escapeHtml(r.product_image || '')}" data-rating="${r.rating}" data-content="${escapeHtml(r.content)}" data-title="${escapeHtml(r.title)}">
            <div class="review-summary">
              <h3>${escapeHtml(r.title)}</h3>
              <p class="review-author">${escapeHtml(r.username || '')}</p>
            </div>
            <div class="review-details">
              ${r.product_image ? `<img src="${escapeHtml(r.product_image)}" alt="${escapeHtml(r.product_name)}" class="review-image">` : ''}
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
      } else {
        list.textContent = 'Errore nel caricamento delle recensioni';
      }
    } catch (e) {
      list.textContent = 'Errore di rete';
    }
  }

  async function createReview(formData) {
    const res = await fetch('api.php?endpoint=reviews', {
      method: 'POST',
      body: formData
    });
    return res.json();
  }


  async function deleteReview(id) {
    const res = await fetch(`api.php?endpoint=reviews&id=${id}`, {
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
      loadReviews();
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
            loadReviews();
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

  loadReviews();
});
