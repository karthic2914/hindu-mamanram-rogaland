/* Submenu icons for Hindu Mamanram navigation.
   Separate from main.js so existing navigation/dropdown logic stays untouched. */
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

  function addFaIcon(link, iconClass) {
    if (!link || link.querySelector('.submenu-fa-icon, .submenu-goddess-icon')) return;
    const icon = document.createElement('i');
    icon.className = `${iconClass} submenu-fa-icon`;
    icon.setAttribute('aria-hidden', 'true');
    link.prepend(icon);
  }

  function addGoddessIcon(link) {
    if (!link || link.querySelector('.submenu-fa-icon, .submenu-goddess-icon')) return;
    const image = document.createElement('img');
    image.src = 'assets/deity-lakshmi.jpg';
    image.alt = '';
    image.className = 'submenu-goddess-icon';
    image.setAttribute('aria-hidden', 'true');
    link.prepend(image);
  }

  function applySubmenuIcons() {
    document.querySelectorAll('.nav .menu a').forEach((link) => {
      const href = link.getAttribute('href') || '';
      const baseHref = href.split('#')[0];

      // Varalakshmi pooja albums use Goddess Lakshmi instead of a generic photo icon.
      if (/^gallery\.html#y20\d{2}$/.test(href)) {
        addGoddessIcon(link);
        return;
      }

      if (iconMap[baseHref]) addFaIcon(link, iconMap[baseHref]);
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
      .nav .menu a .submenu-goddess-icon {
        width: 27px;
        height: 27px;
        min-width: 27px;
        object-fit: cover;
        object-position: center;
        border-radius: 50%;
        border: 1px solid rgba(139,35,72,.18);
        box-shadow: 0 2px 7px rgba(70,25,35,.12);
        transition: transform .18s ease, box-shadow .18s ease;
      }
      .nav .menu a:hover .submenu-fa-icon,
      .nav .menu a:focus-visible .submenu-fa-icon {
        color: currentColor;
        transform: translateX(1px);
      }
      .nav .menu a:hover .submenu-goddess-icon,
      .nav .menu a:focus-visible .submenu-goddess-icon {
        transform: scale(1.07);
        box-shadow: 0 3px 9px rgba(70,25,35,.18);
      }
      .nav .menu .group-label { padding-left: 0; }
    `;
    document.head.appendChild(style);
  }

  function init() {
    addStyles();
    applySubmenuIcons();
    const observer = new MutationObserver(applySubmenuIcons);
    observer.observe(document.body, { childList: true, subtree: true });
    window.setTimeout(() => observer.disconnect(), 5000);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
