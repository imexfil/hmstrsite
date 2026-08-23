document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('.site-nav');
  if (!header) return;

  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 20);
  });
});
