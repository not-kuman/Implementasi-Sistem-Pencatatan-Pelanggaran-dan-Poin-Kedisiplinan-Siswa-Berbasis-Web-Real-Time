/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Main Application Scripts
 */

document.addEventListener('DOMContentLoaded', function() {
  // Mobile Sidebar Toggle
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.getElementById('app-sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');

  if (sidebarToggle && sidebar && backdrop) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      backdrop.classList.toggle('show');
    });

    backdrop.addEventListener('click', function() {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    });
  }

  // Double Submit Prevention
  document.querySelectorAll('form:not(.no-prevent-double)').forEach(form => {
    form.addEventListener('submit', function(e) {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.dataset.originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
          <svg class="animate-spin" style="width:14px;height:14px;margin-right:6px;display:inline-block;animation:spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor" stroke-dasharray="31.4 31.4" fill="none" />
          </svg>
          Memproses...
        `;
        // Safety timeout to re-enable after 8 seconds in case of failure
        setTimeout(() => {
          if (submitBtn.disabled) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.originalText;
          }
        }, 8000);
      }
    });
  });

  // Client-side quick filter for tables
  document.querySelectorAll('[data-table-filter]').forEach(input => {
    const tableId = input.getAttribute('data-table-filter');
    const table = document.getElementById(tableId);
    if (!table) return;

    input.addEventListener('input', function() {
      const query = this.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  });
});

// Modal Management Helper
const Modal = {
  open: function(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
      el.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  },
  close: function(modalId) {
    const el = document.getElementById(modalId);
    if (el) {
      el.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
};

// Global Confirmation Dialog
function confirmAction(options) {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.id = 'dynamic-confirm-modal';

    overlay.innerHTML = `
      <div class="modal-dialog" style="max-width: 420px;">
        <div class="modal-header">
          <h3 class="modal-title">${options.title || 'Konfirmasi Tindakan'}</h3>
          <button type="button" class="modal-close-btn" id="confirm-x-btn">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <p style="color: var(--color-text-muted); line-height: 1.5;">${options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?'}</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="confirm-cancel-btn">${options.cancelText || 'Batal'}</button>
          <button type="button" class="btn ${options.isDanger ? 'btn-danger' : 'btn-primary'}" id="confirm-ok-btn">${options.confirmText || 'Lanjutkan'}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);

    const cleanup = () => {
      overlay.classList.remove('active');
      setTimeout(() => {
        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
      }, 200);
    };

    document.getElementById('confirm-cancel-btn').onclick = () => {
      cleanup();
      resolve(false);
    };
    document.getElementById('confirm-x-btn').onclick = () => {
      cleanup();
      resolve(false);
    };
    document.getElementById('confirm-ok-btn').onclick = () => {
      cleanup();
      resolve(true);
    };
  });
}

// Fetch API wrapper with CSRF injection
async function apiRequest(url, options = {}) {
  const metaCsrf = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : '';

  const headers = options.headers || {};
  headers['X-Requested-With'] = 'XMLHttpRequest';
  if (csrfToken && !headers['X-CSRF-TOKEN']) {
    headers['X-CSRF-TOKEN'] = csrfToken;
  }

  options.headers = headers;
  
  try {
    const response = await fetch(url, options);
    const data = await response.json();
    return data;
  } catch (err) {
    console.error('API Error:', err);
    throw err;
  }
}

// Keyframes injection for spin
const style = document.createElement('style');
style.innerHTML = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
document.head.appendChild(style);
