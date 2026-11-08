<?php
/**
 * SIM Kedisiplinan Siswa - SMA Kartika 1-2 Medan
 * Main Root Entrypoint
 */

require_once __DIR__ . '/config/app.php';

if (is_logged_in()) {
    $user = current_user();
    if ($user) {
        redirect_to_dashboard($user['role']);
    }
}

header("Location: " . BASE_URL . "/login.php");
exit;
