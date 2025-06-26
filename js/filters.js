document.addEventListener('DOMContentLoaded', function() {
      const categoryFilter = document.getElementById('category-filter');
      const ratingFilter = document.getElementById('rating-filter');
      const searchFilter = document.getElementById('search-filter');
      const sortFilter = document.getElementById('sort-filter');
      const reviewsGrid = document.getElementById('reviews-grid');
      const loadMoreBtn = document.getElementById('load-more-btn');
      const noReviews = document.getElementById('no-reviews');

      // Gestione filtri
      function applyFilters() {
        const category = categoryFilter.value;
        const rating = ratingFilter.value;
        const search = searchFilter.value.toLowerCase();
        const sort = sortFilter.value;

        const reviews = reviewsGrid.querySelectorAll('.review-card');
        let visibleCount = 0;

        reviews.forEach(review => {
          let show = true;

          // Filtro categoria
          if (category && review.dataset.category !== category) {
            show = false;
          }

          // Filtro rating
          if (rating && parseInt(review.dataset.rating) < parseInt(rating)) {
            show = false;
          }

          // Filtro ricerca
          if (search) {
            const title = review.querySelector('.review-title').textContent.toLowerCase();
            const excerpt = review.querySelector('.review-excerpt').textContent.toLowerCase();
            if (!title.includes(search) && !excerpt.includes(search)) {
              show = false;
            }
          }

          review.style.display = show ? 'block' : 'none';
          if (show) visibleCount++;
        });

        // Mostra messaggio se nessuna recensione
        noReviews.style.display = visibleCount === 0 ? 'block' : 'none';
        reviewsGrid.style.display = visibleCount === 0 ? 'none' : 'grid';
      }

      // Event listeners per i filtri
      categoryFilter.addEventListener('change', applyFilters);
      ratingFilter.addEventListener('change', applyFilters);
      searchFilter.addEventListener('input', applyFilters);
      sortFilter.addEventListener('change', applyFilters);

      // Gestione caricamento altre recensioni
      loadMoreBtn.addEventListener('click', function() {
        // Qui si implementerebbe la chiamata AJAX per caricare altre recensioni
        console.log('Caricamento altre recensioni...');
        
        // Simulazione caricamento
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Caricamento...';
        
        setTimeout(() => {
          this.innerHTML = '<i class="fas fa-plus"></i> Carica altre recensioni';
          // Qui si aggiungerebbero le nuove recensioni al DOM
        }, 1000);
      });

      // Gestione click su recensione per aprire dettaglio
      reviewsGrid.addEventListener('click', function(e) {
        const reviewCard = e.target.closest('.review-card');
        if (reviewCard) {
          // Qui si implementerebbe l'apertura del dettaglio recensione
          console.log('Apertura dettaglio recensione');
        }
      });
    });