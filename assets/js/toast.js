/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Toast Notification System
 */

const Toast = (function() {
  let container = null;

  function ensureContainer() {
    if (!container) {
      container = document.getElementById('toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
      }
    }
    return container;
  }

  function getIcon(type) {
    switch (type) {
      case 'success':
        return `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
      case 'error':
        return `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
      case 'warning':
        return `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>`;
      case 'info':
      default:
        return `<svg class="toast-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`;
    }
  }

  function show(type, title, message, duration = 4000) {
    const cont = ensureContainer();
    const item = document.createElement('div');
    item.className = `toast-item ${type}`;

    item.innerHTML = `
      ${getIcon(type)}
      <div class="toast-content">
        <div class="toast-title">${title}</div>
        ${message ? `<div class="toast-message">${message}</div>` : ''}
      </div>
      <button type="button" class="toast-close" aria-label="Tutup">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    `;

    const closeBtn = item.querySelector('.toast-close');
    const dismiss = () => {
      item.classList.remove('show');
      setTimeout(() => {
        if (item.parentNode) item.parentNode.removeChild(item);
      }, 250);
    };

    closeBtn.addEventListener('click', dismiss);

    cont.appendChild(item);
    // Trigger transition
    requestAnimationFrame(() => {
      item.classList.add('show');
    });

    if (duration > 0) {
      setTimeout(dismiss, duration);
    }

    return dismiss;
  }

  return {
    success: (title, message, duration) => show('success', title, message, duration),
    error: (title, message, duration) => show('error', title, message, duration),
    warning: (title, message, duration) => show('warning', title, message, duration),
    info: (title, message, duration) => show('info', title, message, duration)
  };
})();

window.Toast = Toast;
