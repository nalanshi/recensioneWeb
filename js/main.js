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
  }
  
  // Initialize carousel
  if (slides.length > 0) {
    slides[0].classList.add('active');
    dots[0].classList.add('active');
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
      }
      
      // Show selected slide
      slides[index].classList.add('active');
      dots[index].classList.add('active');
    });
  });
  
  // Products scroll functionality
  const scrollLeftBtn = document.getElementById('scroll-left');
  const scrollRightBtn = document.getElementById('scroll-right');
  const productsContainer = document.querySelector('.products-container');
  
  if (scrollLeftBtn && scrollRightBtn && productsContainer) {
    const scrollAmount = 300;
    
    scrollLeftBtn.addEventListener('click', () => {
      productsContainer.scrollBy({
        left: -scrollAmount,
        behavior: 'smooth'
      });
    });
    
    scrollRightBtn.addEventListener('click', () => {
      productsContainer.scrollBy({
        left: scrollAmount,
        behavior: 'smooth'
      });
    });
    
    // Enable horizontal scrolling with mouse wheel
    productsContainer.addEventListener('wheel', (e) => {
      if (e.deltaY !== 0) {
        e.preventDefault();
        productsContainer.scrollBy({
          left: e.deltaY,
          behavior: 'smooth'
        });
      }
    }, { passive: false });
  }
  
  // Hamburger menu functionality
  const hamburgerMenu = document.querySelector('.hamburger-menu');
  const navLinks = document.querySelector('.nav-links');
  
  if (hamburgerMenu) {
    hamburgerMenu.addEventListener('click', function() {
      this.classList.toggle('active');
      navLinks.classList.toggle('active');
    });
  }
});

