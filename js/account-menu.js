document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.account-menu').forEach((menu) => {
    const button = menu.querySelector('.avatar-circle');
    const popup = menu.querySelector('.account-popup');

    button.addEventListener('click', () => {
      const isOpen = popup.hasAttribute('data-open');
      document.querySelectorAll('.account-popup[data-open]').forEach((openPopup) => {
        openPopup.removeAttribute('data-open');
      });
      if (!isOpen) popup.setAttribute('data-open', '');
    });

    document.addEventListener('click', (event) => {
      if (!menu.contains(event.target)) popup.removeAttribute('data-open');
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') popup.removeAttribute('data-open');
    });
  });
});
