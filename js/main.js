document.addEventListener('DOMContentLoaded', function() {
  // Funzionalità del carosello
  let slideIndex = 0;
  const slides = document.querySelectorAll('.carousel-slide');
  const dots = document.querySelectorAll('.dot');
  const interval = 5000; // Cambia slide ogni 5 secondi
  
  function showSlides() {
    // Nasconde tutte le slide
    for (let i = 0; i < slides.length; i++) {
      slides[i].classList.remove('active');
      dots[i].classList.remove('active');
      dots[i].setAttribute('aria-selected', 'false');
      dots[i].setAttribute('tabindex', '-1');
    }
    
    // Incrementa l'indice della slide
    slideIndex++;
    
    // Torna alla prima slide se si arriva alla fine
    if (slideIndex > slides.length) {
      slideIndex = 1;
    }
    
    // Mostra la slide corrente
    slides[slideIndex - 1].classList.add('active');
    dots[slideIndex - 1].classList.add('active');
    dots[slideIndex - 1].setAttribute('aria-selected', 'true');
    dots[slideIndex - 1].setAttribute('tabindex', '0');
  }
  
  // Inizializza il carosello
  if (slides.length > 0) {
    slides[0].classList.add('active');
    dots[0].classList.add('active');
    dots[0].setAttribute('aria-selected', 'true');
    dots[0].setAttribute('tabindex', '0');
    setInterval(showSlides, interval);
  }
  
  // Aggiunge gestione click ai punti
  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      // Resetta l'indice e aggiorna la visualizzazione
      slideIndex = index;
      
      // Nasconde tutte le slide
      for (let i = 0; i < slides.length; i++) {
        slides[i].classList.remove('active');
        dots[i].classList.remove('active');
        dots[i].setAttribute('aria-selected', 'false');
        dots[i].setAttribute('tabindex', '-1');
      }
      
      // Mostra la slide selezionata
      slides[index].classList.add('active');
      dots[index].classList.add('active');
      dots[index].setAttribute('aria-selected', 'true');
      dots[index].setAttribute('tabindex', '0');
    });
    
    // Navigazione da tastiera per i punti del carosello
    dot.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dot.click();
      }
    });
  });
  
  // Scorrimento prodotti in orizzontale
  const scrollLeftBtn = document.getElementById('scroll-left');
  const scrollRightBtn = document.getElementById('scroll-right');
  const productsContainer = document.querySelector('.products-container');
  const productsScrollContainer = document.querySelector('.products-scroll-container');
  
  if (scrollLeftBtn && scrollRightBtn && productsContainer && productsScrollContainer) {
    let currentPosition = 0;
    const cardWidth = 220; // Larghezza card aggiornata
    const gap = 32; // Spazio tra le card (2rem = 32px)
    const scrollAmount = cardWidth + gap;
    const totalCards = document.querySelectorAll('.product-card').length;
    
    function getVisibleCards() {
      return Math.floor(productsScrollContainer.offsetWidth / scrollAmount);
    }
    
    function getMaxPosition() {
      const visibleCards = getVisibleCards();
      return Math.max(0, (totalCards - visibleCards) * scrollAmount);
    }
    
    function updateScrollButtons() {
      const maxPosition = getMaxPosition();
      scrollLeftBtn.disabled = currentPosition <= 0;
      scrollRightBtn.disabled = currentPosition >= maxPosition;
      
      // Aggiorna attributi ARIA
      scrollLeftBtn.setAttribute('aria-disabled', currentPosition <= 0);
      scrollRightBtn.setAttribute('aria-disabled', currentPosition >= maxPosition);
      
      // Aggiunge feedback visivo per lo stato disabilitato
      if (currentPosition <= 0) {
        scrollLeftBtn.style.opacity = '0.5';
      } else {
        scrollLeftBtn.style.opacity = '1';
      }
      
      if (currentPosition >= maxPosition) {
        scrollRightBtn.style.opacity = '0.5';
      } else {
        scrollRightBtn.style.opacity = '1';
      }
    }
    
    function updateScrollIndicators() {
      // Rimuove gli indicatori esistenti
      const existingIndicators = document.querySelector('.scroll-indicators');
      if (existingIndicators) {
        existingIndicators.remove();
      }
      
      const visibleCards = getVisibleCards();
      
      // Crea nuovi indicatori se necessario
      if (totalCards > visibleCards) {
        const indicatorsContainer = document.createElement('div');
        indicatorsContainer.className = 'scroll-indicators';
        indicatorsContainer.setAttribute('role', 'tablist');
        indicatorsContainer.setAttribute('aria-label', 'Indicatori posizione prodotti');
        
        const totalPositions = Math.ceil(totalCards / visibleCards);
        const currentIndicator = Math.floor(currentPosition / (scrollAmount * visibleCards));
        
        for (let i = 0; i < totalPositions; i++) {
          const indicator = document.createElement('button');
          indicator.className = 'scroll-indicator';
          indicator.setAttribute('role', 'tab');
          indicator.setAttribute('aria-label', `Gruppo prodotti ${i + 1}`);
          
          if (i === currentIndicator) {
            indicator.classList.add('active');
            indicator.setAttribute('aria-selected', 'true');
          } else {
            indicator.setAttribute('aria-selected', 'false');
          }
          
          indicator.addEventListener('click', () => {
            currentPosition = i * scrollAmount * visibleCards;
            const maxPosition = getMaxPosition();
            if (currentPosition > maxPosition) currentPosition = maxPosition;
            
            productsContainer.style.transform = `translateX(-${currentPosition}px)`;
            updateScrollButtons();
            updateScrollIndicators();
          });
          
          indicatorsContainer.appendChild(indicator);
        }
        
        document.querySelector('.recommended-products').appendChild(indicatorsContainer);
      }
    }
    
    // Funzione di scorrimento a sinistra
    function scrollLeft() {
      if (currentPosition > 0) {
        currentPosition = Math.max(0, currentPosition - scrollAmount);
        productsContainer.style.transform = `translateX(-${currentPosition}px)`;
        updateScrollButtons();
        updateScrollIndicators();
      }
    }
    
    // Funzione di scorrimento a destra
    function scrollRight() {
      const maxPosition = getMaxPosition();
      if (currentPosition < maxPosition) {
        currentPosition = Math.min(maxPosition, currentPosition + scrollAmount);
        productsContainer.style.transform = `translateX(-${currentPosition}px)`;
        updateScrollButtons();
        updateScrollIndicators();
      }
    }
    
    // Gestione dei pulsanti di scorrimento
    scrollLeftBtn.addEventListener('click', scrollLeft);
    scrollRightBtn.addEventListener('click', scrollRight);
    
    // Navigazione da tastiera per i pulsanti di scorrimento
    [scrollLeftBtn, scrollRightBtn].forEach(btn => {
      btn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          btn.click();
        }
      });
    });
    
    // Scorrimento con la rotella del mouse sui prodotti
    productsScrollContainer.addEventListener('wheel', (e) => {
      e.preventDefault();
      
      const delta = e.deltaY || e.deltaX;
      const maxPosition = getMaxPosition();
      
      if (delta > 0) {
        // Scorri a destra
        if (currentPosition < maxPosition) {
          currentPosition = Math.min(maxPosition, currentPosition + (scrollAmount / 2));
          productsContainer.style.transform = `translateX(-${currentPosition}px)`;
          updateScrollButtons();
          updateScrollIndicators();
        }
      } else {
        // Scorri a sinistra
        if (currentPosition > 0) {
          currentPosition = Math.max(0, currentPosition - (scrollAmount / 2));
          productsContainer.style.transform = `translateX(-${currentPosition}px)`;
          updateScrollButtons();
          updateScrollIndicators();
        }
      }
    }, { passive: false });
    
    // Supporto touch/swipe per mobile
    let startX = 0;
    let isDragging = false;
    
    productsScrollContainer.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
      isDragging = true;
    }, { passive: true });
    
    productsScrollContainer.addEventListener('touchmove', (e) => {
      if (!isDragging) return;
      e.preventDefault();
    }, { passive: false });
    
    productsScrollContainer.addEventListener('touchend', (e) => {
      if (!isDragging) return;
      
      const endX = e.changedTouches[0].clientX;
      const diffX = startX - endX;
      
      if (Math.abs(diffX) > 50) { // Distanza minima per lo swipe
        if (diffX > 0) {
          // Swipe a sinistra - scorri a destra
          scrollRight();
        } else {
          // Swipe a destra - scorri a sinistra
          scrollLeft();
        }
      }
      
      isDragging = false;
    }, { passive: true });
    
    // Inizializza lo stato di scorrimento
    updateScrollButtons();
    updateScrollIndicators();
    
    // Aggiornamento al ridimensionamento finestra
    window.addEventListener('resize', () => {
      const maxPosition = getMaxPosition();
      
      if (currentPosition > maxPosition) {
        currentPosition = maxPosition;
        productsContainer.style.transform = `translateX(-${currentPosition}px)`;
      }
      
      updateScrollButtons();
      updateScrollIndicators();
    });
  }
  
  // Funzionalità menu hamburger
  const hamburgerMenu = document.querySelector('.hamburger-menu');
  const navLinks = document.querySelector('.nav-links');
  
  if (hamburgerMenu) {
    hamburgerMenu.addEventListener('click', function() {
      this.classList.toggle('active');
      navLinks.classList.toggle('active');
      
      // Aggiorna attributi ARIA
      const isExpanded = this.classList.contains('active');
      this.setAttribute('aria-expanded', isExpanded);
      navLinks.setAttribute('aria-hidden', !isExpanded);
    });
    
    // Navigazione da tastiera per il menu hamburger
    hamburgerMenu.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        hamburgerMenu.click();
      }
    });
    
    // Chiude il menu cliccando fuori
    document.addEventListener('click', (e) => {
      if (!hamburgerMenu.contains(e.target) && !navLinks.contains(e.target)) {
        hamburgerMenu.classList.remove('active');
        navLinks.classList.remove('active');
        hamburgerMenu.setAttribute('aria-expanded', 'false');
        navLinks.setAttribute('aria-hidden', 'true');
      }
    });
  }
  
  // Accessibilità migliorata per le card prodotto
  const productCards = document.querySelectorAll('.product-card');
  productCards.forEach(card => {
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        // Simula click o naviga al dettaglio prodotto
        console.log('Product card activated:', card.querySelector('.product-title').textContent);
      }
    });
  });
});

