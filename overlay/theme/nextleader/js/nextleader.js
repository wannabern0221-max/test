document.addEventListener('DOMContentLoaded', function () {
  const header = document.querySelector('.nl-site-header');
  const toggle = document.querySelector('.nl-nav-toggle');
  const nav = document.getElementById('nl-nav');

  function syncHeaderState() {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 8);
  }

  function closeNav() {
    if (!toggle || !nav) return;
    toggle.setAttribute('aria-expanded', 'false');
    nav.classList.remove('is-open');
  }

  syncHeaderState();
  window.addEventListener('scroll', syncHeaderState, { passive: true });

  if (!toggle || !nav) return;

  toggle.addEventListener('click', function () {
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
    nav.classList.toggle('is-open', !open);
  });

  nav.addEventListener('click', function (event) {
    if (event.target.closest('a')) closeNav();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeNav();
  });

  document.addEventListener('click', function (event) {
    if (!nav.classList.contains('is-open')) return;
    if (nav.contains(event.target) || toggle.contains(event.target)) return;
    closeNav();
  });
});
