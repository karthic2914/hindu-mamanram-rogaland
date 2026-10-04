/* Submenu icons for Hindu Mamanram navigation.
   Kept separate from main.js so existing navigation logic is untouched. */
(function () {
  'use strict';

  const iconMap = {
    'worship.html': 'fa-solid fa-om',
    'donate.html': 'fa-solid fa-hand-holding-heart',
    'wedding.html': 'fa-solid fa-ring',
    'funeral.html': 'fa-solid fa-hands-praying',
    'radio.html': 'fa-solid fa-radio',
    'gallery.html': 'fa-solid fa-images'
  };

  function addIcon(link, iconClass) {
    if (!link || link.querySelector('.submenu-fa-icon')) return;

    const icon = document.createElement('i');
    icon.className = `${iconClass} submenu-fa-icon`;
    icon.setAttribute('aria-hidden', 'true');
    link.prepend(icon);
  }

  function applySubmenuIcons() {
    document.querySelectorAll('.nav .menu a').forEach((link) => {
      const href = link.getAttribute('href') || '';
      const baseHref = href.split('#')[0];

      if (href.startsWith('gallery.html#')) {
        addIcon(link, 'fa-regular fa-image');
        return;
      }

      if (iconMap[baseHref]) {
        addIcon(link, iconMap[baseHref]);
      }
    });
  }

  function addStyles() {
    if (document.getElementById('submenu-icon-styles')) return;

    const style = document.createElement('style');
    style.id = 'submenu-icon-styles';
    style.textContent = `
      .nav .menu a {
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .nav .menu a .submenu-fa-icon {
        width: 20px;
        min-width: 20px;
        text-align: center;
        color: #8b2348;
        font-size: 15px;
        line-height: 1;
        transition: color .18s ease, transform .18s ease;
      }

      .nav .menu a:hover .submenu-fa-icon,
      .nav .menu a:focus-visible .submenu-fa-icon {
        color: currentColor;
        transform: translateX(1px);
      }

      .nav .menu .group-label {
        padding-left: 0;
      }
    `;
    document.head.appendChild(style);
  }

  function init() {
    addStyles();
    applySubmenuIcons();

    /* main.js builds the header dynamically. Observe briefly so icons are
       also applied if this file executes before the header is rendered. */
    const observer = new MutationObserver(() => applySubmenuIcons());
    observer.observe(document.body, { childList: true, subtree: true });
    window.setTimeout(() => observer.disconnect(), 5000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
