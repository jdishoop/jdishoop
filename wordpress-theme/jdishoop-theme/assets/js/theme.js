document.addEventListener('DOMContentLoaded', function () {
  const toggleButton = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.primary-nav');

  if (toggleButton && nav) {
    toggleButton.addEventListener('click', function () {
      const isOpen = nav.classList.toggle('open');
      toggleButton.setAttribute('aria-expanded', String(isOpen));
    });
  }
});
