/**
 * Dashboard JavaScript - Gestione interattività e funzionalità dinamiche
 * Basato sulla grafica AgID esistente
 */

class DashboardManager {
    constructor() {
        this.currentSection = 'profilo';
        this.currentPage = 1;
        this.commentsPerPage = 10;
        this.init();
    }

    showPage() {
        document.getElementById("sidebar")?.classList.remove("hidden");
        document.getElementById("main-content")?.classList.remove("hidden");
        document.getElementById("dashboardFooter")?.classList.remove("hidden");
    }


    /**
     * Inizializzazione del dashboard
     */
    async init() {
        this.setupEventListeners();
        this.setupSidebar();
        await this.loadUserData();
        this.setupFormValidation();
        this.setupDateSelectors();
    }


    /**
     * Restituisce il percorso corretto per le immagini
     */
    formatImagePath(path) {
        if (!path) return '';
        if (/^(https?:\/\/|\.|\/)/.test(path)) {
            return path;
        }
        return '../' + path;
    }

    /**
     * Setup degli event listeners
     */
    setupEventListeners() {
        // Navigazione
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                this.switchSection(section);
            });
        });

        // Menu mobile
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebarClose = document.getElementById('sidebarClose');
        const overlay = document.getElementById('overlay');

        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => this.toggleMobileSidebar());
        }
        if (sidebarClose) {
            sidebarClose.addEventListener('click', () => this.closeMobileSidebar());
        }
        if (overlay) {
            overlay.addEventListener('click', () => this.closeMobileSidebar());
        }

        // Uscita
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.logout());
        }

        // Form profilo
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => this.handleProfileSubmit(e));
        }

        // Caricamento foto
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        const photoInput = document.getElementById('photoInput');
        if (changePhotoBtn && photoInput) {
            changePhotoBtn.addEventListener('click', () => {
                // Reset del valore per evitare invii indesiderati se l'utente annulla la selezione
                photoInput.value = '';
                photoInput.click();
            });
            photoInput.addEventListener('change', (e) => this.handlePhotoUpload(e));
        }

        // Modale password
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const passwordModal = document.getElementById('passwordModal');
        const passwordForm = document.getElementById('passwordForm');
        const passwordModalClose = document.getElementById('passwordModalClose');
        const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');

        if (changePasswordBtn) {
            changePasswordBtn.addEventListener('click', () => this.openModal('passwordModal'));
        }
        if (passwordForm) {
            passwordForm.addEventListener('submit', (e) => this.handlePasswordChange(e));
        }
        if (passwordModalClose) {
            passwordModalClose.addEventListener('click', () => this.closeModal('passwordModal'));
        }
        if (cancelPasswordBtn) {
            cancelPasswordBtn.addEventListener('click', () => this.closeModal('passwordModal'));
        }

        // Modale eliminazione account
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        const deleteModal = document.getElementById('deleteModal');
        const deleteModalClose = document.getElementById('deleteModalClose');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteConfirmation = document.getElementById('deleteConfirmation');

        if (deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', () => this.openModal('deleteModal'));
        }
        if (deleteModalClose) {
            deleteModalClose.addEventListener('click', () => this.closeModal('deleteModal'));
        }
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', () => this.closeModal('deleteModal'));
        }
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => this.handleAccountDelete());
        }
        if (deleteConfirmation) {
            deleteConfirmation.addEventListener('input', (e) => {
                const confirmBtn = document.getElementById('confirmDeleteBtn');
                if (confirmBtn) {
                    confirmBtn.disabled = e.target.value !== 'ELIMINA';
                }
            });
        }

        // Modale modifica commento
        const editModalClose = document.getElementById('editCommentModalClose');
        const cancelEditBtn = document.getElementById('cancelEditCommentBtn');
        const editForm = document.getElementById('editCommentForm');

        if (editModalClose) {
            editModalClose.addEventListener('click', () => this.closeModal('editCommentModal'));
        }
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', () => this.closeModal('editCommentModal'));
        }
        if (editForm) {
            editForm.addEventListener('submit', (e) => this.submitEditComment(e));
        }

        // Ricerca e filtri commenti
        const commentSearch = document.getElementById('commentSearch');
        const commentRatingFilter = document.getElementById('commentRatingFilter');
        const commentDateFilter = document.getElementById('commentDateFilter');

        if (commentSearch) {
            commentSearch.addEventListener('input', this.debounce(() => this.loadComments(), 500));
        }
        if (commentRatingFilter) {
            commentRatingFilter.addEventListener('change', () => this.loadComments());
        }
        if (commentDateFilter) {
            commentDateFilter.addEventListener('change', () => this.loadComments());
        }



        // Navigazione da tastiera
        document.addEventListener('keydown', (e) => this.handleKeyboardNavigation(e));
    }

    /**
     * Setup della sidebar
     */
    setupSidebar() {
        // Evidenzia la sezione attiva
        this.updateActiveNavItem(this.currentSection);
    }

    /**
     * Cambia sezione
     */
    switchSection(section) {
        // Nascondi tutte le sezioni
        document.querySelectorAll('.content-section').forEach(sec => {
            sec.classList.remove('active');
        });

        // Mostra la sezione selezionata
        const targetSection = document.getElementById(`${section}-section`);
        if (targetSection) {
            targetSection.classList.add('active');
            this.currentSection = section;
            this.updateActiveNavItem(section);

            // Carica dati specifici per la sezione
            if (section === 'commenti') {
                this.loadComments();
            }

            // Chiudi sidebar mobile
            this.closeMobileSidebar();

            // Aggiorna URL senza ricaricare la pagina
            history.pushState({section}, '', `#${section}`);
        }
    }

    /**
     * Aggiorna elemento di navigazione attivo
     */
    updateActiveNavItem(section) {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });

        const activeLink = document.querySelector(`[data-section="${section}"]`);
        if (activeLink) {
            activeLink.closest('.nav-item').classList.add('active');
        }
    }

    /**
     * Toggle sidebar mobile
     */
    toggleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        }
    }

    /**
     * Chiudi sidebar mobile
     */
    closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        if (sidebar && overlay) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    /**
     * Esegue una fetch e restituisce JSON se possibile
     */
    async safeFetchJson(url, options = {}) {
        const response = await fetch(url, options);
        const contentType = response.headers.get('Content-Type') || '';
        if (contentType.includes('application/json')) {
            return response.json();
        }
        const text = await response.text();
        throw new Error(text);
    }

    /**
     * Carica dati utente
     */
    async loadUserData() {
        try {
            const data = await this.safeFetchJson('api.php?endpoint=profile');

            if (data.success) {
                this.populateUserData(data.data);
                this.showPage();
            } else {
                this.showNotification('Errore nel caricamento dei dati utente', 'error');
            }
        } catch (error) {
            console.error('Errore caricamento dati utente:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }

    /**
     * Popola i dati utente nel form
     */
    populateUserData(userData) {
        // Aggiorna informazioni header
        const userName = document.getElementById('userName');
        const userAvatar = document.getElementById('userAvatar');
        const profilePhoto = document.getElementById('profilePhoto');
        const profileDisplayName = document.getElementById('profileDisplayName');
        const profileUsername = document.getElementById('profileUsername');

        if (userName) {
            userName.textContent = `${userData.first_name} ${userData.last_name}`;
        }
        if (profileDisplayName) {
            profileDisplayName.textContent = `${userData.first_name} ${userData.last_name}`;
        }

        if (userAvatar && userData.profile_photo) {
            userAvatar.src = this.formatImagePath(userData.profile_photo);
        }

        if (profilePhoto && userData.profile_photo) {
            profilePhoto.src = this.formatImagePath(userData.profile_photo);
        }

        if (profileUsername) {
            profileUsername.textContent = `@${userData.username}`;
        }

        // Popola form profilo
        const fields = ['lastName', 'firstName', 'email', 'username'];
        fields.forEach(field => {
            const element = document.getElementById(field);
            if (element && userData[field.replace(/([A-Z])/g, '_$1').toLowerCase()]) {
                element.value = userData[field.replace(/([A-Z])/g, '_$1').toLowerCase()];
            }
        });

        // Data di nascita
        if (userData.birth_day) {
            const daySelect = document.getElementById('birthDay');
            if (daySelect) daySelect.value = userData.birth_day;
        }
        if (userData.birth_month) {
            const monthSelect = document.getElementById('birthMonth');
            if (monthSelect) monthSelect.value = userData.birth_month;
        }
        if (userData.birth_year) {
            const yearSelect = document.getElementById('birthYear');
            if (yearSelect) yearSelect.value = userData.birth_year;
        }
    }

    /**
     * Gestisce submit del form profilo
     */
    async handleProfileSubmit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            last_name: formData.get('lastName'),
            first_name: formData.get('firstName'),
            email: formData.get('email'),
            birth_day: formData.get('birthDay'),
            birth_month: formData.get('birthMonth'),
            birth_year: formData.get('birthYear')
        };

        try {
            const response = await fetch('api.php?endpoint=profile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Profilo aggiornato con successo', 'success');
                this.loadUserData(); // Ricarica i dati
            } else {
                this.showNotification(result.message || 'Errore durante l\'aggiornamento', 'error');
            }
        } catch (error) {
            console.error('Errore aggiornamento profilo:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }

    /**
     * Gestisce upload foto profilo
     */
    async handlePhotoUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validazione client-side
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.showNotification('Tipo di file non supportato', 'error');
            return;
        }

        if (file.size > 5 * 1024 * 1024) { // 5MB
            this.showNotification('File troppo grande (max 5MB)', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('photo', file);

        try {
            const response = await fetch('api.php?endpoint=actions&action=upload_photo', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Foto profilo aggiornata', 'success');
                // Aggiorna le immagini
                const userAvatar = document.getElementById('userAvatar');
                const profilePhoto = document.getElementById('profilePhoto');
                const updatedPath = this.formatImagePath(result.photo_url) + '?t=' + Date.now();
                if (userAvatar) userAvatar.src = updatedPath;
                if (profilePhoto) profilePhoto.src = updatedPath;
            } else {
                this.showNotification(result.message || 'Errore durante l\'upload', 'error');
            }
        } catch (error) {
            console.error('Errore upload foto:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }

    /**
     * Gestisce cambio password
     */
    async handlePasswordChange(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            current_password: formData.get('currentPassword'),
            new_password: formData.get('newPassword'),
            confirm_password: formData.get('confirmPassword')
        };

        try {
            const response = await fetch('api.php?endpoint=actions&action=change_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Password cambiata con successo', 'success');
                this.closeModal('passwordModal');
                e.target.reset();
            } else {
                this.showNotification(result.message || 'Errore durante il cambio password', 'error');
            }
        } catch (error) {
            console.error('Errore cambio password:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }

    /**
     * Gestisce eliminazione account
     */
    async handleAccountDelete() {
        const confirmation = document.getElementById('deleteConfirmation').value;

        if (confirmation !== 'ELIMINA') {
            this.showNotification('Conferma non valida', 'error');
            return;
        }

        try {
            const response = await fetch('api.php?endpoint=profile', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    confirmation: confirmation
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Account eliminato con successo', 'success');
                setTimeout(() => {
                    window.location.href = '../php/login.php';
                }, 2000);
            } else {
                this.showNotification(result.message || 'Errore durante l\'eliminazione', 'error');
            }
        } catch (error) {
            console.error('Errore eliminazione account:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }

    /**
     * Carica commenti utente
     */
    async loadComments(page = 1) {
        const search = document.getElementById('commentSearch')?.value || '';
        const rating = document.getElementById('commentRatingFilter')?.value || '';
        const commentDateFilter = document.getElementById('commentDateFilter')?.value || '';

        const commentsList = document.getElementById('commentsList');
        const pagination = document.getElementById('commentsPagination');
        if (commentsList) {
            commentsList.innerHTML = `
                <div class="list-loading" role="status" aria-live="polite">
                    <span class="spinner" aria-hidden="true"></span> Caricamento in corso...
                </div>`;
        }
        if (pagination) pagination.innerHTML = '';

        const params = new URLSearchParams({
            page: page,
            limit: this.commentsPerPage,
            search: search,
            rating: rating,
            date_filter: commentDateFilter
        });

        try {
            const response = await fetch(`api.php?endpoint=user_comments&${params}`);
            const data = await response.json();

            if (data.success) {
                this.displayComments(data.data);
                this.updatePagination(data.data);
            } else {
                this.showNotification('Errore nel caricamento dei commenti', 'error');
            }
        } catch (error) {
            console.error('Errore caricamento commenti:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }

    /**
     * Visualizza commenti
     */
    displayComments(data) {
        const commentsList = document.getElementById('commentsList');
        if (!commentsList) return;

        if (data.comments.length === 0) {
            commentsList.innerHTML = `
                <div class="no-reviews">
                    <i aria-hidden="true" class="fas fa-star" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                    <h3>Nessun commento trovato</h3>
                    <p>Non hai ancora scritto commenti o nessun commento corrisponde ai filtri selezionati.</p>
                </div>
            `;
            return;
        }

        const reviewsHTML = data.comments.map(comment => `
            <div class="review-item" data-comment-id="${comment.id}"
                 data-content="${this.escapeHtml(comment.content)}"
                 data-rating="${comment.star}"
                 data-title="${this.escapeHtml(comment.title)}">
                <div class="review-content">
                    <div class="review-header">
                        <h3 class="review-title">${this.escapeHtml(comment.title)}</h3>
                        <span class="review-date">${comment.formatted_date}</span>
                    </div>
                    <div class="review-rating">
                        ${comment.stars_html}
                    </div>
                    <p class="review-text">${this.escapeHtml(comment.content_preview)}</p>
                    <div class="review-meta">
                        <span class="product-name"><i aria-hidden="true" class="fas fa-tag"></i> ${this.escapeHtml(comment.title)}</span>
                    </div>
                    <div class="review-actions">
                        <button onclick="dashboard.editComment(${comment.id})" class="btn-action">
                            <i aria-hidden="true" class="fas fa-edit"></i> Modifica
                        </button>
                        <button onclick="dashboard.deleteComment(${comment.id})" class="btn-action btn-danger">
                            <i aria-hidden="true" class="fas fa-trash"></i> Elimina
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        commentsList.innerHTML = reviewsHTML;
    }

    /**
     * Aggiorna paginazione
     */
    updatePagination(data) {
        const pagination = document.getElementById('commentsPagination');
        if (!pagination) return;

        const totalPages = data.total_pages;
        const currentPage = data.page;

        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let paginationHTML = '';

        // Pulsante precedente
        if (currentPage > 1) {
            paginationHTML += `<button onclick="dashboard.loadComments(${currentPage - 1})" class="pagination-btn">
                <i aria-hidden="true" class="fas fa-chevron-left"></i>
            </button>`;
        }

        // Numeri pagina
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<button onclick="dashboard.loadComments(1)" class="pagination-btn">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span class="pagination-dots">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<button onclick="dashboard.loadComments(${i})" 
                class="pagination-btn ${i === currentPage ? 'active' : ''}">${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="pagination-dots">...</span>`;
            }
            paginationHTML += `<button onclick="dashboard.loadComments(${totalPages})" class="pagination-btn">${totalPages}</button>`;
        }

        // Pulsante successivo
        if (currentPage < totalPages) {
            paginationHTML += `<button onclick="dashboard.loadComments(${currentPage + 1})" class="pagination-btn">
                <i aria-hidden="true" class="fas fa-chevron-right"></i>
            </button>`;
        }

        pagination.innerHTML = paginationHTML;
    }

    /**
     * Elimina commento
     */
    async deleteComment(commentId) {
        if (!confirm('Sei sicuro di voler eliminare questo commento?')) {
            return;
        }

        try {
            const response = await fetch(`api.php?endpoint=user_comments&id=${commentId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({})
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Commento eliminato con successo', 'success');
                this.loadComments(this.currentPage);
            } else {
                this.showNotification(result.message || 'Errore durante l\'eliminazione', 'error');
            }
        } catch (error) {
            console.error('Errore eliminazione commento:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }

    /**
     * Apre il modal di modifica con i dati del commento
     */
    editComment(commentId) {
        const item = document.querySelector(`[data-comment-id="${commentId}"]`);
        if (!item) return;

        document.getElementById('editCommentId').value = commentId;
        document.getElementById('editCommentRating').value = item.dataset.rating || '1';
        document.getElementById('editCommentContent').value = item.dataset.content || '';

        this.openModal('editCommentModal');
    }

    /**
     * Invia la modifica del commento
     */
    async submitEditComment(e) {
        e.preventDefault();
        const form = e.target;
        const commentId = document.getElementById('editCommentId').value;
        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        try {
            const res = await fetch(`api.php?endpoint=user_comments&id=${commentId}`, {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            if (result.success) {
                this.showNotification('Commento aggiornato con successo', 'success');
                this.closeModal('editCommentModal');
                this.loadComments(this.currentPage);
            } else {
                this.showNotification(result.message || 'Errore durante l\'aggiornamento', 'error');
            }
        } catch (error) {
            console.error('Errore aggiornamento commento:', error);
            this.showNotification('Errore di connessione', 'error');
        }
    }


    /**
     * Setup del tema
     */

    /**
     * Setup selettori data
     */
    setupDateSelectors() {
        this.populateDaySelector();
        this.populateMonthSelector();
        this.populateYearSelector();
    }

    populateDaySelector() {
        const daySelect = document.getElementById('birthDay');
        if (!daySelect) return;

        for (let i = 1; i <= 31; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            daySelect.appendChild(option);
        }
    }

    populateMonthSelector() {
        const monthSelect = document.getElementById('birthMonth');
        if (!monthSelect) return;

        const months = [
            'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
        ];

        months.forEach((month, index) => {
            const option = document.createElement('option');
            option.value = index + 1;
            option.textContent = month;
            monthSelect.appendChild(option);
        });
    }

    populateYearSelector() {
        const yearSelect = document.getElementById('birthYear');
        if (!yearSelect) return;

        const currentYear = new Date().getFullYear();
        for (let i = currentYear; i >= 1900; i--) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = i;
            yearSelect.appendChild(option);
        }
    }

    /**
     * Setup validazione form
     */
    setupFormValidation() {
        // Validazione email in tempo reale
        const emailInput = document.getElementById('email');
        if (emailInput) {
            emailInput.addEventListener('blur', (e) => {
                this.validateEmail(e.target);
            });
        }

        // Validazione password
        const newPasswordInput = document.getElementById('newPassword');
        const confirmPasswordInput = document.getElementById('confirmPassword');

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', (e) => {
                this.validatePassword(e.target);
            });
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', (e) => {
                this.validatePasswordConfirmation(e.target);
            });
        }
    }

    validateEmail(input) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(input.value);

        this.setFieldValidation(input, isValid, 'Email non valida');
        return isValid;
    }

    validatePassword(input) {
        const password = input.value;
        const minLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);

        const isValid = minLength && hasUpper && hasNumber;
        let message = '';

        if (!minLength) message = 'Minimo 8 caratteri';
        else if (!hasUpper) message = 'Almeno una maiuscola';
        else if (!hasNumber) message = 'Almeno un numero';

        this.setFieldValidation(input, isValid, message);
        return isValid;
    }

    validatePasswordConfirmation(input) {
        const newPassword = document.getElementById('newPassword')?.value;
        const isValid = input.value === newPassword;

        this.setFieldValidation(input, isValid, 'Le password non coincidono');
        return isValid;
    }

    setFieldValidation(input, isValid, message) {
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        if (!isValid && message) {
            const errorElement = document.createElement('small');
            errorElement.className = 'field-error';
            errorElement.style.color = 'var(--danger-agid)';
            errorElement.textContent = message;
            input.parentNode.appendChild(errorElement);
        }

        input.style.borderColor = isValid ? 'var(--success-agid)' : 'var(--danger-agid)';
    }

    /**
     * Gestione navigazione da tastiera
     */
    handleKeyboardNavigation(e) {
        // ESC per chiudere modali
        if (e.key === 'Escape') {
            this.closeAllModals();
            this.closeMobileSidebar();
        }

        // Ctrl+1, Ctrl+2, Ctrl+3 per navigazione rapida
        if (e.ctrlKey) {
            switch (e.key) {
                case '1':
                    e.preventDefault();
                    this.switchSection('profilo');
                    break;
                case '2':
                    e.preventDefault();
                    this.switchSection('commenti');
                    break;
            }
        }
    }

    /**
     * Gestione modali
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Focus sul primo input
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';

            // Reset form se presente
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
                // Rimuovi errori di validazione
                form.querySelectorAll('.field-error').forEach(error => error.remove());
                form.querySelectorAll('input, textarea, select').forEach(input => {
                    input.style.borderColor = '';
                });
            }
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }

    /**
     * Logout
     */
    logout() {
        if (confirm('Sei sicuro di voler uscire?')) {
            window.location.href = '../php/logout.php';
        }
    }

    /**
     * Mostra notifica
     */
    showNotification(message, type = 'info') {
        // Rimuovi notifiche esistenti
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());

        // Crea nuova notifica
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i aria-hidden="true" class="fas ${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                    <i aria-hidden="true" class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Stili per la notifica
        notification.style.cssText = `
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            z-index: 1003;
            min-width: 18.75rem;
            max-width: 31.25rem;
            animation: slideInRight 0.3s ease-out;
        `;

        const content = notification.querySelector('.notification-content');
        content.style.cssText = `
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            color: var(--text-primary);
        `;

        const icon = notification.querySelector('i:first-child');
        icon.style.color = this.getNotificationColor(type);

        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: var(--spacing-xs);
            margin-left: auto;
        `;

        document.body.appendChild(notification);

        // Auto-remove dopo 5 secondi
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    getNotificationColor(type) {
        const colors = {
            success: 'var(--success-agid)',
            error: 'var(--danger-agid)',
            warning: 'var(--warning-agid)',
            info: 'var(--info-agid)'
        };
        return colors[type] || colors.info;
    }

    /**
     * Utility functions
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}

// Inizializza il dashboard quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new DashboardManager();
});

// Gestione navigazione browser
window.addEventListener('popstate', (e) => {
    if (e.state && e.state.section) {
        window.dashboard.switchSection(e.state.section);
    }
});

// Gestione resize per sidebar mobile
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        window.dashboard.closeMobileSidebar();
    }
});

// Aggiungi stili per le animazioni
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }

    .no-reviews {
        text-align: center;
        padding: var(--spacing-xl);
        color: var(--text-secondary);
    }

    .pagination-dots {
        padding: var(--spacing-xs) var(--spacing-sm);
        color: var(--text-secondary);
    }

    .btn-action {
        background: none;
        border: 1px solid var(--border);
        color: var(--text-secondary);
        padding: var(--spacing-xs) var(--spacing-sm);
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: var(--transition);
        font-size: 0.85rem;
    }

    .btn-action:hover {
        color: var(--primary-agid);
        border-color: var(--primary-agid);
    }

    .btn-action.btn-danger:hover {
        color: var(--danger-agid);
        border-color: var(--danger-agid);
    }

    .pagination-btn {
        padding: var(--spacing-xs) var(--spacing-sm);
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-secondary);
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: var(--transition);
        min-width: 2.5rem;
        height: 2.5rem;
        margin: 0 0.125rem;
    }

    .pagination-btn:hover {
        color: var(--primary-agid);
        border-color: var(--primary-agid);
    }

    .pagination-btn.active {
        background: var(--primary-agid);
        color: var(--text-light);
        border-color: var(--primary-agid);
    }
`;
document.head.appendChild(style);
