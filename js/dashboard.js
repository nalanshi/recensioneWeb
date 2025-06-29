document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('overlay');

  function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.add('active');
      overlay?.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove('active');
      overlay?.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const section = link.dataset.section;
      document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
      document.getElementById(`${section}-section`)?.classList.add('active');
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
      link.closest('.nav-item')?.classList.add('active');
      history.pushState({section}, '', `#${section}`);
    });
  });

  document.getElementById('editCommentModalClose')?.addEventListener('click', () => closeModal('editCommentModal'));
  document.getElementById('cancelEditCommentBtn')?.addEventListener('click', () => closeModal('editCommentModal'));

  document.querySelectorAll('.edit-comment-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const item = btn.closest('.review-item');
      if (!item) return;
      document.getElementById('editCommentId').value = item.dataset.commentId;
      document.getElementById('editCommentRating').value = item.dataset.rating;
      document.getElementById('editCommentContent').value = item.dataset.content;
      openModal('editCommentModal');
    });
  });

  const editForm = document.getElementById('editCommentForm');
  if (editForm) {
    editForm.addEventListener('submit', () => {
      const btn = editForm.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;
    });
  }
});
