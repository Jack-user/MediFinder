<?php
session_start();
if (!isset($_SESSION['user_id']) || (($_SESSION['user_role'] ?? 'patient') !== 'patient')) {
    header('Location: /CEMO_System/system/auth/login.php');
    exit;
}
require_once __DIR__ . '/../includes/db.php';
// Reuse existing patient dashboard
include __DIR__ . '/../dashboard.php';


