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
  let csrfToken = '';

  async function loadCSRF() {
    try {
      const res = await fetch('../php/api.php?endpoint=csrf_token');
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
      const res = await fetch('../php/api.php?endpoint=reviews&limit=20&all=1');
      const data = await res.json();
      if (data.success) {
        list.innerHTML = data.data.reviews.map(r => `
          <div class="review-item" data-id="${r.id}" data-product="${escapeHtml(r.product_name)}" data-image="${escapeHtml(r.product_image || '')}" data-rating="${r.rating}">
            <h3>${escapeHtml(r.title)}</h3>
            <p class="review-author">${escapeHtml(r.username || '')}</p>
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

  async function createReview(formData) {
    formData.append('csrf_token', csrfToken);
    const res = await fetch('../php/api.php?endpoint=reviews', {
      method: 'POST',
      body: formData
    });
    return res.json();
  }

  async function updateReview(id, formData) {
    formData.append('csrf_token', csrfToken);
    formData.append('_method', 'PUT');
    const res = await fetch(`../php/api.php?endpoint=reviews?id=${id}`, {
      method: 'POST',
      body: formData
    });
    return res.json();
  }

  async function deleteReview(id) {
    const res = await fetch(`../php/api.php?endpoint=reviews?id=${id}`, {
      method: 'DELETE',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ csrf_token: csrfToken })
    });
    return res.json();
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
    formData.append('old_image', form.old_image.value);
    let result;
    if (form.dataset.editId) {
      result = await updateReview(form.dataset.editId, formData);
    } else {
      result = await createReview(formData);
    }
    if (result.success) {
      form.reset();
      form.old_image.value = '';
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
      form.image.value = '';
      form.old_image.value = item.dataset.image || '';
      form.content.value = item.querySelector('p').textContent;
      form.dataset.editId = e.target.dataset.id;
      submitBtn.textContent = 'Aggiorna';
      window.scrollTo({ top: form.offsetTop, behavior: 'smooth' });
    }
  });

  await loadCSRF();
  loadReviews();
});
