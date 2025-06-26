document.addEventListener('DOMContentLoaded', function() {
  // Carousel functionality
  let slideIndex = 0;
  const slides = document.querySelectorAll('.carousel-slide');
  const dots = document.querySelectorAll('.dot');
  const interval = 5000; // Change slide every 5 seconds
  
  function showSlides() {
    // Hide all slides
    for (let i = 0; i < slides.length; i++) {
      slides[i].classList.remove('active');
      dots[i].classList.remove('active');
      dots[i].setAttribute('aria-selected', 'false');
      dots[i].setAttribute('tabindex', '-1');
    }
    
    // Increment slide index
    slideIndex++;
    
    // Reset to first slide if at the end
    if (slideIndex > slides.length) {
      slideIndex = 1;
    }
    
    // Show current slide
    slides[slideIndex - 1].classList.add('active');
    dots[slideIndex - 1].classList.add('active');
    dots[slideIndex - 1].setAttribute('aria-selected', 'true');
    dots[slideIndex - 1].setAttribute('tabindex', '0');
  }
  
  // Initialize carousel
  if (slides.length > 0) {
    slides[0].classList.add('active');
    dots[0].classList.add('active');
    dots[0].setAttribute('aria-selected', 'true');
    dots[0].setAttribute('tabindex', '0');
    setInterval(showSlides, interval);
  }
  
  // Add click event to dots
  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      // Reset slideIndex and update display
      slideIndex = index;
      
      // Hide all slides
      for (let i = 0; i < slides.length; i++) {
        slides[i].classList.remove('active');
        dots[i].classList.remove('active');
        dots[i].setAttribute('aria-selected', 'false');
        dots[i].setAttribute('tabindex', '-1');
      }
      
      // Show selected slide
      slides[index].classList.add('active');
      dots[index].classList.add('active');
      dots[index].setAttribute('aria-selected', 'true');
      dots[index].setAttribute('tabindex', '0');
    });
    
    // Keyboard navigation for dots
    dot.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        dot.click();
      }
    });
  });
  
  // Products scroll functionality - Horizontal scrolling
  const scrollLeftBtn = document.getElementById('scroll-left');
  const scrollRightBtn = document.getElementById('scroll-right');
  const productsContainer = document.querySelector('.products-container');
  const productsScrollContainer = document.querySelector('.products-scroll-container');
  
  if (scrollLeftBtn && scrollRightBtn && productsContainer && productsScrollContainer) {
    let currentPosition = 0;
    const cardWidth = 220; // Updated to match new card width
    const gap = 32; // gap between cards (2rem = 32px)
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
      
      // Update ARIA attributes
      scrollLeftBtn.setAttribute('aria-disabled', currentPosition <= 0);
      scrollRightBtn.setAttribute('aria-disabled', currentPosition >= maxPosition);
      
      // Add visual feedback for disabled state
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
      // Remove existing indicators
      const existingIndicators = document.querySelector('.scroll-indicators');
      if (existingIndicators) {
        existingIndicators.remove();
      }
      
      const visibleCards = getVisibleCards();
      
      // Create new indicators if needed
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
    
    // Scroll left function
    function scrollLeft() {
      if (currentPosition > 0) {
        currentPosition = Math.max(0, currentPosition - scrollAmount);
        productsContainer.style.transform = `translateX(-${currentPosition}px)`;
        updateScrollButtons();
        updateScrollIndicators();
      }
    }
    
    // Scroll right function
    function scrollRight() {
      const maxPosition = getMaxPosition();
      if (currentPosition < maxPosition) {
        currentPosition = Math.min(maxPosition, currentPosition + scrollAmount);
        productsContainer.style.transform = `translateX(-${currentPosition}px)`;
        updateScrollButtons();
        updateScrollIndicators();
      }
    }
    
    // Event listeners for scroll buttons
    scrollLeftBtn.addEventListener('click', scrollLeft);
    scrollRightBtn.addEventListener('click', scrollRight);
    
    // Keyboard navigation for scroll buttons
    [scrollLeftBtn, scrollRightBtn].forEach(btn => {
      btn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          btn.click();
        }
      });
    });
    
    // Mouse wheel scrolling for products container
    productsScrollContainer.addEventListener('wheel', (e) => {
      e.preventDefault();
      
      const delta = e.deltaY || e.deltaX;
      const maxPosition = getMaxPosition();
      
      if (delta > 0) {
        // Scroll right
        if (currentPosition < maxPosition) {
          currentPosition = Math.min(maxPosition, currentPosition + (scrollAmount / 2));
          productsContainer.style.transform = `translateX(-${currentPosition}px)`;
          updateScrollButtons();
          updateScrollIndicators();
        }
      } else {
        // Scroll left
        if (currentPosition > 0) {
          currentPosition = Math.max(0, currentPosition - (scrollAmount / 2));
          productsContainer.style.transform = `translateX(-${currentPosition}px)`;
          updateScrollButtons();
          updateScrollIndicators();
        }
      }
    }, { passive: false });
    
    // Touch/swipe support for mobile
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
      
      if (Math.abs(diffX) > 50) { // Minimum swipe distance
        if (diffX > 0) {
          // Swipe left - scroll right
          scrollRight();
        } else {
          // Swipe right - scroll left
          scrollLeft();
        }
      }
      
      isDragging = false;
    }, { passive: true });
    
    // Initialize scroll state
    updateScrollButtons();
    updateScrollIndicators();
    
    // Update on window resize
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
  
  // Hamburger menu functionality
  const hamburgerMenu = document.querySelector('.hamburger-menu');
  const navLinks = document.querySelector('.nav-links');
  //AGGIUNTO
  const isExpanded = this.classList.contains('active');
  this.setAttribute('aria-expanded', isExpanded);
  navLinks.setAttribute('aria-hidden', !isExpanded);
  if (hamburgerMenu) {
    hamburgerMenu.addEventListener('click', function() {
      this.classList.toggle('active');
      navLinks.classList.toggle('active');
      
      // Update ARIA attributes
      const isExpanded = this.classList.contains('active');
      this.setAttribute('aria-expanded', isExpanded);
      navLinks.setAttribute('aria-hidden', !isExpanded);
    });
    
    // Keyboard navigation for hamburger menu
    hamburgerMenu.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        hamburgerMenu.click();
      }
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
      if (!hamburgerMenu.contains(e.target) && !navLinks.contains(e.target)) {
        hamburgerMenu.classList.remove('active');
        navLinks.classList.remove('active');
        hamburgerMenu.setAttribute('aria-expanded', 'false');
        navLinks.setAttribute('aria-hidden', 'true');
      }
    });
  }
  
  // Enhanced accessibility for product cards
  const productCards = document.querySelectorAll('.product-card');
  productCards.forEach(card => {
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        // Simulate click or navigate to product detail
        console.log('Product card activated:', card.querySelector('.product-title').textContent);
      }
    });
  });
});

