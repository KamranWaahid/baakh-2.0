const initSelected = localStorage.getItem('baakhDarkTheme') === 'dark';
if(initSelected)
{
  const html = document.documentElement; // Get the <body> element instead of <html>
  html.setAttribute('data-theme', 'dark'); // Add 'data-theme' attribute to <body>
}

// JavaScript
function resetTheme() {
  const btnBaakhTheme = document.getElementById('btn-baakh-theme');
  const body = document.documentElement; // Get the <body> element instead of <html>
  const btnDarkThemeIcon = document.getElementById('btnDarkThemeIcon');

  if (btnBaakhTheme.classList.contains('active')) {
    body.setAttribute('data-theme', 'dark'); // Add 'data-theme' attribute to <body>
    localStorage.setItem('baakhDarkTheme', 'dark');
    btnDarkThemeIcon.classList.remove('bi-sun');
    btnDarkThemeIcon.classList.add('bi-moon');
  } else {
    body.removeAttribute('data-theme'); // Remove 'data-theme' attribute from <body>
    localStorage.removeItem('baakhDarkTheme');
    btnDarkThemeIcon.classList.remove('bi-moon');
    btnDarkThemeIcon.classList.add('bi-sun');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Initialize the theme based on user preferences stored in localStorage.
  function initTheme() {
    const darkThemeSelected = localStorage.getItem('baakhDarkTheme') === 'dark';
    const body = document.documentElement; // Get the <body> element instead of <html>
    const btnDarkThemeIcon = document.getElementById('btnDarkThemeIcon');

    if (darkThemeSelected) {
      body.setAttribute('data-theme', 'dark'); // Add 'data-theme' attribute to <body>
      btnDarkThemeIcon.classList.remove('bi-sun');
      btnDarkThemeIcon.classList.add('bi-moon');
      document.getElementById('btn-baakh-theme').classList.add('active')
    } else {
      body.removeAttribute('data-theme'); // Remove 'data-theme' attribute from <body>
      btnDarkThemeIcon.classList.remove('bi-moon');
      btnDarkThemeIcon.classList.add('bi-sun');
    }
  }

  initTheme(); // Initialize theme immediately

  // Attach the click event listener to the button after the theme is initialized
  const btnBaakhTheme = document.getElementById('btn-baakh-theme');
  btnBaakhTheme.addEventListener('click', function() {
    this.classList.toggle('active');
    resetTheme();
  });
});
