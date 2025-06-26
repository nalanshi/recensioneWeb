document.addEventListener('DOMContentLoaded', function() {
  const hamburgerMenu = document.querySelector('.hamburger-menu');
  const navLinks = document.querySelector('.nav-links');

  if (hamburgerMenu) {
    hamburgerMenu.addEventListener('click', function() {
      this.classList.toggle('active');
      navLinks.classList.toggle('active');

      const isExpanded = this.classList.contains('active');
      this.setAttribute('aria-expanded', isExpanded);
      navLinks.setAttribute('aria-hidden', !isExpanded);
    });

    hamburgerMenu.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        hamburgerMenu.click();
      }
    });

    document.addEventListener('click', (e) => {
      if (!hamburgerMenu.contains(e.target) && !navLinks.contains(e.target)) {
        hamburgerMenu.classList.remove('active');
        navLinks.classList.remove('active');
        hamburgerMenu.setAttribute('aria-expanded', 'false');
        navLinks.setAttribute('aria-hidden', 'true');
      }
    });
  }
});
