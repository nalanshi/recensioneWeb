document.addEventListener('DOMContentLoaded', async () => {
  const form = document.getElementById('create-review-form');
  const list = document.getElementById('reviews-list');
  const submitBtn = form.querySelector('button[type="submit"]');
  let csrfToken = '';

  async function loadCSRF() {
    try {
      const res = await fetch('../php/csrf_token.php');
      const data = await res.json();
      csrfToken = data.csrf_token || '';
    } catch (e) {
      csrfToken = '';
    }
  }

  function escapeHtml(text) {
    const p = document.createElement('p');
    p.textContent = text;
    return p.innerHTML;
  }

  async function loadReviews() {
    try {
      const res = await fetch('../php/reviews_api.php?limit=20');
      const data = await res.json();
      if (data.success) {
        list.innerHTML = data.data.reviews.map(r => `
          <div class="review-item" data-id="${r.id}" data-product="${escapeHtml(r.product_name)}" data-rating="${r.rating}">
            <h3>${escapeHtml(r.title)}</h3>
            <p>${escapeHtml(r.content)}</p>
            <div class="review-actions">
              <button class="edit-btn" data-id="${r.id}">Modifica</button>
              <button class="delete-btn" data-id="${r.id}">Elimina</button>
            </div>
          </div>
        `).join('');
      } else {
        list.textContent = 'Errore nel caricamento delle recensioni';
      }
    } catch (e) {
      list.textContent = 'Errore di rete';
    }
  }

  async function createReview(data) {
    const res = await fetch('../php/reviews_api.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ ...data, csrf_token: csrfToken })
    });
    return res.json();
  }

  async function updateReview(id, data) {
    const res = await fetch(`../php/reviews_api.php?id=${id}`, {
      method: 'PUT',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ ...data, csrf_token: csrfToken })
    });
    return res.json();
  }

  async function deleteReview(id) {
    const res = await fetch(`../php/reviews_api.php?id=${id}`, {
      method: 'DELETE',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ csrf_token: csrfToken })
    });
    return res.json();
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const data = {
      title: form.title.value,
      product_name: form.product.value,
      rating: form.rating.value,
      content: form.content.value,
      product_image: ''
    };
    let result;
    if (form.dataset.editId) {
      result = await updateReview(form.dataset.editId, data);
    } else {
      result = await createReview(data);
    }
    if (result.success) {
      form.reset();
      delete form.dataset.editId;
      submitBtn.textContent = 'Pubblica';
      loadReviews();
    } else {
      alert(result.message || 'Errore');
    }
  });

  list.addEventListener('click', async e => {
    if (e.target.classList.contains('delete-btn')) {
      const id = e.target.dataset.id;
      if (confirm('Eliminare la recensione?')) {
        const r = await deleteReview(id);
        if (r.success) loadReviews(); else alert(r.message || 'Errore');
      }
    } else if (e.target.classList.contains('edit-btn')) {
      const item = e.target.closest('.review-item');
      form.title.value = item.querySelector('h3').textContent;
      form.product.value = item.dataset.product || '';
      form.rating.value = item.dataset.rating || 5;
      form.content.value = item.querySelector('p').textContent;
      form.dataset.editId = e.target.dataset.id;
      submitBtn.textContent = 'Aggiorna';
      window.scrollTo({ top: form.offsetTop, behavior: 'smooth' });
    }
  });

  await loadCSRF();
  loadReviews();
});
