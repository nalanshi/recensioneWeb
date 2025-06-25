(function() {
  function applyTheme(theme) {
    var chosen = theme;
    if (theme === 'auto') {
      chosen = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', chosen);
  }

  var saved = localStorage.getItem('theme') || 'light';
  applyTheme(saved);

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
    if (localStorage.getItem('theme') === 'auto') {
      applyTheme('auto');
    }
  });
})();
