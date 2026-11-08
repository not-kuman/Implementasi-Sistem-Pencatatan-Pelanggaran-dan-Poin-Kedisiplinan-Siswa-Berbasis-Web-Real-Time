<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Layout Footer & Core Scripts
 */
?>
    </main>

    <footer class="app-footer">
      <div class="footer-copyright-text">
        &copy; 2026 &mdash; Copyright by ARSYAD SADEWA, BAGAS PRASETYO, NIKEN AYU RESDIANA. All Rights Reserved.
      </div>
      <div class="footer-meta-text">
        <span><?= e(SCHOOL_NAME) ?></span>
        <span class="footer-dot">&bull;</span>
        <span>Tahun Ajaran <?= e(get_setting('academic_year', '2026/2027')) ?></span>
      </div>
    </footer>
  </div>
</div>

<!-- Global Toast Container -->
<div id="toast-container"></div>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/toast.js"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<?php if (!empty($extra_js)): ?>
  <?= $extra_js ?>
<?php endif; ?>

<script>
// Check for session flash messages
<?php if (!empty($_SESSION['flash_success'])): ?>
  Toast.success('Berhasil', <?= json_encode($_SESSION['flash_success']) ?>);
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  Toast.error('Kesalahan', <?= json_encode($_SESSION['flash_error']) ?>);
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
</script>
</body>
</html>
