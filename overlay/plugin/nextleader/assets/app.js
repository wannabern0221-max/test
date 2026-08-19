document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
  const q = document.querySelector('[data-glossary-search]');
  if (q) {
    q.addEventListener('input', function () {
      const term = q.value.trim().toLocaleLowerCase();
      document.querySelectorAll('[data-glossary-item]').forEach(function (item) {
        item.hidden = term && !item.textContent.toLocaleLowerCase().includes(term);
      });
    });
  }
});
