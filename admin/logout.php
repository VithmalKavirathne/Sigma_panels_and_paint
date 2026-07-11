<?php
// Sigma Panels & Paint - Admin Logout
// Phase 5 implementation.

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logout_admin();
redirect('admin/login.php');
